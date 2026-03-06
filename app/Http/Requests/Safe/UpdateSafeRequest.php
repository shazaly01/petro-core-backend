<?php

namespace App\Http\Requests\Safe;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSafeRequest extends FormRequest
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
        // جلب مُعرف (ID) الخزينة من الرابط الحالي (Route parameter)
        // قد يكون كائناً (Model instance) أو مجرد رقم حسب إعدادات الـ Route
        $safe = $this->route('safe');
        $safeId = is_object($safe) ? $safe->id : $safe;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                // التحقق من فرادة الاسم مع استثناء الخزينة الحالية التي يتم تعديلها
                Rule::unique('safes', 'name')->ignore($safeId),
            ],
        ];
    }

    /**
     * Get custom attributes for validator errors.
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
            'name.unique' => 'اسم الخزينة هذا مستخدم لخزينة أخرى، يرجى اختيار اسم آخر.',
        ];
    }
}
