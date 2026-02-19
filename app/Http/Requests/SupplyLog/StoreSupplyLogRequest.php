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
            // الخزان الذي تم تفريغ الشحنة فيه
            'tank_id' => ['required', 'integer', 'exists:tanks,id'],

            // الكمية المفرغة (يجب أن تكون قيمة موجبة)
            'quantity' => ['required', 'numeric', 'min:1'],

            // سعر الشراء/التكلفة (اختياري، للتقارير المالية)
            'cost_price' => ['nullable', 'numeric', 'min:0'],

            // بيانات الشاحنة والسائق (للتوثيق)
            'driver_name' => ['nullable', 'string', 'max:255'],
            'truck_plate_number' => ['nullable', 'string', 'max:50'],
            'invoice_number' => ['nullable', 'string', 'max:100'], // رقم فاتورة المصدر

            // قراءة المسطرة قبل وبعد (اختياري)
            'stock_before' => ['nullable', 'numeric', 'min:0'],
            'stock_after' => ['nullable', 'numeric', 'gte:stock_before'], // يجب أن يكون "بعد" أكبر من أو يساوي "قبل"
        ];
    }
}
