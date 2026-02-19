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
        'name',
        'code',
        'model',
        'is_active',
        'notes',
    ];

    /**
     * العلاقات
     */

    // المضخة تتبع جزيرة معينة
    public function island(): BelongsTo
    {
        return $this->belongsTo(Island::class);
    }

    // المضخة تحتوي على عدة مسدسات
    public function nozzles(): HasMany
    {
        return $this->hasMany(Nozzle::class);
    }
}
