<?php

namespace App\Policies;

use App\Models\Tank;
use App\Models\User;

class TankPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('infrastructure.view');
    }

    public function view(User $user, Tank $tank): bool
    {
        return $user->can('infrastructure.view');
    }

    public function create(User $user): bool
    {
        return $user->can('infrastructure.create');
    }

    public function update(User $user, Tank $tank): bool
    {
        return $user->can('infrastructure.update');
    }

    public function delete(User $user, Tank $tank): bool
    {
        return $user->can('infrastructure.delete');
    }

    public function restore(User $user, Tank $tank): bool
    {
        return $user->can('infrastructure.delete');
    }

    public function forceDelete(User $user, Tank $tank): bool
    {
        return $user->can('infrastructure.delete');
    }
}
