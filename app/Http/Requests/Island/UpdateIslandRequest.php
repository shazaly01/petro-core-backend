<?php

namespace App\Http\Requests\Island;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIslandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('islands', 'code')->ignore($this->route('island'))
            ],
            'is_active' => ['boolean'],
        ];
    }
}
