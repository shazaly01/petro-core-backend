<?php

namespace App\Http\Requests\Tank;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTankRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fuel_type_id' => ['required', 'exists:fuel_types,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('tanks', 'code')->ignore($this->route('tank'))
            ],
            'capacity' => ['required', 'numeric', 'min:1'],
            // نسمح بتعديل المخزون يدوياً فقط للمدير (عادة التعديل يكون عبر التوريد/البيع)
            'current_stock' => ['required', 'numeric', 'min:0', 'lte:capacity'],
            'alert_threshold' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
