<?php

namespace App\Policies;

use App\Models\Nozzle;
use App\Models\User;

class NozzlePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('infrastructure.view');
    }

    public function view(User $user, Nozzle $nozzle): bool
    {
        return $user->can('infrastructure.view');
    }

    public function create(User $user): bool
    {
        return $user->can('infrastructure.create');
    }

    public function update(User $user, Nozzle $nozzle): bool
    {
        return $user->can('infrastructure.update');
    }

    public function delete(User $user, Nozzle $nozzle): bool
    {
        return $user->can('infrastructure.delete');
    }
}
