<?php

namespace App\Support;

/**
 * Guards menu-item / link URL input against dangerous schemes. Used by the
 * Filament MenuItem form and reusable anywhere else a user-supplied URL
 * needs the same check.
 */
class SafeUrl
{
    private const DANGEROUS_SCHEMES = ['javascript:', 'data:', 'vbscript:', 'file:'];

    public static function isSafe(?string $value): bool
    {
        if (blank($value) || $value === '#') {
            return true;
        }

        $normalized = strtolower(trim($value));

        foreach (self::DANGEROUS_SCHEMES as $scheme) {
            if (str_starts_with($normalized, $scheme)) {
                return false;
            }
        }

        if (str_starts_with($value, '/')) {
            return (bool) preg_match('/^\/[A-Za-z0-9\-_\/?=&.#%]*$/', $value);
        }

        return (bool) filter_var($value, FILTER_VALIDATE_URL);
    }
}
