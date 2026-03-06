<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'shift_id',
        'user_id',
        'amount',
        'spent_at',
        'payment_method',
        'category',
        'description',
    ];

    protected $casts = [
        'spent_at' => 'datetime',
        'amount' => 'decimal:3',
    ];

    /**
     * العلاقة مع الوردية الكلية
     */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * العلاقة مع المستخدم (الذي أدخل المصروف)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
