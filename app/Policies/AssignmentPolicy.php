<?php

namespace App\Policies;

use App\Models\Assignment;
use App\Models\User;

class AssignmentPolicy
{
    /**
     * رؤية سجل التكليفات
     */
    public function viewAny(User $user): bool
    {
        return $user->can('assignment.view');
    }

    public function view(User $user, Assignment $assignment): bool
    {
        return $user->can('assignment.view');
    }

    /**
     * إنشاء تكليف جديد (توزيع عامل على مضخة)
     */
    public function create(User $user): bool
    {
        return $user->can('assignment.create');
    }

    /**
     * تعديل التكليف (مثلاً: إغلاق التكليف يدوياً أو تصحيح العداد)
     */
    public function update(User $user, Assignment $assignment): bool
    {
        // يمكن للمشرف تعديل التكليف ما دام مفتوحاً
        if ($assignment->status === 'active') {
            return $user->can('assignment.update');
        }

        // التكليفات المغلقة تتطلب صلاحية أعلى (مثل Admin) لتعديلها
        return $user->hasRole('Admin') || $user->hasRole('Super Admin');
    }

    /**
     * حذف التكليف (خطير جداً)
     */
    public function delete(User $user, Assignment $assignment): bool
    {
        return $user->can('assignment.delete');
    }
}
