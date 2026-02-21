<?php

namespace App\Http\Requests\InventoryAdjustment;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInventoryAdjustmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        // في حالة التعديل (الذي سيقوم به المدير فقط)، نجعل الحقول اختيارية (sometimes)
        // بحيث لو أرسل حقلاً واحداً فقط للتعديل يتم قبوله، ولكن بشروط صارمة.
        return [
            'tank_id' => ['sometimes', 'required', 'integer', 'exists:tanks,id'],
            'actual_stock' => ['sometimes', 'required', 'numeric', 'min:0'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'tank_id.required' => 'يرجى تحديد الخزان.',
            'tank_id.exists' => 'الخزان المحدد غير موجود.',
            'actual_stock.required' => 'يجب إدخال الرصيد الفعلي.',
            'actual_stock.numeric' => 'الرصيد الفعلي يجب أن يكون رقماً.',
            'actual_stock.min' => 'الرصيد الفعلي لا يمكن أن يكون أقل من الصفر.',
        ];
    }
}
