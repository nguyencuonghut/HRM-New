<?php

namespace App\Policies;

use App\Models\ContractTemplate;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ContractTemplatePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view contract templates');
    }

    public function view(User $user, ContractTemplate $template): bool
    {
        return $user->hasPermissionTo('view contract templates');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create contract templates');
    }

    public function update(User $user, ContractTemplate $template): bool
    {
        return $user->hasPermissionTo('edit contract templates');
    }

    public function delete(User $user, ContractTemplate $template): bool
    {
        return $user->hasPermissionTo('delete contract templates');
    }

    public function bulkDelete(User $user): bool
    {
        return $user->hasPermissionTo('delete contract templates');
    }
}
