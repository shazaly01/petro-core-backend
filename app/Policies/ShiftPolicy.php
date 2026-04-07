<?php

namespace App\Policies;

use App\Models\Shift;
use App\Models\User;

class ShiftPolicy
{
    /**
     * من يمكنه رؤية قائمة الورديات؟
     */
    public function viewAny(User $user): bool
    {
        return $user->can('shift.view');
    }

    /**
     * من يمكنه رؤية تفاصيل وردية معينة؟
     */
    public function view(User $user, Shift $shift): bool
    {
        return $user->can('shift.view');
    }

    /**
     * من يمكنه فتح وردية جديدة؟
     */
    public function create(User $user): bool
    {
        // يجب أن لا يكون لدى المستخدم وردية مفتوحة بالفعل (يمكن إضافة هذا الشرط هنا أو في الـ Controller)
        return $user->can('shift.create');
    }

    /**
     * من يمكنه تعديل الوردية (الإغلاق وتسجيل العجز)؟
     */
public function update(User $user, Shift $shift): bool
    {
        // 1. إذا كانت الوردية مغلقة، المشرف العام فقط يمكنه التعديل
        if ($shift->status === 'closed') {
             return $user->hasRole('Admin') || $user->hasRole('Super Admin');
        }

        // 🛑 2. الحل الجذري: إذا كان المستخدم الحالي هو نفسه صاحب الوردية المفتوحة، اسمح له فوراً!
        if ($shift->supervisor_id === $user->id) {
            return true;
        }

        // 3. كإجراء احتياطي، نتحقق من الصلاحية العامة
        return $user->can('shift.close');
    }

    /**
     * حذف الوردية (عملية خطرة نادراً ما تحدث)
     */
    public function delete(User $user, Shift $shift): bool
    {
        return $user->can('shift.delete');
    }
}
