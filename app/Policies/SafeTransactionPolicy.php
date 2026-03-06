<?php

namespace App\Policies;

use App\Models\SafeTransaction;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SafeTransactionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('safe_transaction.view');
    }

    public function view(User $user, SafeTransaction $safeTransaction): bool
    {
        return $user->hasPermissionTo('safe_transaction.view');
    }

    // 🛑 الحماية الصارمة: منع الإنشاء اليدوي (لأنها تنشأ آلياً عبر الكود)
    public function create(User $user): bool
    {
        return false;
    }

    // 🛑 منع التعديل نهائياً على أي حركة مالية مسجلة
    public function update(User $user, SafeTransaction $safeTransaction): bool
    {
        return false;
    }

    // 🛑 منع حذف الحركات المالية
    public function delete(User $user, SafeTransaction $safeTransaction): bool
    {
        return false;
    }

    public function restore(User $user, SafeTransaction $safeTransaction): bool
    {
        return false;
    }

    public function forceDelete(User $user, SafeTransaction $safeTransaction): bool
    {
        return false;
    }
}
