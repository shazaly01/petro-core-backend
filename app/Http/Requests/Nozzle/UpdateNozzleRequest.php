<?php

namespace App\Http\Requests\Nozzle;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateNozzleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pump_id' => ['required', 'integer', 'exists:pumps,id'],
            'tank_id' => ['required', 'integer', 'exists:tanks,id'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('nozzles,code')->ignore($this->route('nozzle'))
            ],
            // السماح بتعديل العداد يدوياً (للمدراء فقط لتصحيح الأخطاء)
            'current_counter' => ['required', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }
}
