<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FuelType extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * الحقول القابلة للتعبئة
     */
    protected $fillable = [
        'name',
        'current_price',
        'description',
    ];

    /**
     * العلاقات
     */

    // نوع الوقود يغذي عدة خزانات
    public function tanks(): HasMany
    {
        return $this->hasMany(Tank::class);
    }
}
