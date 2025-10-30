<?php

namespace App\Policies;

use App\Models\Department;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DepartmentPolicy
{
    /**
     * Determine if user can view any departments
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view departments');
    }

    /**
     * Determine if user can view a specific department
     */
    public function view(User $user, Department $department): bool
    {
        return $user->can('view departments');
    }

    /**
     * Determine if user can create departments
     */
    public function create(User $user): bool
    {
        return $user->can('create departments');
    }

    /**
     * Determine if user can update a department
     */
    public function update(User $user, Department $department): bool
    {
        return $user->can('edit departments');
    }

    /**
     * Determine if user can delete a department
     */
    public function delete(User $user, Department $department): bool
    {
        return $user->can('delete departments');
    }

    /**
     * Determine if user can bulk delete departments
     */
    public function bulkDelete(User $user): bool
    {
        return $user->can('delete departments');
    }

    /**
     * Determine if user can reorder departments
     */
    public function reorder(User $user): bool
    {
        return $user->can('edit departments');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Department $department): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Department $department): bool
    {
        return false;
    }
}
