<?php

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;

class TransactionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('transaction.view');
    }

    public function view(User $user, Transaction $transaction): bool
    {
        return $user->can('transaction.view');
    }

    /**
     * تسجيل عملية دفع جديدة
     */
    public function create(User $user): bool
    {
        // العامل والمشرف يمكنهم تسجيل الدفع
        return $user->can('transaction.create');
    }

    /**
     * تعديل عملية دفع (تصحيح مبلغ خطأ)
     */
    public function update(User $user, Transaction $transaction): bool
    {
        return $user->can('transaction.update');
    }

    /**
     * حذف عملية دفع
     */
    public function delete(User $user, Transaction $transaction): bool
    {
        return $user->can('transaction.delete');
    }
}
