<?php

namespace App\Http\Requests\Nozzle;

use Illuminate\Foundation\Http\FormRequest;

class StoreNozzleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pump_id' => ['required', 'integer', 'exists:pumps,id'], // تابع لأي مضخة؟
            'tank_id' => ['required', 'integer', 'exists:tanks,id'], // يسحب من أي خزان؟

            // الكود قد يكون رقم المسدس (1، 2، 3...)
            // يفضل أن يكون فريداً لتسهيل البحث
            'code' => ['required', 'string', 'max:50', 'unique:nozzles,code'],

            // قراءة العداد الابتدائية عند تركيب النظام
            // لا يمكن أن تكون سالبة
            'current_counter' => ['required', 'numeric', 'min:0'],

            'is_active' => ['boolean'],
        ];
    }
}
