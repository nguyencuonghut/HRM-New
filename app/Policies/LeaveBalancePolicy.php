<?php

namespace App\Policies;

use App\Models\LeaveBalance;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class LeaveBalancePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view leave balances');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, LeaveBalance $leaveBalance): bool
    {
        return $user->hasPermissionTo('view leave balances');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('adjust leave balances');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, LeaveBalance $leaveBalance): bool
    {
        return $user->hasPermissionTo('adjust leave balances');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, LeaveBalance $leaveBalance): bool
    {
        return $user->hasPermissionTo('adjust leave balances');
    }

    /**
     * Determine whether the user can adjust leave balances.
     */
    public function adjust(User $user, ?LeaveBalance $leaveBalance = null): bool
    {
        return $user->hasPermissionTo('adjust leave balances');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, LeaveBalance $leaveBalance): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, LeaveBalance $leaveBalance): bool
    {
        return false;
    }
}
