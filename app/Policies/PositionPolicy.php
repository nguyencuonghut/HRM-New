<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Position;
use Illuminate\Auth\Access\HandlesAuthorization;

class PositionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can view any positions.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view positions');
    }

    /**
     * Determine if the user can view the position.
     */
    public function view(User $user, Position $position): bool
    {
        return $user->hasPermissionTo('view positions');
    }

    /**
     * Determine if the user can create positions.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create positions');
    }

    /**
     * Determine if the user can update the position.
     */
    public function update(User $user, Position $position): bool
    {
        return $user->hasPermissionTo('edit positions');
    }

    /**
     * Determine if the user can delete the position.
     */
    public function delete(User $user, Position $position): bool
    {
        return $user->hasPermissionTo('delete positions');
    }

    /**
     * Determine if the user can bulk delete positions.
     */
    public function bulkDelete(User $user): bool
    {
        return $user->hasPermissionTo('delete positions');
    }
}
