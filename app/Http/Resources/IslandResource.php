<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IslandResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'is_active' => (bool) $this->is_active,

            // عند جلب الجزيرة، غالباً نريد رؤية المضخات التي عليها
            // نستخدم PumpResource (سننشئه لاحقاً) أو نعرض البيانات الخام مؤقتاً
            // هنا استخدمت whenLoaded لتجنب تحميل البيانات إذا لم نطلبها
            'pumps' => PumpResource::collection($this->whenLoaded('pumps')),

            'created_at' => $this->created_at->format('Y-m-d'),
        ];
    }
}
