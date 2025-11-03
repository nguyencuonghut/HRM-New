<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Province;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProvincePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view provinces');
    }

    public function view(User $user, Province $province): bool
    {
        return $user->hasPermissionTo('view provinces');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create provinces');
    }

    public function update(User $user, Province $province): bool
    {
        return $user->hasPermissionTo('edit provinces');
    }

    public function delete(User $user, Province $province): bool
    {
        return $user->hasPermissionTo('delete provinces');
    }

    public function bulkDelete(User $user): bool
    {
        return $user->hasPermissionTo('delete provinces');
    }
}
