<?php

namespace App\Models;

use App\Enums\VoucherType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Voucher extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'voucher_no',
        'shift_id',
        'user_id',
        'type',
        'amount',
        'payment_method',
        'description',
        'date',
    ];

    protected $casts = [
        // 🛑 السحر هنا: لارافل سيتعامل مع هذا الحقل كـ Enum تلقائياً
        'type' => VoucherType::class,

        'amount' => 'decimal:3',
        'voucher_no' => 'decimal:0', // كما تفضل دائماً لأرقام السندات
        'date' => 'datetime',
    ];

    /**
     * العلاقة مع الوردية
     */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * العلاقة مع المستخدم (منشئ السند)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ربط السند بحركة الخزينة التي نتجت عنه (دفتر الأستاذ)
     */
    public function safeTransaction(): MorphOne
    {
        return $this->morphOne(SafeTransaction::class, 'transactionable');
    }
}
