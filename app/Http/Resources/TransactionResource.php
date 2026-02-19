<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => (float) $this->amount,
            'payment_method' => $this->payment_method, // cash, visa...
            'reference_number' => $this->reference_number,
            'notes' => $this->notes,
            'created_at' => $this->created_at->format('Y-m-d H:i'),

            // التكليف المرتبط (لمعرفة من قام بالتحصيل)
            'assignment_id' => $this->assignment_id,
        ];
    }
}
