<?php

namespace App\Http\Requests\Shift;

use Illuminate\Foundation\Http\FormRequest;

class StoreShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // المشرف عادة هو المستخدم الحالي، لكن يمكن تحديده يدوياً
           // 'supervisor_id' => ['nullable', 'exists:users,id'],
            'start_at' => ['nullable', 'date'], // لو ترك فارغاً نستخدم الوقت الحالي
            'status' => ['in:open'], // يجب أن تبدأ مفتوحة
        ];
    }
}
