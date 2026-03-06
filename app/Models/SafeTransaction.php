<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SafeTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_no',
        'safe_id',
        'user_id',
        'shift_id',
        'type',
        'amount',
        'balance_after',
        'transactionable_type',
        'transactionable_id',
        'description',
    ];

    protected $casts = [
        'transaction_no' => 'decimal:0',
        'amount' => 'decimal:3',
        'balance_after' => 'decimal:3',
    ];

    public function safe(): BelongsTo
    {
        return $this->belongsTo(Safe::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // العلاقة المتعددة (قد تجلب لك Assignment أو Expense)
    public function transactionable(): MorphTo
    {
        return $this->morphTo();
    }
}
