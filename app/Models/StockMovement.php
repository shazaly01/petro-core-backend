<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'tank_id',
        'type',
        'quantity',
        'balance_before',
        'balance_after',
        'trackable_id',
        'trackable_type',
        'user_id',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    // العلاقة السحرية المتعددة الأشكال (تربط السجل بالتوريد أو التكليف)
    public function trackable(): MorphTo
    {
        return $this->morphTo();
    }

    // الخزان المرتبط بالحركة
    public function tank(): BelongsTo
    {
        return $this->belongsTo(Tank::class);
    }

    // المستخدم أو المشرف الذي نفذ الحركة
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
