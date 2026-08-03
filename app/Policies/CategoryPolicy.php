<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    private const VIEWERS = ['super_admin', 'administrator', 'editor'];

    private const MANAGERS = ['super_admin', 'administrator'];

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(self::VIEWERS);
    }

    public function view(User $user, Category $category): bool
    {
        return $user->hasAnyRole(self::VIEWERS);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(self::MANAGERS);
    }

    public function update(User $user, Category $category): bool
    {
        return $user->hasAnyRole(self::VIEWERS);
    }

    public function delete(User $user, Category $category): bool
    {
        return $user->hasAnyRole(self::MANAGERS);
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasAnyRole(self::MANAGERS);
    }
}
