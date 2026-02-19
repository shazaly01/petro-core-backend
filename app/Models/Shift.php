<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'supervisor_id',
        'start_at',
        'end_at',
        'status', // open, closed
        'total_expected_cash',
        'total_actual_cash',
        'difference',
        'handover_notes',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'total_expected_cash' => 'decimal:3',
        'total_actual_cash' => 'decimal:3',
        'difference' => 'decimal:3',
    ];

    /**
     * العلاقات
     */

    // المشرف الذي فتح الوردية
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    // جميع التكليفات التي تمت خلال هذه الوردية
    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }
}
