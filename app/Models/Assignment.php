<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Assignment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'shift_id',
        'user_id',
        'pump_id',          // ربطنا التكليف بالمضخة كاملة

        // التوقيتات
        'start_at',
        'end_at',

        // قراءات المسدس الأول
        'start_counter_1',
        'end_counter_1',

        // قراءات المسدس الثاني
        'start_counter_2',
        'end_counter_2',

        // التسعير والماليات
        'unit_price',       // سعر اللتر (موحد للمضخة لأنها من خزان واحد)
        'expected_amount',  // إجمالي المبلغ المحسوب برمجياً من العدادات
        'cash_amount',      // ما سلمه العامل كاش
        'bank_amount',      // ما سلمه العامل عبر البنك/الشبكة
        'difference',       // العجز (سالب) أو الزيادة (موجب)

        'status',           // active, completed
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'start_counter_1' => 'decimal:2',
        'end_counter_1' => 'decimal:2',
        'start_counter_2' => 'decimal:2',
        'end_counter_2' => 'decimal:2',
        'unit_price' => 'decimal:3',
        'expected_amount' => 'decimal:3',
        'cash_amount' => 'decimal:3',
        'bank_amount' => 'decimal:3',
        'difference' => 'decimal:3',
    ];

    /**
     * العلاقات
     */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pump(): BelongsTo
    {
        return $this->belongsTo(Pump::class);
    }
}
