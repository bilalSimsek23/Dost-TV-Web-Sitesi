<?php

namespace App\Services\Theme;

use App\Models\ThemeSetting;
use App\Support\SiteCache;
use Illuminate\Support\Collection;

/**
 * Reads/writes the currently active theme design tokens (theme_settings
 * table). Draft/publish/versioning on top of these values (theme_versions)
 * is a later phase's concern and does not live here.
 */
class ThemeSettingsService
{
    /**
     * All active theme settings keyed by their `key`, cached.
     */
    public function all(): Collection
    {
        return SiteCache::rememberTheme(fn () => ThemeSetting::query()->get()->keyBy('key'));
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()->get($key)?->value ?? $default;
    }

    /**
     * Updates an existing setting's value. Silently does nothing for a key
     * with no matching row (e.g. a form field whose seed hasn't run yet) —
     * one unrecognized key must never fail the whole settings save.
     */
    public function set(string $key, mixed $value): ?ThemeSetting
    {
        $setting = ThemeSetting::query()->where('key', $key)->first();

        if (! $setting) {
            return null;
        }

        $setting->update(['value' => $value]);

        return $setting;
    }

    /**
     * Active settings grouped by their `group` column (e.g. for rendering
     * a grouped Filament form or a grouped preview panel).
     */
    public function grouped(): Collection
    {
        return $this->all()->groupBy('group');
    }
}
