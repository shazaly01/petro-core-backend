<?php

namespace App\Policies;

use App\Models\Pump;
use App\Models\User;

class PumpPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('infrastructure.view');
    }

    public function view(User $user, Pump $pump): bool
    {
        return $user->can('infrastructure.view');
    }

    public function create(User $user): bool
    {
        return $user->can('infrastructure.create');
    }

    public function update(User $user, Pump $pump): bool
    {
        return $user->can('infrastructure.update');
    }

    public function delete(User $user, Pump $pump): bool
    {
        return $user->can('infrastructure.delete');
    }
}
