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

    // الإضافة الجديدة: إلحاق الحقل الوهمي ببيانات النموذج
    protected $appends = ['name'];

    /**
     * الإضافة الجديدة: دالة الوصول (Accessor) لتوليد اسم الوردية
     */
    public function getNameAttribute(): string
    {
        if (!$this->start_at) {
            return 'وردية غير محددة الوقت';
        }

        // استخدام locale('ar') لضمان أن اسم اليوم باللغة العربية دائماً (مثل: السبت، الأحد)
        $dayName = $this->start_at->locale('ar')->translatedFormat('l');
        $date = $this->start_at->format('Y-m-d');

        return "وردية يوم {$dayName} تاريخ {$date}";
    }

    /**
     * العلاقات
     */

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }
}
