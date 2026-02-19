<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FuelType;
use App\Models\Assignment;
use App\Models\SupplyLog;
use App\Models\Tank;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function dailyMovement(Request $request)
    {
        $date = $request->query('date', Carbon::today()->toDateString());

        // جلب جميع أنواع الوقود مع الخزانات التابعة لها
        $fuelTypes = FuelType::with('tanks')->get();

        $report = $fuelTypes->map(function ($fuel) use ($date) {
            $tankIds = $fuel->tanks->pluck('id');

            // 1. الكمية المستلمة (من سجلات التوريد)
            $received = SupplyLog::whereIn('tank_id', $tankIds)
                ->whereDate('created_at', $date)
                ->sum('quantity');

            // 2. الكمية المباعة (من التكليفات)
            $sold = Assignment::whereHas('nozzle', function($q) use ($tankIds) {
                    $q->whereIn('tank_id', $tankIds);
                })
                ->whereDate('created_at', $date)
                ->where('status', 'completed')
                ->sum('sold_liters');

            // 3. حساب المخزون (بناءً على الحالة الحالية والعمليات)
            // ملاحظة: مخزون آخر المدة لهذا اليوم = المخزون الحالي - (أي توريد بعد هذا اليوم) + (أي بيع بعد هذا اليوم)
            // للتبسيط، سنفترض أن التقرير يُطلب لليوم الحالي:
            $closingStock = $fuel->tanks->sum('current_stock');
            $openingStock = $closingStock + $sold - $received;

            // 4. الفارق (حسابي حالياً، ويمكنك إضافة حقل "جرد يدوي" لاحقاً للمقارنة)
            $varianceLiters = 0; // ناتج الجرد الفعلي - الجرد الدفتري
            $varianceDinar = $varianceLiters * $fuel->current_price;

            return [
                'fuel_type' => $fuel->name,
                'opening_stock' => (float) $openingStock,
                'closing_stock' => (float) $closingStock,
                'quantity_sold' => (float) $sold,
                'quantity_received' => (float) $received,
                'variance_liters' => (float) $varianceLiters,
                'variance_dinar' => (float) $varianceDinar,
            ];
        });

        return response()->json([
            'date' => $date,
            'data' => $report,
            'totals' => [
                'sold' => $report->sum('quantity_sold'),
                'received' => $report->sum('quantity_received'),
                'variance_dinar' => $report->sum('variance_dinar'),
            ]
        ]);
    }
}
