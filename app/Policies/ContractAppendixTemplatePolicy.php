<?php

namespace App\Policies;

use App\Models\ContractAppendixTemplate;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ContractAppendixTemplatePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view appendix templates');
    }

    public function view(User $user, ContractAppendixTemplate $template): bool
    {
        return $user->hasPermissionTo('view appendix templates');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create appendix templates');
    }

    public function update(User $user, ContractAppendixTemplate $template): bool
    {
        return $user->hasPermissionTo('edit appendix templates');
    }

    public function delete(User $user, ContractAppendixTemplate $template): bool
    {
        return $user->hasPermissionTo('delete appendix templates');
    }

    public function bulkDelete(User $user): bool
    {
        return $user->hasPermissionTo('delete appendix templates');
    }
}
