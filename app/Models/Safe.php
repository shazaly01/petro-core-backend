<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Safe extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'balance'];

    protected $casts = [
        'balance' => 'decimal:3',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(SafeTransaction::class);
    }
}
