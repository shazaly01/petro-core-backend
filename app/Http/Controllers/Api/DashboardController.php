<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Shift;
use App\Models\Tank;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function stats()
    {
        $today = Carbon::today();

        // 1. ملخص المبيعات (نحسب فقط التكليفات المغلقة والمنتهية اليوم)
        $salesToday = Assignment::whereDate('updated_at', '>=', $today)
            ->where('status', 'completed')
            ->selectRaw('
                SUM(expected_amount) as total_expected,
                SUM(cash_amount) as total_cash,
                SUM(bank_amount) as total_bank,
                SUM(difference) as total_difference,
                SUM((COALESCE(end_counter_1, start_counter_1) - start_counter_1) + (COALESCE(end_counter_2, start_counter_2) - start_counter_2)) as total_liters
            ')
            ->first();

        $totalCash = (float) ($salesToday->total_cash ?? 0);
        $totalBank = (float) ($salesToday->total_bank ?? 0);
        $totalExpected = (float) ($salesToday->total_expected ?? 0);
        $totalCollected = $totalCash + $totalBank;
        $totalDifference = (float) ($salesToday->total_difference ?? 0);
        $totalLiters = (float) ($salesToday->total_liters ?? 0);

        // 2. حالة المخزون الحي
        $inventory = Tank::with('fuelType:id,name')
            ->get(['id', 'name', 'fuel_type_id', 'current_stock', 'capacity', 'alert_threshold'])
            ->map(function ($tank) {
                $percentage = $tank->capacity > 0 ? round(($tank->current_stock / $tank->capacity) * 100, 2) : 0;
                return [
                    'id' => $tank->id,
                    'name' => $tank->name,
                    'fuel_type' => $tank->fuelType?->name ?? 'غير محدد',
                    'stock_level' => (float) $tank->current_stock,
                    'capacity' => (float) $tank->capacity,
                    'percentage' => $percentage,
                    'is_low' => $tank->current_stock <= $tank->alert_threshold,
                ];
            });

        // 3. أداء العمال (للتكليفات المغلقة اليوم فقط)
        // 🛑 التعديل هنا: استخدام full_name بدلاً من name
        $topWorkers = Assignment::whereDate('updated_at', '>=', $today)
            ->where('status', 'completed')
            ->with('user:id,full_name')
            ->select('user_id',
                DB::raw('SUM(cash_amount + bank_amount) as total_collected'),
                DB::raw('SUM(difference) as total_diff')
            )
            ->groupBy('user_id')
            ->orderByDesc('total_collected')
            ->take(5)
            ->get();

        // 4. الورديات المفتوحة حالياً
        // 🛑 التعديل هنا: استخدام full_name بدلاً من name
        $activeShifts = Shift::where('status', 'open')
            ->with('supervisor:id,full_name')
            ->withCount(['assignments as active_assignments_count' => function ($query) {
                $query->where('status', 'active');
            }])
            ->get()
            ->map(function ($shift) {
                return [
                    'id' => $shift->id,
                    // 🛑 التعديل هنا: استدعاء full_name
                    'supervisor' => $shift->supervisor?->full_name ?? 'مشرف غير معروف',
                    'start_at' => $shift->start_at ? Carbon::parse($shift->start_at)->diffForHumans() : 'الآن',
                    'active_assignments' => $shift->active_assignments_count
                ];
            });

        return response()->json([
            'overview' => [
                'total_expected_revenue' => $totalExpected,
                'total_collected_revenue' => $totalCollected,
                'total_cash' => $totalCash,
                'total_bank' => $totalBank,
                'total_difference' => $totalDifference,
                'total_liters_today' => $totalLiters,
                'is_deficit' => $totalDifference < 0,
            ],
            'active_shifts' => $activeShifts,
            'inventory' => $inventory,
            'payment_breakdown' => [
                ['method' => 'نقدي (Cash)', 'total' => $totalCash],
                ['method' => 'شبكة (Bank)', 'total' => $totalBank],
            ],
            'top_workers' => $topWorkers->map(fn($w) => [
                // 🛑 التعديل هنا: استدعاء full_name
                'name' => $w->user?->full_name ?? 'عامل غير معروف',
                'collected' => (float) $w->total_collected,
                'difference' => (float) $w->total_diff,
            ]),
            'alerts' => [
                'low_stock_tanks' => $inventory->where('is_low', true)->values(),
                'deficits' => $totalDifference < 0 ? 'تنبيه: يوجد عجز مالي إجمالي في تكليفات اليوم، يرجى المراجعة.' : null,
            ]
        ]);
    }
}
