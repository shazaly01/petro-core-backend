<?php

namespace App\Policies;

use App\Models\Safe;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SafePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('safe.view');
    }

    public function view(User $user, Safe $safe): bool
    {
        return $user->hasPermissionTo('safe.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('safe.create');
    }

    public function update(User $user, Safe $safe): bool
    {
        return $user->hasPermissionTo('safe.update');
    }

    public function delete(User $user, Safe $safe): bool
    {
        return $user->hasPermissionTo('safe.delete');
    }

    /**
     * دالة مخصصة للتحقق من صلاحية سحب/تصفير الخزينة
     */
    public function withdraw(User $user, Safe $safe): bool
    {
        return $user->hasPermissionTo('safe.withdraw');
    }
}
