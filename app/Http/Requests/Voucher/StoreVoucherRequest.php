<?php

namespace App\Http\Requests\Voucher;

use App\Enums\VoucherType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVoucherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // الصلاحيات سنتحكم بها عبر الـ Policy
    }

    public function rules(): array
    {
        return [
            // التحقق من أن النوع ينتمي حصراً للـ Enum الخاص بنا
            'type' => ['required', Rule::enum(VoucherType::class)],

            // المبلغ يجب أن يكون رقماً وأكبر من الصفر
            'amount' => ['required', 'numeric', 'min:0.01'],

            'payment_method' => ['nullable', 'string', 'in:cash,bank'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'type' => 'نوع السند',
            'amount' => 'المبلغ',
            'payment_method' => 'طريقة الدفع',
            'description' => 'البيان',
        ];
    }
}
