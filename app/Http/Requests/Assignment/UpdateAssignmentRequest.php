<?php

namespace App\Http\Requests\Assignment;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'end_at' => ['nullable', 'date', 'after:start_at'],
            'status' => ['in:active,completed'],

            // يجب إدخال قراءات النهاية إذا كانت حالة التكليف "مكتمل"، ويجب أن تكون أكبر أو تساوي قراءة البداية
            'end_counter_1' => ['required_if:status,completed', 'numeric', 'gte:start_counter_1'],
            'end_counter_2' => ['required_if:status,completed', 'numeric', 'gte:start_counter_2'],

            // المبالغ المالية المحصلة مطلوبة عند الإغلاق
            'cash_amount' => ['required_if:status,completed', 'numeric', 'min:0'],
            'bank_amount' => ['required_if:status,completed', 'numeric', 'min:0'],
        ];
    }
}
