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
            'code' => $this->code,
            'current_counter' => (float) $this->current_counter,
            'is_active' => (bool) $this->is_active,

            // معلومات الخزان المرتبط (لمعرفة المخزون المتبقي)
            'tank_id' => $this->tank_id,
            'tank' => new TankResource($this->whenLoaded('tank')),

            // **مهم جداً:** نوع الوقود الذي يضخه هذا المسدس
            // نجلبه عبر العلاقة مع الخزان
            'fuel_type' => $this->tank && $this->tank->fuelType
                ? $this->tank->fuelType->name
                : 'غير محدد',
        ];
    }
}
