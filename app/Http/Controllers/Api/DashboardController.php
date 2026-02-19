<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Shift;
use App\Models\Tank;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function stats()
    {
        // 1. ملخص المبيعات (اليوم)
        $today = Carbon::today();
        $salesToday = Assignment::whereDate('created_at', $today)
            ->selectRaw('SUM(total_amount) as total_money, SUM(sold_liters) as total_liters')
            ->first();

        // 2. حالة المخزون (الخزانات)
        $inventory = Tank::with('fuelType:id,name')
            ->get(['id', 'name', 'fuel_type_id', 'current_stock', 'capacity', 'alert_threshold'])
            ->map(function ($tank) {
                return [
                    'id' => $tank->id,
                    'name' => $tank->name,
                    'fuel_type' => $tank->fuelType->name,
                    'stock_level' => (float) $tank->current_stock,
                    'percentage' => round(($tank->current_stock / $tank->capacity) * 100, 2),
                    'is_low' => $tank->current_stock <= $tank->alert_threshold,
                ];
            });

        // 3. أداء العمال (أكثر 5 عمال مبيعاً اليوم)
        $topWorkers = Assignment::whereDate('created_at', $today)
            ->with('user:id,name')
            ->select('user_id', DB::raw('SUM(total_amount) as total_sales'))
            ->groupBy('user_id')
            ->orderByDesc('total_sales')
            ->take(5)
            ->get();

        // 4. طرق الدفع (كاش vs شبكة)
        $paymentMethods = Transaction::whereDate('created_at', $today)
            ->select('payment_method', DB::raw('SUM(amount) as total'))
            ->groupBy('payment_method')
            ->get();

        // 5. حالة الوردية الحالية
        $currentShift = Shift::where('status', 'open')
            ->with('supervisor:id,name')
            ->withCount('assignments')
            ->latest()
            ->first();

        return response()->json([
            'overview' => [
                'total_revenue_today' => (float) ($salesToday->total_money ?? 0),
                'total_liters_today' => (float) ($salesToday->total_liters ?? 0),
                'active_shift' => $currentShift ? [
                    'id' => $currentShift->id,
                    'supervisor' => $currentShift->supervisor->name,
                    'start_at' => $currentShift->start_at->diffForHumans(),
                    'assignments_count' => $currentShift->assignments_count
                ] : null,
            ],
            'inventory' => $inventory,
            'payment_breakdown' => $paymentMethods,
            'top_workers' => $topWorkers->map(fn($w) => [
                'name' => $w->user->name,
                'sales' => (float) $w->total_sales
            ]),
            'alerts' => [
                'low_stock_tanks' => $inventory->where('is_low', true)->values(),
            ]
        ]);
    }
}
