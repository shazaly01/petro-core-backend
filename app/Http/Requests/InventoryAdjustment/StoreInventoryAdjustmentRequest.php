<?php

namespace App\Http\Requests\InventoryAdjustment;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryAdjustmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // تم تفعيل حارس الـ Policy في الـ Controller، لذا نتركه true هنا
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tank_id' => ['required', 'integer', 'exists:tanks,id'],

            // الرصيد الفعلي يجب أن يكون رقماً ولا يمكن أن يكون بالسالب
            'actual_stock' => ['required', 'numeric', 'min:0'],

            // السبب اختياري، ولكن إذا كُتب يجب ألا يتجاوز 255 حرفاً
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * تخصيص رسائل الخطأ باللغة العربية (اختياري لتحسين تجربة المستخدم)
     */
    public function messages(): array
    {
        return [
            'tank_id.required' => 'يرجى تحديد الخزان المراد جرده.',
            'tank_id.exists' => 'الخزان المحدد غير موجود في النظام.',
            'actual_stock.required' => 'يجب إدخال الرصيد الفعلي الموجود في الخزان.',
            'actual_stock.numeric' => 'الرصيد الفعلي يجب أن يكون رقماً.',
            'actual_stock.min' => 'الرصيد الفعلي لا يمكن أن يكون أقل من الصفر.',
            'reason.max' => 'سبب التسوية يجب ألا يتجاوز 255 حرفاً.',
        ];
    }
}
