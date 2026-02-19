<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TankResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,

            // تفاصيل نوع الوقود (بدلاً من مجرد رقم ID)
            'fuel_type' => new FuelTypeResource($this->whenLoaded('fuelType')),

            'capacity' => (float) $this->capacity,
            'current_stock' => (float) $this->current_stock,
            'alert_threshold' => (float) $this->alert_threshold,

            // نسبة الامتلاء (مفيد جداً للواجهة الأمامية لرسم شريط التقدم)
            'fill_percentage' => $this->capacity > 0
                ? round(($this->current_stock / $this->capacity) * 100, 1)
                : 0,

            'created_at' => $this->created_at->format('Y-m-d'),
        ];
    }
}
