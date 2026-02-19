<?php

namespace App\Http\Requests\Island;

use Illuminate\Foundation\Http\FormRequest;

class StoreIslandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', 'unique:islands,code'],
            'is_active' => ['boolean'],
        ];
    }
}
