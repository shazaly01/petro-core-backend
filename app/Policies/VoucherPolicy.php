<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Voucher;
use Illuminate\Auth\Access\HandlesAuthorization;

class VoucherPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('voucher.view');
    }

    public function view(User $user, Voucher $voucher): bool
    {
        return $user->hasPermissionTo('voucher.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('voucher.create');
    }

    public function update(User $user, Voucher $voucher): bool
    {
        return $user->hasPermissionTo('voucher.update');
    }

    public function delete(User $user, Voucher $voucher): bool
    {
        return $user->hasPermissionTo('voucher.delete');
    }

    public function restore(User $user, Voucher $voucher): bool
    {
        return $user->hasPermissionTo('voucher.delete');
    }

    public function forceDelete(User $user, Voucher $voucher): bool
    {
        // يفضل دائماً إعطاء صلاحية الحذف النهائي للمدير فقط (Admin)
        return $user->hasRole('Super Admin') || $user->hasRole('Admin');
    }
}
