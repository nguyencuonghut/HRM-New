<?php

namespace App\Policies;

use App\Models\EmployeeExperience;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class EmployeeExperiencePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view employee profiles');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, EmployeeExperience $employeeExperience): bool
    {
        return $user->hasPermissionTo('view employee profiles');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('edit employee profiles');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, EmployeeExperience $employeeExperience): bool
    {
        return $user->hasPermissionTo('edit employee profiles');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, EmployeeExperience $employeeExperience): bool
    {
        return $user->hasPermissionTo('edit employee profiles');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, EmployeeExperience $employeeExperience): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, EmployeeExperience $employeeExperience): bool
    {
        return false;
    }
}
