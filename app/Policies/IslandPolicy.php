<?php

namespace App\Policies;

use App\Models\Island;
use App\Models\User;

class IslandPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('infrastructure.view');
    }

    public function view(User $user, Island $island): bool
    {
        return $user->can('infrastructure.view');
    }

    public function create(User $user): bool
    {
        return $user->can('infrastructure.create');
    }

    public function update(User $user, Island $island): bool
    {
        return $user->can('infrastructure.update');
    }

    public function delete(User $user, Island $island): bool
    {
        return $user->can('infrastructure.delete');
    }

    public function restore(User $user, Island $island): bool
    {
        return $user->can('infrastructure.delete');
    }

    public function forceDelete(User $user, Island $island): bool
    {
        return $user->can('infrastructure.delete');
    }
}
