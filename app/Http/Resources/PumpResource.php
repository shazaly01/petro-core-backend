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
            'name' => $this->name,
            'code' => $this->code,
            'model' => $this->model,
            'is_active' => (bool) $this->is_active,
            'notes' => $this->notes,

            // نعرض اسم الجزيرة التي تقع عليها المضخة
            'island' => $this->whenLoaded('island', function () {
                return $this->island->name;
            }),

            // نعرض المسدسات الموجودة في هذه المضخة (مهم جداً للواجهة)
            'nozzles' => NozzleResource::collection($this->whenLoaded('nozzles')),
        ];
    }
}
