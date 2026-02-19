<?php

namespace App\Http\Requests\FuelType;

use Illuminate\Foundation\Http\FormRequest;

class StoreFuelTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // الصلاحيات تدار عبر Policies
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:fuel_types,name'],
            'current_price' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
