<?php

namespace App\Policies;

use App\Models\InsuranceSalaryCategory;
use App\Models\User;

class InsuranceSalaryCategoryPolicy
{
    /**
     * Determine if user can view any insurance salary categories
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view departments'); // Reuse department permission
    }

    /**
     * Determine if user can view a specific insurance salary category
     */
    public function view(User $user, InsuranceSalaryCategory $insuranceInsuranceSalaryCategory): bool
    {
        return $user->can('view departments');
    }

    /**
     * Determine if user can create salary categories
     */
    public function create(User $user): bool
    {
        return $user->can('create departments');
    }

    /**
     * Determine if user can update a salary category
     */
    public function update(User $user, InsuranceSalaryCategory $insuranceSalaryCategory): bool
    {
        return $user->can('edit departments');
    }

    /**
     * Determine if user can delete a salary category
     */
    public function delete(User $user, InsuranceSalaryCategory $insuranceSalaryCategory): bool
    {
        return $user->can('delete departments');
    }

    /**
     * Determine if user can bulk delete salary categories
     */
    public function bulkDelete(User $user): bool
    {
        return $user->can('delete departments');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, InsuranceSalaryCategory $insuranceSalaryCategory): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, InsuranceSalaryCategory $insuranceSalaryCategory): bool
    {
        return false;
    }
}
