<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pump extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'island_id',
        'tank_id',           // تم إضافة الخزان هنا مباشرة
        'name',
        'code',              // الكود سيكون رقمي DECIMAL(18,0)
        'model',
        'current_counter_1', // العداد التراكمي للمسدس الأول
        'current_counter_2', // العداد التراكمي للمسدس الثاني
        'is_active',
        'notes',
    ];

    protected $casts = [
        'code' => 'decimal:0', // ليتوافق مع الأكواد الطويلة
        'current_counter_1' => 'decimal:2',
        'current_counter_2' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * العلاقات
     */
    public function island(): BelongsTo
    {
        return $this->belongsTo(Island::class);
    }

    public function tank(): BelongsTo
    {
        return $this->belongsTo(Tank::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }
}
