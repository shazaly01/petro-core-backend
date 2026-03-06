<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SafeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'balance' => (float) $this->balance, // إرجاع الرصيد كرقم عشري
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),

            // إذا أردنا جلب الحركات مع الخزينة (اختياري حسب الحاجة)
            'transactions' => SafeTransactionResource::collection($this->whenLoaded('transactions')),
        ];
    }
}
