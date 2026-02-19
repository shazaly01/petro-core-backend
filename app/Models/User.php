<?php

namespace App\Models;

// Imports...
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, HasRoles, HasApiTokens;

    protected $guard_name = 'api';

    protected $fillable = [
        'full_name',
        'username',
        'email',
        'password',
        // أضف أي حقول أخرى تحتاجها مثل 'phone', 'is_active'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // --- العلاقات الجديدة ---

    // 1. الورديات التي قام هذا المستخدم بفتحها (كمشرف)
    public function supervisedShifts(): HasMany
    {
        return $this->hasMany(Shift::class, 'supervisor_id');
    }

    // 2. التكليفات التي عمل بها (كعامل مضخة)
    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class, 'user_id');
    }

    // 3. عمليات التوريد التي سجلها (تعبئة الخزانات)
    public function supplyLogs(): HasMany
    {
        return $this->hasMany(SupplyLog::class, 'supervisor_id');
    }
}
