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

            // قراءة العداد النهائية (مطلوبة عند الإغلاق)
            // gte = Greater Than or Equal (أكبر من أو يساوي)
            'end_counter' => ['required_if:status,completed', 'numeric', 'gte:start_counter'],

            'status' => ['in:active,completed'],
        ];
    }
}
