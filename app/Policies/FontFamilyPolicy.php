<?php

namespace App\Policies;

use App\Models\FontFamily;
use App\Models\User;

class FontFamilyPolicy
{
    private const MANAGERS = ['super_admin', 'administrator', 'designer'];

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(self::MANAGERS);
    }

    public function view(User $user, FontFamily $fontFamily): bool
    {
        return $user->hasAnyRole(self::MANAGERS);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(self::MANAGERS);
    }

    public function update(User $user, FontFamily $fontFamily): bool
    {
        return $user->hasAnyRole(self::MANAGERS);
    }

    public function delete(User $user, FontFamily $fontFamily): bool
    {
        return $user->hasAnyRole(self::MANAGERS);
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasAnyRole(self::MANAGERS);
    }
}
