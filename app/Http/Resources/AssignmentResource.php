<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Api\UserResource;

class AssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // حساب اللترات المباعة برمجياً للعرض في الواجهة (حتى قبل الإغلاق إذا توفرت بيانات النهاية)
        $soldLiters1 = $this->end_counter_1 ? ($this->end_counter_1 - $this->start_counter_1) : 0;
        $soldLiters2 = $this->end_counter_2 ? ($this->end_counter_2 - $this->start_counter_2) : 0;
        $totalSoldLiters = $soldLiters1 + $soldLiters2;

        return [
            'id' => $this->id,
            'status' => $this->status, // active, completed

            'start_at' => $this->start_at ? $this->start_at->format('Y-m-d H:i') : null,
            'end_at' => $this->end_at ? $this->end_at->format('Y-m-d H:i') : null,

            'user' => new UserResource($this->whenLoaded('user')),

            // 🛑 المضخة بدلاً من المسدس
            'pump' => new PumpResource($this->whenLoaded('pump')),

            // 🛑 قراءات المسدس الأول
            'start_counter_1' => (float) $this->start_counter_1,
            'end_counter_1' => $this->end_counter_1 !== null ? (float) $this->end_counter_1 : null,
            'sold_liters_1' => (float) $soldLiters1, // لترات المسدس الأول

            // 🛑 قراءات المسدس الثاني
            'start_counter_2' => (float) $this->start_counter_2,
            'end_counter_2' => $this->end_counter_2 !== null ? (float) $this->end_counter_2 : null,
            'sold_liters_2' => (float) $soldLiters2, // لترات المسدس الثاني

            'total_sold_liters' => (float) $totalSoldLiters, // إجمالي اللترات المباعة

            // 🛑 الحسابات المالية الصريحة
            'unit_price' => (float) $this->unit_price,
            'expected_amount' => $this->expected_amount !== null ? (float) $this->expected_amount : null,
            'cash_amount' => $this->cash_amount !== null ? (float) $this->cash_amount : null,
            'bank_amount' => $this->bank_amount !== null ? (float) $this->bank_amount : null,
            'difference' => $this->difference !== null ? (float) $this->difference : null,

            // 🛑 تحديث مسار جلب السعر المباشر (من المضخة -> الخزان -> الوقود)
            'current_live_price' => $this->status === 'completed'
                ? (float) $this->unit_price
                : (float) ($this->pump->tank->fuelType->current_price ?? 0),

            'shift' => new ShiftResource($this->whenLoaded('shift')),

            // 🛑 تم حذف معاملات transactions و total_paid و remaining_due
        ];
    }
}
