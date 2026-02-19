<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FuelTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'current_price' => (float) $this->current_price, // تحويل الرقم لضمان التنسيق
            'description' => $this->description,

            // نعرض عدد الخزانات المرتبطة بهذا النوع (للمعلومات فقط)
            'tanks_count' => $this->whenCounted('tanks'),

            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
