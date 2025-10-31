<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class EmployeePolicy
{
    /**
     * Determine if user can view any employees
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view employees');
    }

    /**
     * Determine if user can view a specific employee
     */
    public function view(User $user, Employee $employee): bool
    {
        return $user->can('view employees');
    }

    /**
     * Determine if user can create employees
     */
    public function create(User $user): bool
    {
        return $user->can('create employees');
    }

    /**
     * Determine if user can update a employee
     */
    public function update(User $user, Employee $employee): bool
    {
        return $user->can('edit employees');
    }

    /**
     * Determine if user can delete a employee
     */
    public function delete(User $user, Employee $employee): bool
    {
        return $user->can('delete employees');
    }
}
