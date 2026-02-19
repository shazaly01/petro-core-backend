<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NozzleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            // [التعديل هنا] إضافة pump_id الذي كان مفقوداً
            'pump_id' => $this->pump_id,

            'code' => $this->code,
            'current_counter' => (float) $this->current_counter,
            'is_active' => (bool) $this->is_active,

            // معلومات الخزان المرتبط
            'tank_id' => $this->tank_id,
            'tank' => new TankResource($this->whenLoaded('tank')),

            // نوع الوقود وسعره
            'fuel_type' => $this->tank && $this->tank->fuelType
                ? $this->tank->fuelType->name
                : 'غير محدد',
            'fuel_price' => $this->tank && $this->tank->fuelType
                ? (float) $this->tank->fuelType->current_price
                : 0,
        ];
    }
    }
