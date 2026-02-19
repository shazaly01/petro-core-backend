<?php

namespace App\Http\Requests\SupplyLog;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSupplyLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tank_id' => ['sometimes', 'integer', 'exists:tanks,id'],
            'quantity' => ['sometimes', 'numeric', 'min:1'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'driver_name' => ['nullable', 'string', 'max:255'],
            'truck_plate_number' => ['nullable', 'string', 'max:50'],
            'invoice_number' => ['nullable', 'string', 'max:100'],
            'stock_before' => ['nullable', 'numeric', 'min:0'],
            'stock_after' => ['nullable', 'numeric', 'gte:stock_before'],
        ];
    }
}
