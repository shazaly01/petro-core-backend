<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
{
    /**
     * تحويل المصروف إلى مصفوفة قابلة للإرسال عبر الـ API.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            // المبلغ مفرمط لـ 3 خانات عشرية
            'amount' => (float) $this->amount,

            // التاريخ والوقت بتنسيق مقروء
            'spent_at' => $this->spent_at->format('Y-m-d H:i'),
            'spent_at_date' => $this->spent_at->format('Y-m-d'),

            // طريقة الدفع والبيان
            'payment_method' => $this->payment_method,
            'payment_method_label' => $this->payment_method === 'cash' ? 'نقداً' : 'مصرف',
            'description' => $this->description,

            // بيانات الوردية المرتبطة
            'shift_id' => $this->shift_id,
            'shift_name' => $this->whenLoaded('shift', function() {
                return $this->shift->name; // يستخدم الـ Accessor الذي عرفناه في موديل Shift
            }),

            // بيانات المستخدم الذي سجل المصروف
            'user_id' => $this->user_id,
            'user_name' => $this->whenLoaded('user', function() {
                return $this->user->full_name;
            }),

            'created_at' => $this->created_at->format('Y-m-d H:i'),
        ];
    }
}
