<?php

namespace App\Policies;

use App\Models\InsuranceMonthlyReport;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class InsuranceMonthlyReportPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view insurance reports');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, InsuranceMonthlyReport $insuranceReport): bool
    {
        return $user->hasPermissionTo('view insurance reports');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create insurance reports');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, InsuranceMonthlyReport $insuranceReport): bool
    {
        return $user->hasPermissionTo('finalize insurance reports');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, InsuranceMonthlyReport $insuranceReport): bool
    {
        return $user->hasPermissionTo('delete insurance reports');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, InsuranceMonthlyReport $insuranceReport): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, InsuranceMonthlyReport $insuranceReport): bool
    {
        return false;
    }
}
