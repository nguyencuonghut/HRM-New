<?php

namespace App\Policies;

use App\Models\School;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SchoolPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view schools');
    }

    public function view(User $user, School $school): bool
    {
        return $user->hasPermissionTo('view schools');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create schools');
    }

    public function update(User $user, School $school): bool
    {
        return $user->hasPermissionTo('edit schools');
    }

    public function delete(User $user, School $school): bool
    {
        return $user->hasPermissionTo('delete schools');
    }

    public function bulkDelete(User $user): bool
    {
        return $user->hasPermissionTo('delete schools');
    }
}
