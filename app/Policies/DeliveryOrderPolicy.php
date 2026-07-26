<?php

namespace App\Policies;

use App\Models\DeliveryOrder;
use App\Models\User;

class DeliveryOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, DeliveryOrder $deliveryOrder): bool
    {
        if ($user->isAdmin() || $user->isStaff()) {
            return true;
        }

        return $user->id === $deliveryOrder->customer_id;
    }

    public function create(User $user): bool
    {
        return $user->isCustomer();
    }

    public function manage(User $user): bool
    {
        return $user->isAdmin() || $user->isStaff();
    }

    public function cancel(User $user, DeliveryOrder $deliveryOrder): bool
    {
        return $user->id === $deliveryOrder->customer_id
            && in_array($deliveryOrder->status, ['pending', 'confirmed'], true);
    }
}
