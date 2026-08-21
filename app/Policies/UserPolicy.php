<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isAdministrator();
    }

    public function view(User $user, User $model): bool
    {
        return $user->isSuperAdmin() || $user->isAdministrator();
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isAdministrator();
    }

    public function update(User $user, User $model): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isAdministrator()) {
            // Administrator cannot update Super Admin
            return ! $model->isSuperAdmin();
        }

        return false;
    }

    public function delete(User $user, User $model): bool
    {
        if ($user->isSuperAdmin()) {
            // Cannot delete last active super admin
            return ! User::isLastActiveSuperAdmin($model);
        }

        if ($user->isAdministrator()) {
            // Administrator cannot delete Super Admin
            return ! $model->isSuperAdmin();
        }

        return false;
    }

    public function restore(User $user, User $model): bool
    {
        return $user->isSuperAdmin();
    }

    public function forceDelete(User $user, User $model): bool
    {
        // Only super_admin can permanently delete accounts (and never the last active super admin)
        if (! $user->isSuperAdmin()) {
            return false;
        }

        return ! User::isLastActiveSuperAdmin($model);
    }
}
