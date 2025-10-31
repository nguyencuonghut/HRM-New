<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Role;
use Illuminate\Auth\Access\HandlesAuthorization;

class RolePolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view any roles
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view roles');
    }

    /**
     * Determine if user can view a specific role
     */
    public function view(User $user, Role $role): bool
    {
        return $user->can('view roles');
    }

    /**
     * Determine if user can create roles
     */
    public function create(User $user): bool
    {
        return $user->can('create roles');
    }

    /**
     * Determine if user can update a role
     */
    public function update(User $user, Role $role): bool
    {
        return $user->can('edit roles');
    }

    /**
     * Determine if user can delete a role
     */
    public function delete(User $user, Role $role): bool
    {
        // Cannot delete Super Admin role
        return $user->can('delete roles') && $role->name !== 'Super Admin';
    }

    /**
     * Determine if user can bulk delete roles
     */
    public function bulkDelete(User $user): bool
    {
        return $user->can('delete roles');
    }

    /**
     * Determine if user can assign permissions to roles
     */
    public function assignPermissions(User $user, Role $role): bool
    {
        return $user->can('edit roles');
    }
}
