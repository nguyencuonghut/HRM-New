<?php

namespace App\Policies;

use App\Models\SkillCategory;
use App\Models\User;

class SkillCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view skills');
    }

    public function view(User $user, SkillCategory $skillCategory): bool
    {
        return $user->can('view skills');
    }

    public function create(User $user): bool
    {
        return $user->can('create skills');
    }

    public function update(User $user, SkillCategory $skillCategory): bool
    {
        return $user->can('edit skills');
    }

    public function delete(User $user, SkillCategory $skillCategory): bool
    {
        return $user->can('delete skills');
    }
}
