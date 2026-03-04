<?php

namespace App\Http\Requests\Expense;

use Illuminate\Foundation\Http\FormRequest;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // المبلغ يجب أن يكون رقماً موجباً
            'amount' => ['required', 'numeric', 'min:0.001'],

            // تاريخ الصرف
            'spent_at' => ['required', 'date'],

            // طريقة الدفع (نقدى أو مصرف)
            'payment_method' => ['required', 'string', 'in:cash,bank'],

            // البيان أو الوصف إجباري لتوثيق المصرف
            'description' => ['required', 'string', 'max:1000'],

            // ملاحظة: shift_id سيتم جلبه برمجياً في Controller من الوردية المفتوحة
            // ولكن نضعه هنا في حال أردنا تمريره يدوياً من الواجهة
            'shift_id' => ['nullable', 'integer', 'exists:shifts,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'يرجى إدخال مبلغ المصروف.',
            'amount.numeric' => 'يجب أن يكون المبلغ رقماً.',
            'amount.min' => 'يجب أن يكون المبلغ أكبر من صفر.',
            'spent_at.required' => 'تاريخ الصرف مطلوب.',
            'payment_method.required' => 'يرجى تحديد طريقة الدفع (نقدى/مصرف).',
            'payment_method.in' => 'طريقة الدفع المختارة غير صالحة.',
            'description.required' => 'يرجى كتابة بيان المصروف (الوصف).',
        ];
    }
}
