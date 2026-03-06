<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VoucherResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            // تحويل رقم السند إلى نص (String) لضمان عدم فقدان أي أرقام في الجافاسكربت
            'voucher_no' => (string) $this->voucher_no,

            // 🛑 الاستفادة من الـ Enum: إرسال القيمة البرمجية والاسم المعرب
            'type' => $this->type->value,
            'type_ar' => $this->type->label(),

            'amount' => (float) $this->amount,
            'payment_method' => $this->payment_method,
            'payment_method_ar' => $this->payment_method === 'cash' ? 'نقدي' : 'بنكي',

            'description' => $this->description,
            'date' => $this->date ? $this->date->format('Y-m-d H:i:s') : null,

            // جلب بيانات الوردية (إذا تم طلبها أو تحميلها)
            'shift' => $this->whenLoaded('shift', function () {
                return [
                    'id' => $this->shift->id,
                    'status' => $this->shift->status,
                    // يمكنك استخدام الدالة التي أنشأتها سابقاً لجلب اسم الوردية بالعربي
                    'name' => $this->shift->name ?? 'وردية رقم ' . $this->shift->id,
                ];
            }),

            // جلب بيانات المستخدم الذي قام بإنشاء السند
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->full_name ?? $this->user->username,
                ];
            }),

            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
        ];
    }
}
