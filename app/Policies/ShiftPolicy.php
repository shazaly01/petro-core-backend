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
        // التعديل مسموح إذا كان لديه صلاحية الإغلاق
        // أو إذا كان هو المشرف صاحب الوردية (بشرط أن تكون الوردية لا تزال مفتوحة)
        if ($shift->status === 'closed') {
             // المشرف العام فقط يمكنه تعديل وردية مغلقة (للتصحيح)
             return $user->hasRole('Admin') || $user->hasRole('Super Admin');
        }

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
