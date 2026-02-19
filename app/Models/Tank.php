<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tank extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'fuel_type_id',
        'name',
        'code',
        'capacity',
        'current_stock',
        'alert_threshold',
    ];

    /**
     * العلاقات
     */

    // الخزان يتبع نوع وقود واحد
    public function fuelType(): BelongsTo
    {
        return $this->belongsTo(FuelType::class);
    }

    // الخزان يغذي عدة مسدسات
    public function nozzles(): HasMany
    {
        return $this->hasMany(Nozzle::class);
    }

    // سجلات التعبئة (التوريد) لهذا الخزان
    public function supplyLogs(): HasMany
    {
        return $this->hasMany(SupplyLog::class);
    }
}
