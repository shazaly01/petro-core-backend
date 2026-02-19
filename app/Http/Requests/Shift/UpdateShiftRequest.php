<?php

namespace App\Http\Requests\Shift;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // عند الإغلاق، يجب إرسال حالة "closed"
            'status' => ['sometimes', 'in:open,closed'],

            // وقت الإغلاق
            'end_at' => ['nullable', 'date', 'after:start_at'],

            // الكاش الفعلي الموجود في الدرج (يجب إدخاله عند الإغلاق)
            'total_actual_cash' => ['nullable', 'numeric', 'min:0'],

            // ملاحظات التسليم
            'handover_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
