<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryAdjustmentResource extends JsonResource
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

            // بيانات الخزان (تظهر فقط إذا تم تحميل العلاقة لتسريع الأداء)
            'tank_id' => $this->tank_id,
            'tank_name' => $this->whenLoaded('tank', fn() => $this->tank->name),
            'fuel_type' => $this->whenLoaded('tank', fn() => $this->tank->fuelType?->name ?? 'غير محدد'),

            // بيانات المشرف الذي قام بالجرد
            'user_id' => $this->user_id,
            'supervisor_name' => $this->whenLoaded('user', fn() => $this->user->full_name ?? 'غير معروف'),

            // الأرصدة (محولة إلى أرقام عشرية لضمان توافقها مع الواجهة)
            'system_stock' => (float) $this->system_stock,
            'actual_stock' => (float) $this->actual_stock,
            'difference' => (float) $this->difference,

            // حقل إضافي ذكي لمساعدة الواجهة الأمامية في التلوين (أحمر للعجز، أخضر للزيادة)
            'type_label' => $this->difference > 0 ? 'زيادة في المخزون (+)' : ($this->difference < 0 ? 'عجز في المخزون (-)' : 'مطابق'),
            'is_deficit' => $this->difference < 0,

            // السبب
            'reason' => $this->reason ?? 'لم يُكتب سبب',

            // التواريخ بتنسيق مقروء للبشر وتنسيق دقيق
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d h:i A') : null,
            'created_at_human' => $this->created_at ? $this->created_at->diffForHumans() : null,
        ];
    }
}
