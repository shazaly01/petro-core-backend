<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class InventoryAdjustment extends Model
{
    use HasFactory;

    protected $fillable = [
        'tank_id',
        'user_id',
        'system_stock',
        'actual_stock',
        'difference',
        'reason',
    ];

    protected $casts = [
        'system_stock' => 'decimal:2',
        'actual_stock' => 'decimal:2',
        'difference' => 'decimal:2',
    ];

    // جلب الخزان
    public function tank(): BelongsTo
    {
        return $this->belongsTo(Tank::class);
    }

    // جلب المشرف الذي قام بالجرد
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // 🛑 العلاقة السحرية: جلب حركة المخزون الناتجة عن هذه التسوية
    public function stockMovement(): MorphOne
    {
        return $this->morphOne(StockMovement::class, 'trackable');
    }
}
