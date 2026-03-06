<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SafeTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            // تنسيق رقم الحركة كـ string لضمان عدم فقده لأي أرقام في الـ JS بما أنه DECIMAL(18,0)
            'transaction_no' => (string) $this->transaction_no,

            'type' => $this->type, // 'in' or 'out'
            'type_ar' => $this->type === 'in' ? 'وارد' : 'صادر', // ترجمة للواجهة

            'amount' => (float) $this->amount,
            'balance_after' => (float) $this->balance_after,
            'description' => $this->description,

            // بيانات من قام بالحركة
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->full_name ?? $this->user->username,
                ];
            }),

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
