<?php

namespace App\Policies;

use App\Models\EmployeeAssignment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class EmployeeAssignmentPolicy
{
    /**
     * Determine if user can view any employee assignments
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view employee assignments');
    }

    /**
     * Determine if user can view a specific employee assignment
     */
    public function view(User $user, EmployeeAssignment $assignment): bool
    {
        return $user->can('view employee assignments');
    }

    /**
     * Determine if user can create employees assignments
     */
    public function create(User $user): bool
    {
        return $user->can('create employee assignments');
    }

    /**
     * Determine if user can update a employee assignment
     */
    public function update(User $user, EmployeeAssignment $assignment): bool
    {
        return $user->can('edit employee assignments');
    }

    /**
     * Determine if user can delete a employee assignment
     */
    public function delete(User $user, EmployeeAssignment $assignment): bool
    {
        return $user->can('delete employee assignments');
    }
}
