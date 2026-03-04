<?php

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;

class ExpensePolicy
{
    /**
     * تحديد ما إذا كان المستخدم يمكنه عرض قائمة المصروفات.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('expense.view');
    }

    /**
     * تحديد ما إذا كان المستخدم يمكنه عرض مصروف محدد.
     */
    public function view(User $user, Expense $expense): bool
    {
        return $user->can('expense.view');
    }

    /**
     * تحديد ما إذا كان المستخدم يمكنه إضافة مصروف جديد.
     */
    public function create(User $user): bool
    {
        return $user->can('expense.create');
    }

    /**
     * تحديد ما إذا كان المستخدم يمكنه تحديث مصروف.
     */
    public function update(User $user, Expense $expense): bool
    {
        return $user->can('expense.update');
    }

    /**
     * تحديد ما إذا كان المستخدم يمكنه حذف مصروف.
     */
    public function delete(User $user, Expense $expense): bool
    {
        return $user->can('expense.delete');
    }
}
