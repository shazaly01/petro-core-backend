<?php

namespace App\Http\Requests\Pump;

use Illuminate\Foundation\Http\FormRequest;

class StorePumpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'island_id' => ['required', 'integer', 'exists:islands,id'], // يجب أن تتبع جزيرة موجودة
            'name' => ['required', 'string', 'max:255'], // مثال: مضخة 1
            'code' => ['nullable', 'string', 'max:50', 'unique:pumps,code'], // كود فريد
            'model' => ['nullable', 'string', 'max:100'], // موديل الماكينة
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
