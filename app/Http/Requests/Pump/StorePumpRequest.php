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
            'island_id' => ['required', 'integer', 'exists:islands,id'],
            'tank_id' => ['required', 'integer', 'exists:tanks,id'], // إضافة الخزان
            'name' => ['required', 'string', 'max:255'],

            // الكود رقمي ليتوافق مع نوع DECIMAL(18,0)
            'code' => ['nullable', 'numeric', 'unique:pumps,code'],

            'model' => ['nullable', 'string', 'max:100'],

            // العدادات الابتدائية للمسدسين عند تعريف المضخة لأول مرة
            'current_counter_1' => ['required', 'numeric', 'min:0'],
            'current_counter_2' => ['required', 'numeric', 'min:0'],

            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
