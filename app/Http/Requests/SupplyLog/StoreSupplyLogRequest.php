<?php

namespace App\Http\Requests\SupplyLog;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupplyLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tank_id' => ['required', 'integer', 'exists:tanks,id'],
            'quantity' => ['required', 'numeric', 'min:1'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],

            // تم تغيير القواعد هنا لتصبح إجبارية (required)
            'driver_name' => ['required', 'string', 'max:255'],
            'truck_plate_number' => ['required', 'string', 'max:50'],

            'invoice_number' => ['nullable', 'string', 'max:100'],
            'stock_before' => ['nullable', 'numeric', 'min:0'],
            'stock_after' => ['nullable', 'numeric', 'gte:stock_before'],
        ];
    }

    /**
     * تخصيص رسائل الخطأ لتظهر بشكل واضح للمستخدم
     */
    public function messages(): array
    {
        return [
            'driver_name.required' => 'يرجى إدخال اسم السائق، هذا الحقل مطلوب.',
            'truck_plate_number.required' => 'يرجى إدخال رقم لوحة الشاحنة، هذا الحقل مطلوب.',
        ];
    }
}
