<?php

namespace App\Services\Menu;

use App\Models\Menu;
use App\Support\SiteCache;
use Illuminate\Support\Collection;

/**
 * Reads the active, cached menu tree for a given location. This is the only
 * place that queries the menus/menu_items tables for rendering purposes.
 */
class MenuService
{
    /**
     * Returns the root MenuItems for a location, each with up to two more
     * nested levels of children eager-loaded (root + children + grandchildren
     * = MenuItem::MAX_DEPTH levels). Returns an empty collection — never
     * throws — if the menu doesn't exist, is inactive, or has no items.
     */
    public function forLocation(string $location): Collection
    {
        return SiteCache::rememberMenu($location, function () use ($location) {
            $menu = Menu::query()->active()->where('location', $location)->first();

            if (! $menu) {
                return collect();
            }

            return $menu->items()
                ->active()
                ->whereNull('parent_id')
                ->orderBy('sort_order')
                ->with(['children' => fn ($query) => $query->active()->with([
                    'children' => fn ($query) => $query->active(),
                ])])
                ->get();
        });
    }

    /**
     * Returns all root MenuItems for an admin location (including inactive ones)
     * with eager-loaded children for Filament admin navigation and management screens.
     */
    public function forAdminLocation(string $location): Collection
    {
        $menu = Menu::query()->where('location', $location)->first();

        if (! $menu) {
            return collect();
        }

        return $menu->items()
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->with(['children' => fn ($query) => $query->orderBy('sort_order')->with([
                'children' => fn ($query) => $query->orderBy('sort_order'),
            ])])
            ->get();
    }
}
