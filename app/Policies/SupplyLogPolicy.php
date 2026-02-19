<?php

namespace App\Policies;

use App\Models\SupplyLog;
use App\Models\User;

class SupplyLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('supply.view');
    }

    public function view(User $user, SupplyLog $supplyLog): bool
    {
        return $user->can('supply.view');
    }

    /**
     * تسجيل شحنة وقود واردة
     */
    public function create(User $user): bool
    {
        return $user->can('supply.create');
    }

    /**
     * تعديل بيانات شحنة (مثلاً خطأ في رقم الشاحنة)
     */
    public function update(User $user, SupplyLog $supplyLog): bool
    {
        return $user->can('supply.update');
    }

    /**
     * حذف سجل توريد
     */
    public function delete(User $user, SupplyLog $supplyLog): bool
    {
        return $user->can('supply.delete');
    }
}
