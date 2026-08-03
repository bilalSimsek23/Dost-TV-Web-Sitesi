<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Single source of truth for every cache key used by the dynamic site
 * management layer (menus, theme settings, category tree). Nothing else
 * in the codebase should build these key strings directly.
 */
class SiteCache
{
    private const MENU_PREFIX = 'site:menu:';

    private const THEME_KEY = 'site:theme:active';

    private const CATEGORY_TREE_KEY = 'site:categories:tree';

    private const TTL_SECONDS = 86400;

    public static function menuKey(string $location): string
    {
        return self::MENU_PREFIX.$location;
    }

    public static function rememberMenu(string $location, Closure $callback): mixed
    {
        return Cache::remember(self::menuKey($location), self::TTL_SECONDS, $callback);
    }

    public static function forgetMenu(string $location): void
    {
        Cache::forget(self::menuKey($location));
    }

    public static function forgetAllMenus(): void
    {
        Cache::forget(self::menuKey('header_primary'));
        Cache::forget(self::menuKey('header_secondary'));
        Cache::forget(self::menuKey('mobile'));
        Cache::forget(self::menuKey('footer_primary'));
        Cache::forget(self::menuKey('footer_secondary'));
        Cache::forget(self::menuKey('footer_legal'));
        Cache::forget(self::menuKey('sidebar'));
    }

    public static function rememberTheme(Closure $callback): mixed
    {
        return Cache::remember(self::THEME_KEY, self::TTL_SECONDS, $callback);
    }

    public static function forgetTheme(): void
    {
        Cache::forget(self::THEME_KEY);
    }

    public static function rememberCategoryTree(Closure $callback): mixed
    {
        return Cache::remember(self::CATEGORY_TREE_KEY, self::TTL_SECONDS, $callback);
    }

    public static function forgetCategoryTree(): void
    {
        Cache::forget(self::CATEGORY_TREE_KEY);
    }
}
