<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PumpResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'island_id' => $this->island_id,
            'tank_id' => $this->tank_id, // 🛑 إضافة معرف الخزان المباشر

            'name' => $this->name,
            'code' => $this->code, // سيتم إرساله كما هو من الداتابيز DECIMAL(18,0)
            'model' => $this->model,

            // 🛑 إضافة قراءات العدادات الحالية للواجهة
            'current_counter_1' => (float) $this->current_counter_1,
            'current_counter_2' => (float) $this->current_counter_2,

            'is_active' => (bool) $this->is_active,
            'notes' => $this->notes,

            'island' => $this->whenLoaded('island', function () {
                return $this->island->name;
            }),

            // 🛑 إضافة اسم الخزان للواجهة (اختياري ومفيد)
            'tank' => $this->whenLoaded('tank', function () {
                return $this->tank->name ?? null;
            }),

            // 🛑 تم حذف 'nozzles' نهائياً
        ];
    }
}
