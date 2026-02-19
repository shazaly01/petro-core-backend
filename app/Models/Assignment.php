<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assignment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'shift_id',
        'user_id',
        'nozzle_id',
        'start_at',
        'end_at',
        'start_counter',
        'end_counter',
        'sold_liters',
        'unit_price',
        'total_amount',
        'status', // active, completed
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'start_counter' => 'decimal:2',
        'end_counter' => 'decimal:2',
        'sold_liters' => 'decimal:2',
        'unit_price' => 'decimal:3',
        'total_amount' => 'decimal:3',
    ];

    /**
     * العلاقات
     */

    // تابع لأي وردية عامة؟
    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    // من هو العامل المسؤول؟
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // عن أي مسدس مسؤول؟
    public function nozzle(): BelongsTo
    {
        return $this->belongsTo(Nozzle::class);
    }

    // المدفوعات المسجلة على هذا التكليف (إلكتروني أو دفعات نقدية مرحلية)
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
