<?php

namespace App\Policies;

use App\Models\EducationLevel;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EducationLevelPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view education levels');
    }

    public function view(User $user, EducationLevel $level): bool
    {
        return $user->hasPermissionTo('view education levels');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create education levels');
    }

    public function update(User $user, EducationLevel $level): bool
    {
        return $user->hasPermissionTo('edit education levels');
    }

    public function delete(User $user, EducationLevel $level): bool
    {
        return $user->hasPermissionTo('delete education levels');
    }

    public function bulkDelete(User $user): bool
    {
        return $user->hasPermissionTo('delete education levels');
    }
}
