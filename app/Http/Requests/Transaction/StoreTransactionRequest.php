<?php

namespace App\Http\Requests\Transaction;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // يجب ربط الدفع بتكليف نشط (لنعرف من العامل المسؤول)
            'assignment_id' => ['required', 'exists:assignments,id'],

            'amount' => ['required', 'numeric', 'min:0.1'], // المبلغ
            'payment_method' => ['required', 'string', 'in:cash,visa,sadad,cheque'], // طرق الدفع المسموحة
            'reference_number' => ['nullable', 'string', 'max:100'], // رقم الإيصال
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
