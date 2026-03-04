<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StockMovement;
use App\Models\Tank;
use App\Models\Assignment;
use App\Models\SupplyLog;
use App\Models\Expense;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * تقرير كشف حساب الخزان (Ledger)
     */
    public function tankLedger(Request $request)
    {
        // 1. التحقق من الصلاحية
        $this->authorize('reports.view', StockMovement::class);

        $request->validate([
            'tank_id' => 'required|exists:tanks,id',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date',
        ]);

        $tankId = $request->tank_id;
        // افتراضياً نعرض آخر 30 يوماً إذا لم يحدد التاريخ
        $fromDate = $request->from_date ? Carbon::parse($request->from_date)->startOfDay() : now()->subDays(30)->startOfDay();
        $toDate = $request->to_date ? Carbon::parse($request->to_date)->endOfDay() : now()->endOfDay();

        // 2. حساب الرصيد الافتتاحي (Opening Balance)
        // هو الرصيد قبل أول حركة في الفترة المحددة
        $firstMovement = StockMovement::where('tank_id', $tankId)
            ->where('created_at', '>=', $fromDate)
            ->orderBy('created_at', 'asc')
            ->first();

        $openingBalance = $firstMovement ? $firstMovement->balance_before : 0;

        // 3. جلب الحركات داخل الفترة
        $movements = StockMovement::with(['user:id,full_name', 'trackable'])
            ->where('tank_id', $tankId)
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->orderBy('created_at', 'asc')
            ->get();

        // 4. تنسيق البيانات للعرض
        $reportData = $movements->map(function ($move) {
            return [
                'id' => $move->id,
                'date' => $move->created_at->format('Y-m-d H:i'),
                'type' => $this->translateType($move->type),
                'quantity' => (float) $move->quantity,
                'balance_before' => (float) $move->balance_before,
                'balance_after' => (float) $move->balance_after,
                'user' => $move->user?->full_name,
                'notes' => $move->notes,
                // ربط مباشر لمصدر الحركة (توريد أو تكليف)
                'source_id' => $move->trackable_id,
                'source_type' => class_basename($move->trackable_type),
            ];
        });

        return response()->json([
            'tank_info' => Tank::with('fuelType')->find($tankId),
            'period' => [
                'from' => $fromDate->toDateTimeString(),
                'to' => $toDate->toDateTimeString(),
            ],
            'opening_balance' => (float) $openingBalance,
            'current_balance' => (float) (Tank::find($tankId)->current_stock),
            'movements' => $reportData
        ]);
    }

 /**
     * تقرير حركة المحطة (ملخص المبيعات، التوريدات، والمالية لفترة محددة)
     */
    public function dailyMovement(Request $request)
    {
        // 1. التحقق من المدخلات الجديدة (تاريخ البداية والنهاية)
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date', // التأكد أن النهاية بعد أو تساوي البداية
        ]);

        // 2. تحديد نطاق التاريخ
        $start = $request->start_date
            ? Carbon::parse($request->start_date)->startOfDay()
            : now()->startOfDay();

        $end = $request->end_date
            ? Carbon::parse($request->end_date)->endOfDay()
            : now()->endOfDay();

        // 3. ملخص المبيعات والمالية (من التكليفات المكتملة ضمن النطاق الزمني)
        $assignments = Assignment::with(['pump.tank.fuelType'])
            ->where('status', 'completed')
            ->whereBetween('end_at', [$start, $end])
            ->get();

        $totalExpectedAmount = $assignments->sum('expected_amount');
        $totalCash = $assignments->sum('cash_amount');
        $totalBank = $assignments->sum('bank_amount');
        $totalDifference = $assignments->sum('difference');

        // تجميع المبيعات (باللتر والمبلغ) حسب نوع الوقود
        $salesByFuel = [];
        foreach ($assignments as $assignment) {
            $fuelName = $assignment->pump->tank->fuelType->name ?? 'غير محدد';
            $soldLiters = ($assignment->end_counter_1 - $assignment->start_counter_1) +
                          ($assignment->end_counter_2 - $assignment->start_counter_2);

            if (!isset($salesByFuel[$fuelName])) {
                $salesByFuel[$fuelName] = [
                    'liters' => 0,
                    'amount' => 0
                ];
            }
            $salesByFuel[$fuelName]['liters'] += $soldLiters;
            $salesByFuel[$fuelName]['amount'] += ($soldLiters * $assignment->unit_price);
        }

        // 4. ملخص التوريدات (الوقود الوارد للمحطة ضمن النطاق الزمني)
        $supplies = SupplyLog::with(['tank.fuelType'])
            ->whereBetween('created_at', [$start, $end])
            ->get();

        $suppliesByFuel = [];
        foreach ($supplies as $supply) {
            $fuelName = $supply->tank->fuelType->name ?? 'غير محدد';
            if (!isset($suppliesByFuel[$fuelName])) {
                $suppliesByFuel[$fuelName] = 0;
            }
            $suppliesByFuel[$fuelName] += $supply->quantity;
        }

        // ========================================================
        // --- 5. إضافة المصروفات (الجزء الجديد) ---
        // ========================================================
        $expenses = Expense::with('shift')
            ->whereBetween('spent_at', [$start, $end])
            ->get();

        // إجمالي المصروفات كرقم واحد
        $totalExpenses = $expenses->sum('amount');

        // تجهيز قائمة المصروفات لعرضها في التقرير (المبلغ، البيان، الوردية)
        $expensesList = $expenses->map(function ($expense) {
            return [
                'id' => $expense->id,
                'amount' => (float) $expense->amount,
                'description' => $expense->description,
                // بفرض أن مودل Shift يحتوي على حقل name، إذا كان حقلاً آخر مثل shift_number يرجى تعديله
                'shift_name' => $expense->shift->name ?? 'غير محدد',
                'spent_at' => $expense->spent_at->format('Y-m-d H:i'),
            ];
        });
        // ========================================================

        // 6. حالة الخزانات اللحظية
        $tanksStatus = Tank::with('fuelType')->get()->map(function ($tank) {
            return [
                'id' => $tank->id,
                'name' => $tank->name,
                'fuel_type' => $tank->fuelType->name ?? 'غير محدد',
                'capacity' => (float) $tank->capacity,
                'current_stock' => (float) $tank->current_stock,
                'fill_percentage' => $tank->capacity > 0 ? round(($tank->current_stock / $tank->capacity) * 100, 2) : 0,
            ];
        });

        // 7. تجهيز نص التاريخ لعرضه في الواجهة الأمامية
        $dateRangeText = $start->isSameDay($end)
            ? $start->format('Y-m-d')
            : 'من ' . $start->format('Y-m-d') . ' إلى ' . $end->format('Y-m-d');

        return response()->json([
            'date' => $dateRangeText,
            'financial_summary' => [
                'total_expected' => (float) $totalExpectedAmount,
                'total_cash' => (float) $totalCash,
                'total_bank' => (float) $totalBank,
                'total_difference' => (float) $totalDifference,
                // تمت إضافة إجمالي المصروفات للملخص المالي
                'total_expenses' => (float) $totalExpenses,
            ],
            'sales_by_fuel' => $salesByFuel,
            'supplies_by_fuel' => $suppliesByFuel,
            // تمت إضافة قائمة المصروفات
            'expenses_list' => $expensesList,
            'tanks_status' => $tanksStatus,
        ]);
    }

    /**
     * تقرير نموذج حركة المبيعات اليومية (ميزان المراجعة)
     * يطابق الصورة المطلوبة: نوع الوقود، أول المدة، آخر المدة، المباع، المستلم، والفوارق
     */
    public function dailyFuelReconciliation(Request $request)
    {
        // التحقق من الصلاحية (نفس صلاحية التقارير)
        abort_if(!auth()->user()->can('reports.view'), 403, 'غير مصرح لك بعرض هذا التقرير');

        $request->validate([
            'date' => 'nullable|date',
        ]);

        $date = $request->date ? Carbon::parse($request->date)->startOfDay() : now()->startOfDay();
        $endOfDay = $date->copy()->endOfDay();

        // جلب جميع أنواع الوقود مع خزاناتها
        $fuelTypes = \App\Models\FuelType::with('tanks')->get();
        $reportData = [];

        // متغيرات للإجماليات السفلية (كما في الصورة)
        $totalTotals = [
            'opening_stock' => 0,
            'received' => 0,
            'sold' => 0,
            'closing_stock' => 0,
            'variance_liters' => 0,
            'variance_money' => 0,
        ];

        foreach ($fuelTypes as $fuel) {
            $tankIds = $fuel->tanks->pluck('id')->toArray();
            if (empty($tankIds)) continue;

            // 1. مخزون أول المدة (آخر رصيد مسجل قبل بداية هذا اليوم)
            $openingStock = 0;
            foreach ($tankIds as $tankId) {
                $lastMoveBefore = \App\Models\StockMovement::where('tank_id', $tankId)
                    ->where('created_at', '<', $date)
                    ->latest('created_at')
                    ->first();
                $openingStock += $lastMoveBefore ? $lastMoveBefore->balance_after : 0;
            }

            // 2. الكمية المستلمة (التوريدات خلال هذا اليوم)
            $received = \App\Models\SupplyLog::whereIn('tank_id', $tankIds)
                ->whereBetween('created_at', [$date, $endOfDay])
                ->sum('quantity');

            // 3. الكمية المباعة (من الورديات المكتملة في هذا اليوم)
            $sold = 0;
            $assignments = \App\Models\Assignment::whereHas('pump', function($q) use ($tankIds) {
                    $q->whereIn('tank_id', $tankIds);
                })
                ->where('status', 'completed')
                ->whereBetween('end_at', [$date, $endOfDay])
                ->get();

            foreach ($assignments as $assignment) {
                $sold += ($assignment->end_counter_1 - $assignment->start_counter_1) +
                         ($assignment->end_counter_2 - $assignment->start_counter_2);
            }

            // تحديد سعر اللتر لحساب الفارق المالي (نأخذه من المبيعات إن وجدت، أو من سعر الوقود الافتراضي)
            $unitPrice = $assignments->first() ? $assignments->first()->unit_price : ($fuel->price ?? 0);

            // 4. مخزون آخر المدة الفعلي (الرصيد في نهاية اليوم)
            $closingStock = 0;
            foreach ($tankIds as $tankId) {
                if ($date->isToday()) {
                    // إذا كان التقرير لليوم الحالي، نأخذ الرصيد اللحظي للخزان
                    $closingStock += \App\Models\Tank::find($tankId)->current_stock;
                } else {
                    // إذا كان ليوم سابق، نأخذ آخر حركة تمت في ذلك اليوم
                    $lastMoveDay = \App\Models\StockMovement::where('tank_id', $tankId)
                        ->where('created_at', '<=', $endOfDay)
                        ->latest('created_at')
                        ->first();
                    $closingStock += $lastMoveDay ? $lastMoveDay->balance_after : 0;
                }
            }

            // 5. العمليات الحسابية للفوارق
            // المخزون الدفتري المتوقع = (أول المدة + المستلم) - المباع
            $expectedStock = ($openingStock + $received) - $sold;

            // الفارق باللتر = المخزون الفعلي (آخر المدة) - المخزون المتوقع
            $varianceLiters = $closingStock - $expectedStock;

            // الفارق بالعملة (دينار/جنيه/إلخ) = الفارق باللتر × سعر اللتر
            $varianceMoney = $varianceLiters * $unitPrice;

            $reportData[] = [
                'fuel_name' => $fuel->name,
                'opening_stock' => (float) $openingStock,
                'closing_stock' => (float) $closingStock,
                'sold' => (float) $sold,
                'received' => (float) $received,
                'variance_liters' => (float) $varianceLiters,
                'variance_money' => (float) $varianceMoney,
                'unit_price' => (float) $unitPrice,
            ];

            // تجميع الإجماليات السفلية للجدول
            $totalTotals['opening_stock'] += $openingStock;
            $totalTotals['received'] += $received;
            $totalTotals['sold'] += $sold;
            $totalTotals['closing_stock'] += $closingStock;
            $totalTotals['variance_liters'] += $varianceLiters;
            $totalTotals['variance_money'] += $varianceMoney;
        }

        return response()->json([
            'date' => $date->toDateString(),
            'records' => $reportData,
            'totals' => $totalTotals
        ]);
    }



    /**
     * تقرير أرصدة الخزانات اللحظية (الكمية المفترض وجودها)
     */
    public function tanksStockSummary()
    {
        // التحقق من الصلاحية
        abort_if(!auth()->user()->can('reports.view'), 403, 'غير مصرح لك بعرض أرصدة الخزانات');

        // جلب الخزانات مع نوع الوقود وحساب النسب
        $tanks = \App\Models\Tank::with('fuelType')->get()->map(function ($tank) {
            $capacity = (float) $tank->capacity;
            $currentStock = (float) $tank->current_stock;
            $freeSpace = $capacity - $currentStock;
            $fillPercentage = $capacity > 0 ? round(($currentStock / $capacity) * 100, 2) : 0;

            return [
                'id' => $tank->id,
                'name' => $tank->name,
                'fuel_type' => $tank->fuelType->name ?? 'غير محدد',
                'capacity' => $capacity,
                'current_stock' => $currentStock,
                'free_space' => $freeSpace,
                'fill_percentage' => $fillPercentage,
            ];
        });

        // تجميع الإجماليات حسب نوع الوقود (مفيد جداً للمدير)
        $summaryByFuel = $tanks->groupBy('fuel_type')->map(function ($group) {
            return [
                'total_capacity' => $group->sum('capacity'),
                'total_stock' => $group->sum('current_stock'),
                'total_free_space' => $group->sum('free_space'),
            ];
        });

        return response()->json([
            'date' => now()->format('Y-m-d h:i A'),
            'tanks' => $tanks,
            'summary_by_fuel' => $summaryByFuel
        ]);
    }



    /**
     * تقرير تفاصيل الورديات والتكليفات
     */
    public function shiftDetails(Request $request)
    {
        abort_if(!auth()->user()->can('reports.view'), 403, 'غير مصرح لك بعرض هذا التقرير');

        $request->validate(['date' => 'nullable|date']);

        $date = $request->date ? \Carbon\Carbon::parse($request->date)->toDateString() : now()->toDateString();

        // 🛑 التعديل 1: استخدام أسماء العلاقات والأعمدة الصحيحة (assignments.user) و (start_at)
        $shifts = \App\Models\Shift::with([
            'assignments.user',
            'assignments.pump.tank.fuelType'
        ])
        ->whereDate('start_at', $date)
        ->orWhereDate('end_at', $date)
        ->orderBy('start_at', 'asc')
        ->get()
        ->map(function ($shift) {

            $shiftTotalLiters = 0;
            $shiftTotalAmount = 0;

            $assignments = $shift->assignments->map(function ($assignment) use (&$shiftTotalLiters, &$shiftTotalAmount) {

                // حساب اللترات من العدادين (مع تجنب القيم الفارغة)
                $liters1 = max(0, $assignment->end_counter_1 - $assignment->start_counter_1);
                $liters2 = max(0, $assignment->end_counter_2 - $assignment->start_counter_2);
                $totalLiters = $liters1 + $liters2;

                // المبلغ = اللترات * سعر الوحدة
                $totalAmount = $totalLiters * $assignment->unit_price;

                $shiftTotalLiters += $totalLiters;
                $shiftTotalAmount += $totalAmount;

                return [
                    'id' => $assignment->id,
                    // 🛑 التعديل 2: جلب اسم العامل من علاقة user
                    'worker_name' => $assignment->user->full_name ?? 'غير محدد',
                    'pump_name' => $assignment->pump->name ?? 'غير محدد',
                    'fuel_type' => $assignment->pump->tank->fuelType->name ?? 'غير محدد',
                    'start_counter_1' => $assignment->start_counter_1,
                    'end_counter_1' => $assignment->end_counter_1,
                    'start_counter_2' => $assignment->start_counter_2,
                    'end_counter_2' => $assignment->end_counter_2,
                    'total_liters' => $totalLiters,
                    'unit_price' => $assignment->unit_price,
                    'total_amount' => $totalAmount,
                    'status' => $assignment->status, // active, completed
                ];
            });

            return [
                'id' => $shift->id,
                // 🛑 التعديل 3: الاستفادة من الخاصية name المضافة في مودل Shift
                'shift_name' => $shift->name,
                'start_time' => $shift->start_at,
                'end_time' => $shift->end_at,
                'status' => $shift->status, // open, closed
                'total_liters' => $shiftTotalLiters,
                'total_amount' => $shiftTotalAmount,
                'assignments' => $assignments
            ];
        });

        return response()->json([
            'date' => $date,
            'shifts' => $shifts
        ]);
    }
    }
