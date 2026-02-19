<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplyLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'quantity' => (float) $this->quantity,
            'cost_price' => (float) $this->cost_price,

            // بيانات الشاحنة
            'driver_name' => $this->driver_name,
            'truck_plate_number' => $this->truck_plate_number,
            'invoice_number' => $this->invoice_number,

            // القراءات قبل وبعد
            'stock_before' => (float) $this->stock_before,
            'stock_after' => (float) $this->stock_after,

            'created_at' => $this->created_at->format('Y-m-d H:i'),

            // الخزان والمشرف
            'tank' => new TankResource($this->whenLoaded('tank')),
            'supervisor' => $this->whenLoaded('supervisor', function () {
                return $this->supervisor->name;
            }),
        ];
    }
}
