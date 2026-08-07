<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    private const ALLOWED_MANAGERS = ['super_admin', 'administrator'];

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(self::ALLOWED_MANAGERS);
    }

    public function view(User $user, User $model): bool
    {
        if (! $user->hasAnyRole(self::ALLOWED_MANAGERS)) {
            return false;
        }

        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(self::ALLOWED_MANAGERS);
    }

    public function update(User $user, User $model): bool
    {
        if (! $user->hasAnyRole(self::ALLOWED_MANAGERS)) {
            return false;
        }

        // Administrator cannot edit a Super Admin record
        if ($model->hasRole('super_admin') && ! $user->hasRole('super_admin')) {
            return false;
        }

        return true;
    }

    public function delete(User $user, User $model): bool
    {
        if (! $user->hasAnyRole(self::ALLOWED_MANAGERS)) {
            return false;
        }

        // Cannot delete self
        if ($user->id === $model->id) {
            return false;
        }

        // Administrator cannot delete a Super Admin record
        if ($model->hasRole('super_admin') && ! $user->hasRole('super_admin')) {
            return false;
        }

        // Protect last active super_admin from deletion
        if ($model->hasRole('super_admin')) {
            $activeSuperAdminsCount = User::where('role', 'super_admin')
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->count();

            if ($activeSuperAdminsCount <= 1) {
                return false;
            }
        }

        return true;
    }

    public function restore(User $user, User $model): bool
    {
        // Only super_admin can restore archived users
        return $user->hasRole('super_admin');
    }

    public function forceDelete(User $user, User $model): bool
    {
        // Hard deletion is disabled
        return false;
    }
}
