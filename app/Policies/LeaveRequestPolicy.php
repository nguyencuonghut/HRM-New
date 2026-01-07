<?php

namespace App\Policies;

use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class LeaveRequestPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view leave requests');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, LeaveRequest $leaveRequest): bool
    {
        return $user->hasPermissionTo('view leave requests');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create leave requests');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, LeaveRequest $leaveRequest): bool
    {
        return $user->hasPermissionTo('edit leave requests');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, LeaveRequest $leaveRequest): bool
    {
        return $user->hasPermissionTo('delete leave requests');
    }

    /**
     * Determine whether the user can submit leave requests.
     */
    public function submit(User $user, LeaveRequest $leaveRequest): bool
    {
        return $user->hasPermissionTo('submit leave requests');
    }

    /**
     * Determine whether the user can approve leave requests.
     */
    public function approve(User $user, LeaveRequest $leaveRequest): bool
    {
        return $user->hasPermissionTo('approve leave requests');
    }

    /**
     * Determine whether the user can reject leave requests.
     */
    public function reject(User $user, LeaveRequest $leaveRequest): bool
    {
        return $user->hasPermissionTo('reject leave requests');
    }

    /**
     * Determine whether the user can cancel leave requests.
     */
    public function cancel(User $user, LeaveRequest $leaveRequest): bool
    {
        return $user->hasPermissionTo('cancel leave requests');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function bulkDelete(User $user): bool
    {
        return $user->hasPermissionTo('delete leave requests');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, LeaveRequest $leaveRequest): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, LeaveRequest $leaveRequest): bool
    {
        return false;
    }
}
