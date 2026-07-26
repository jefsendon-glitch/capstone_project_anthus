<?php

namespace App\Policies;

use App\Models\MaintenanceLog;
use App\Models\User;

class MaintenanceLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isStaff();
    }

    public function view(User $user, MaintenanceLog $maintenanceLog): bool
    {
        return $user->isAdmin() || $user->isStaff();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, MaintenanceLog $maintenanceLog): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, MaintenanceLog $maintenanceLog): bool
    {
        return $user->isAdmin();
    }
}
