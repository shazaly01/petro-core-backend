<?php

namespace App\Http\Requests\FuelType;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFuelTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                // استثناء الـ ID الحالي من فحص التكرار
                Rule::unique('fuel_types', 'name')->ignore($this->route('fuel_type'))
            ],
            'current_price' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
