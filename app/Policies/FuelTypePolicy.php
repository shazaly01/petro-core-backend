<?php

namespace App\Policies;

use App\Models\FuelType;
use App\Models\User;

class FuelTypePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('infrastructure.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, FuelType $fuelType): bool
    {
        return $user->can('infrastructure.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('infrastructure.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, FuelType $fuelType): bool
    {
        return $user->can('infrastructure.update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, FuelType $fuelType): bool
    {
        return $user->can('infrastructure.delete');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, FuelType $fuelType): bool
    {
        return $user->can('infrastructure.delete');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, FuelType $fuelType): bool
    {
        // الحذف النهائي يتطلب صلاحية الحذف (أو يمكن تخصيص صلاحية super admin فقط)
        return $user->can('infrastructure.delete');
    }
}
