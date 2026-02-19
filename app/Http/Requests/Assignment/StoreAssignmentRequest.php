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
            // 🛑 تم حذف shift_id لأنه سيتم تحديده تلقائياً في المتحكم

            'user_id' => ['required', 'exists:users,id'], // العامل (تأتي من WorkersDropdown)

            // المسدس: يجب أن يكون موجوداً
            'nozzle_id' => ['required', 'exists:nozzles,id'],

            'start_at' => ['nullable', 'date'],

            // قراءة البداية
            'start_counter' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
