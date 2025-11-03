<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Ward;
use Illuminate\Auth\Access\HandlesAuthorization;

class WardPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view wards');
    }

    public function view(User $user, Ward $ward): bool
    {
        return $user->hasPermissionTo('view wards');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create wards');
    }

    public function update(User $user, Ward $ward): bool
    {
        return $user->hasPermissionTo('edit wards');
    }

    public function delete(User $user, Ward $ward): bool
    {
        return $user->hasPermissionTo('delete wards');
    }

    public function bulkDelete(User $user): bool
    {
        return $user->hasPermissionTo('delete wards');
    }
}
