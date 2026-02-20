<?php

namespace App\Http\Requests\Pump;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePumpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'island_id' => ['required', 'integer', 'exists:islands,id'],
            'tank_id' => ['required', 'integer', 'exists:tanks,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'nullable',
                'numeric',
                Rule::unique('pumps', 'code')->ignore($this->route('pump'))
            ],
            'model' => ['nullable', 'string', 'max:100'],
            'current_counter_1' => ['required', 'numeric', 'min:0'],
            'current_counter_2' => ['required', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
