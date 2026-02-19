<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'assignment_id',
        'amount',
        'payment_method', // cash, visa, sadad, etc.
        'reference_number', // رقم الإيصال
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:3',
    ];

    /**
     * العلاقات
     */

    // تابعة لتكليف معين (لنعرف من العامل المسؤول وقتها)
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }
}
