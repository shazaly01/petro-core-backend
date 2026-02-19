<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Nozzle extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'pump_id',
        'tank_id',
        'code',
        'current_counter', // قراءة العداد الحالية (تتحدث بعد كل إغلاق)
        'is_active',
    ];

    /**
     * العلاقات
     */

    // المسدس جزء من مضخة
    public function pump(): BelongsTo
    {
        return $this->belongsTo(Pump::class);
    }

    // المسدس يسحب من خزان معين (ومن هنا نعرف نوع الوقود والكمية المتبقية)
    public function tank(): BelongsTo
    {
        return $this->belongsTo(Tank::class);
    }

    // المسدس عليه سجل تكليفات (تاريخ من عمل عليه)
    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }
}
