<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Api\UserResource;

class AssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status, // active, completed

            // التواريخ
            'start_at' => $this->start_at->format('Y-m-d H:i'),
            'end_at' => $this->end_at ? $this->end_at->format('Y-m-d H:i') : null,

            // بيانات الموظف
            'user' => new UserResource($this->whenLoaded('user')),

            // بيانات المسدس
            'nozzle' => new NozzleResource($this->whenLoaded('nozzle')),

            // بيانات العدادات
            'start_counter' => (float) $this->start_counter,
            'end_counter' => (float) $this->end_counter,

            // الحسابات المالية (هذه القيم تكون مسجلة فعلياً بعد الإغلاق)
            'sold_liters' => (float) $this->sold_liters,
            'unit_price' => (float) $this->unit_price,
            'total_amount' => (float) $this->total_amount,

            // 🛑 الإضافة الجديدة: السعر الحالي (Live Price) للواجهة الأمامية
            // نستخدم السعر المحفوظ إذا كان التكليف مغلقاً، وإلا نجلب السعر المباشر من الوقود
            'current_live_price' => $this->status === 'completed'
                ? (float) $this->unit_price
                : (float) ($this->nozzle->tank->fuelType->current_price ?? 0),

            // إجمالي المدفوعات المسجلة
            'total_paid' => $this->transactions->sum('amount'),

            // المتبقي (العجز أو الفائض)
            'remaining_due' => (float) ($this->total_amount - $this->transactions->sum('amount')),

            'transactions' => TransactionResource::collection($this->whenLoaded('transactions')),

            // 🛑 (اختياري) يمكنك أيضاً تمرير بيانات الوردية إذا احتجتها في العرض
            'shift' => new ShiftResource($this->whenLoaded('shift')),
        ];
    }
}
