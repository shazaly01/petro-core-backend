<?php

namespace App\Http\Requests\Tank;

use Illuminate\Foundation\Http\FormRequest;

class StoreTankRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fuel_type_id' => ['required', 'exists:fuel_types,id'],
            'name' => ['required', 'string', 'max:255'], // مثال: خزان 1
            'code' => ['nullable', 'string', 'max:50', 'unique:tanks,code'],
            'capacity' => ['required', 'numeric', 'min:1'], // السعة الكلية
            'current_stock' => ['required', 'numeric', 'min:0', 'lte:capacity'], // المخزون الحالي (أقل من أو يساوي السعة)
            'alert_threshold' => ['nullable', 'numeric', 'min:0'], // حد التنبيه
        ];
    }
}
