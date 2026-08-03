<?php

namespace App\Services\Menu;

use App\Models\MenuItem;

/**
 * Resolves the final, clickable URL for a single MenuItem. This is the only
 * place in the codebase allowed to translate a MenuItem's item_type into a
 * URL — Blade views must never re-implement this switch.
 */
class MenuResolver
{
    public function resolve(MenuItem $item): ?string
    {
        return match ($item->item_type) {
            'page', 'program', 'category' => $this->resolveLinkedModel($item),
            'route' => $this->resolveNamedRoute($item),
            'url', 'custom' => $item->url,
            'live_tv' => route('live.tv'),
            'live_radio' => route('live.radio'),
            'schedule' => route('schedule.index'),
            'program_listing', 'program_mega_menu' => route('programs.index'),
            'dropdown' => null,
            default => null,
        };
    }

    private function resolveLinkedModel(MenuItem $item): ?string
    {
        $model = $item->linkedModel;

        if (! $model) {
            return null;
        }

        return match ($item->item_type) {
            'page' => route('pages.show', $model),
            'program' => route('programs.show', $model),
            'category' => route('programs.index', ['kategori' => $model->slug]),
            default => null,
        };
    }

    private function resolveNamedRoute(MenuItem $item): ?string
    {
        if (blank($item->route_name)) {
            return null;
        }

        $parameters = $item->metadata['route_parameters'] ?? [];

        return route($item->route_name, $parameters);
    }
}
