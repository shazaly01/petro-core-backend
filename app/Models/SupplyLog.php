<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplyLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tank_id',
        'supervisor_id',
        'quantity',
        'cost_price',
        'driver_name',
        'truck_plate_number',
        'invoice_number',
        'stock_before',
        'stock_after',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'cost_price' => 'decimal:3',
        'stock_before' => 'decimal:2',
        'stock_after' => 'decimal:2',
    ];

    /**
     * العلاقات
     */

    // أي خزان تمت تعبئته؟
    public function tank(): BelongsTo
    {
        return $this->belongsTo(Tank::class);
    }

    // المشرف الذي استلم الشحنة وسجلها
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }
}
