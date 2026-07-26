<?php

namespace App\Policies;

use App\Models\GallonStock;
use App\Models\User;

class GallonStockPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isStaff();
    }

    public function view(User $user, GallonStock $gallonStock): bool
    {
        return $user->isAdmin() || $user->isStaff();
    }

    public function addStock(User $user): bool
    {
        return $user->isAdmin() || $user->isStaff();
    }

    public function adjust(User $user): bool
    {
        return $user->isAdmin();
    }
}
