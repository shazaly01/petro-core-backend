<?php

namespace App\Http\Requests\Assignment;

use Illuminate\Foundation\Http\FormRequest;

class StoreAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'exists:users,id'], // العامل
            'pump_id' => ['required', 'exists:pumps,id'], // المضخة (بدلاً من المسدس)
            'start_at' => ['nullable', 'date'],

            // ملاحظة: قراءات البداية سيتم سحبها تلقائياً من جدول المضخات في الـ Controller،
            // ولكن نضعها هنا تحسباً لإرسالها من الواجهة للقراءة فقط.
            'start_counter_1' => ['nullable', 'numeric', 'min:0'],
            'start_counter_2' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
