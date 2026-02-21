<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StockMovement;
use App\Models\Tank;
use App\Models\Assignment;
use App\Models\SupplyLog;
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
     * تقرير الحركة اليومية (ملخص المبيعات، التوريدات، والمالية ليوم محدد)
     */
    public function dailyMovement(Request $request)
    {
        $request->validate([
            'date' => 'nullable|date',
        ]);

        // تحديد اليوم المطلوب (أو اليوم الحالي افتراضياً)
        $date = $request->date ? Carbon::parse($request->date)->startOfDay() : now()->startOfDay();
        $endDate = $date->copy()->endOfDay();

        // 1. ملخص المبيعات والمالية (من التكليفات المكتملة في هذا اليوم)
        $assignments = Assignment::with(['pump.tank.fuelType'])
            ->where('status', 'completed')
            ->whereBetween('end_at', [$date, $endDate])
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

        // 2. ملخص التوريدات (الوقود الوارد للمحطة في هذا اليوم)
        $supplies = SupplyLog::with(['tank.fuelType'])
            ->whereBetween('created_at', [$date, $endDate])
            ->get();

        $suppliesByFuel = [];
        foreach ($supplies as $supply) {
            $fuelName = $supply->tank->fuelType->name ?? 'غير محدد';
            if (!isset($suppliesByFuel[$fuelName])) {
                $suppliesByFuel[$fuelName] = 0;
            }
            $suppliesByFuel[$fuelName] += $supply->quantity;
        }

        // 3. حالة الخزانات اللحظية (كميات الوقود المتوفرة الآن)
        $tanksStatus = Tank::with('fuelType')->get()->map(function ($tank) {
            return [
                'id' => $tank->id,
                'name' => $tank->name,
                'fuel_type' => $tank->fuelType->name ?? 'غير محدد',
                'capacity' => (float) $tank->capacity,
                'current_stock' => (float) $tank->current_stock,
                // نسبة الامتلاء لتسهيل رسمها بيانياً في الواجهة
                'fill_percentage' => $tank->capacity > 0 ? round(($tank->current_stock / $tank->capacity) * 100, 2) : 0,
            ];
        });

        return response()->json([
            'date' => $date->toDateString(),
            'financial_summary' => [
                'total_expected' => (float) $totalExpectedAmount,
                'total_cash' => (float) $totalCash,
                'total_bank' => (float) $totalBank,
                'total_difference' => (float) $totalDifference,
            ],
            'sales_by_fuel' => $salesByFuel,
            'supplies_by_fuel' => $suppliesByFuel,
            'tanks_status' => $tanksStatus,
        ]);
    }

    private function translateType($type)
    {
        $types = [
            'in' => 'توريد وقود (+)',
            'out' => 'مبيعات وردية (-)',
            'adjustment' => 'تسوية جردية (+/-)'
        ];
        return $types[$type] ?? $type;
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
}
