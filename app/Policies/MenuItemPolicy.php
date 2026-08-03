<?php

namespace App\Policies;

use App\Models\MenuItem;
use App\Models\User;

class MenuItemPolicy
{
    private const MANAGERS = ['super_admin', 'administrator'];

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(self::MANAGERS);
    }

    public function view(User $user, MenuItem $menuItem): bool
    {
        return $user->hasAnyRole(self::MANAGERS);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(self::MANAGERS);
    }

    public function update(User $user, MenuItem $menuItem): bool
    {
        return $user->hasAnyRole(self::MANAGERS);
    }

    public function delete(User $user, MenuItem $menuItem): bool
    {
        return $user->hasAnyRole(self::MANAGERS);
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasAnyRole(self::MANAGERS);
    }

    public function reorder(User $user): bool
    {
        return $user->hasAnyRole(self::MANAGERS);
    }
}
