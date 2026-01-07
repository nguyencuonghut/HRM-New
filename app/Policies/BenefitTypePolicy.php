<?php

namespace App\Policies;

use App\Models\BenefitType;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class BenefitTypePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view benefits');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, BenefitType $benefitType): bool
    {
        return $user->hasPermissionTo('view benefits');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage benefits');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, BenefitType $benefitType): bool
    {
        return $user->hasPermissionTo('manage benefits');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, BenefitType $benefitType): bool
    {
        return $user->hasPermissionTo('manage benefits');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, BenefitType $benefitType): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, BenefitType $benefitType): bool
    {
        return false;
    }
}
