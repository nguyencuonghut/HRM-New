<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Contract;
use Illuminate\Auth\Access\HandlesAuthorization;

class ContractPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool    { return $user->hasPermissionTo('view contracts'); }
    public function view(User $user, Contract $c): bool { return $user->hasPermissionTo('view contracts'); }
    public function create(User $user): bool     { return $user->hasPermissionTo('create contracts'); }
    public function update(User $user, Contract $c): bool { return $user->hasPermissionTo('edit contracts'); }
    public function delete(User $user, Contract $c): bool { return $user->hasPermissionTo('delete contracts'); }
    public function bulkDelete(User $user): bool { return $user->hasPermissionTo('delete contracts'); }
    public function approve(User $user, Contract $c): bool{ return $user->hasPermissionTo('approve contracts'); }
}
