<?php

namespace App\Policies;

use App\Models\Consumable;
use App\Models\User;

class ConsumablePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Consumable $consumable): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Consumable $consumable): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Consumable $consumable): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, Consumable $consumable): bool
    {
        return $user->isAdmin();
    }

    public function restock(User $user): bool
    {
        return $user->isAdmin();
    }
}
