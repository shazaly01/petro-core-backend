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
        'tank_id',
        'name',
        'code',
        'model',
        'current_counter_1',
        'current_counter_2',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'code' => 'decimal:0', // تم الحفاظ على الدقة العشرية للأكواد الطويلة
        'current_counter_1' => 'decimal:2',
        'current_counter_2' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // 🛑 التعديل الأول: إضافة الحقل الوهمي ليظهر دائماً في الـ JSON
    protected $appends = ['is_busy'];

    /**
     * 🛑 التعديل الثاني: دالة الوصول (Accessor) لمعرفة هل المضخة مشغولة
     * تعيد true إذا كان هناك تكليف نشط مرتبط بها
     */
    public function getIsBusyAttribute(): bool
    {
        return $this->assignments()->where('status', 'active')->exists();
    }

    /**
     * 🛑 التعديل الثالث: Scope لجلب المضخات المتاحة فقط (التي ليس لها تكليف نشط)
     */
    public function scopeAvailable($query)
    {
        return $query->whereDoesntHave('assignments', function ($q) {
            $q->where('status', 'active');
        });
    }

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
