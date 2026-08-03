<?php

namespace App\Policies;

use App\Models\Menu;
use App\Models\User;

class MenuPolicy
{
    private const MANAGERS = ['super_admin', 'administrator'];

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(self::MANAGERS);
    }

    public function view(User $user, Menu $menu): bool
    {
        return $user->hasAnyRole(self::MANAGERS);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(self::MANAGERS);
    }

    public function update(User $user, Menu $menu): bool
    {
        return $user->hasAnyRole(self::MANAGERS);
    }

    public function delete(User $user, Menu $menu): bool
    {
        return $user->hasAnyRole(self::MANAGERS);
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasAnyRole(self::MANAGERS);
    }
}
