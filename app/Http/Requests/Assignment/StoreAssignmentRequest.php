<?php

namespace App\Http\Requests\Assignment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shift_id' => ['required', 'exists:shifts,id'], // تابع للوردية المفتوحة حالياً
            'user_id' => ['required', 'exists:users,id'], // العامل

            // المسدس: يجب أن يكون موجوداً وغير محجوز حالياً (اختياري: يمكن إضافة rule مخصص لعدم التكرار)
            'nozzle_id' => ['required', 'exists:nozzles,id'],

            'start_at' => ['nullable', 'date'],

            // قراءة البداية: عادة يأخذها النظام من المسدس، لكن يمكن تمريرها للتأكيد
            'start_counter' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
