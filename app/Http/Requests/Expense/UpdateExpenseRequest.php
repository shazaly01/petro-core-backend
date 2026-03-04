<?php

namespace App\Http\Requests\Expense;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['sometimes', 'required', 'numeric', 'min:0.001'],
            'spent_at' => ['sometimes', 'required', 'date'],
            'payment_method' => ['sometimes', 'required', 'string', 'in:cash,bank'],
            'description' => ['sometimes', 'required', 'string', 'max:1000'],
            'shift_id' => ['sometimes', 'required', 'integer', 'exists:shifts,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'لا يمكن ترك المبلغ فارغاً.',
            'description.required' => 'البيان مطلوب لتوثيق التعديل.',
        ];
    }
}
