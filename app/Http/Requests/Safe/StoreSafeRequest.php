<?php

namespace App\Http\Requests\Safe;

use Illuminate\Foundation\Http\FormRequest;

class StoreSafeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // نُرجع true لأننا سنعتمد على SafePolicy داخل المتحكم (Controller)
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:safes,name'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     * (لتخصيص أسماء الحقول في رسائل الخطأ لتكون بالعربية إذا لزم الأمر)
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'اسم الخزينة',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'اسم الخزينة هذا مسجل مسبقاً، يرجى اختيار اسم آخر.',
        ];
    }
}
