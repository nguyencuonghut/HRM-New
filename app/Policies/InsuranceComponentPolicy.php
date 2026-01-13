<?php

namespace App\Policies;

use App\Models\InsuranceComponent;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class InsuranceComponentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('manage insurance components');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, InsuranceComponent $component): bool
    {
        return $user->hasPermissionTo('manage insurance components');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, InsuranceComponent $component): bool
    {
        return $user->hasPermissionTo('manage insurance components');
    }

    /**
     * Determine whether the user can manage insurance components.
     */
    public function manage(User $user): bool
    {
        return $user->hasPermissionTo('manage insurance components');
    }
}
