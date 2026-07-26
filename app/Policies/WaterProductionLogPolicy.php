<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WaterProductionLog;

class WaterProductionLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isStaff();
    }

    public function view(User $user, WaterProductionLog $waterProductionLog): bool
    {
        return $user->isAdmin() || $user->isStaff();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isStaff();
    }

    public function delete(User $user, WaterProductionLog $waterProductionLog): bool
    {
        return $user->isAdmin();
    }
}
