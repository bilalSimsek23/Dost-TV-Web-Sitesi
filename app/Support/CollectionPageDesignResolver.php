<?php

namespace App\Support;

class CollectionPageDesignResolver
{
    public static function resolve(object $collection, string $type = 'video'): array
    {
        $settings = method_exists($collection, 'resolvePublicSettings')
            ? $collection->resolvePublicSettings()
            : ($collection->public_settings ?? []);

        if (auth()->check() && auth()->user()?->hasAnyRole(['super_admin', 'administrator', 'designer', 'editor'])) {
            $previewToken = request('collection_preview_token') ?? session('collection_preview_token');
            if ($previewToken) {
                $previewData = \Illuminate\Support\Facades\Cache::get('collection_preview_' . $previewToken) ?? session('collection_preview_data');
                if ($previewData && is_array($previewData)) {
                    $previewId = $previewData['id'] ?? null;
                    $previewSlug = $previewData['slug'] ?? null;

                    if ($previewId === $collection->id || $previewSlug === $collection->slug) {
                        $settings = array_merge($settings, $previewData['public_settings'] ?? []);
                    }
                }
            }
        }

        $defaultDesktop = $type === 'program' ? 5 : 4;
        $defaultTablet = 3;
        $defaultMobile = $type === 'program' ? 2 : 1;

        $desktopCols = (int) ($settings['desktop_columns'] ?? $defaultDesktop);
        if ($desktopCols < 1 || $desktopCols > 8) {
            $desktopCols = $defaultDesktop;
        }

        $tabletCols = (int) ($settings['tablet_columns'] ?? $defaultTablet);
        if ($tabletCols < 1 || $tabletCols > 6) {
            $tabletCols = $defaultTablet;
        }

        $mobileCols = (int) ($settings['mobile_columns'] ?? $defaultMobile);
        if ($mobileCols < 1 || $mobileCols > 4) {
            $mobileCols = $defaultMobile;
        }

        $gapSize = $settings['gap_size'] ?? 'medium';
        $gapClass = match($gapSize) {
            'small' => 'gap-3 sm:gap-4',
            'large' => 'gap-6 sm:gap-8 lg:gap-10',
            default => 'gap-4 sm:gap-6',
        };

        $mobileGrid = match($mobileCols) {
            2 => 'grid-cols-2',
            3 => 'grid-cols-3',
            4 => 'grid-cols-4',
            default => 'grid-cols-1',
        };

        $tabletGrid = match($tabletCols) {
            1 => 'sm:grid-cols-1',
            2 => 'sm:grid-cols-2',
            4 => 'sm:grid-cols-4',
            5 => 'sm:grid-cols-5',
            default => 'sm:grid-cols-3',
        };

        $desktopGrid = match($desktopCols) {
            1 => 'lg:grid-cols-1',
            2 => 'lg:grid-cols-2',
            3 => 'lg:grid-cols-3',
            5 => 'lg:grid-cols-5',
            6 => 'lg:grid-cols-6',
            7 => 'lg:grid-cols-7',
            8 => 'lg:grid-cols-8',
            default => 'lg:grid-cols-4',
        };

        $gridClass = "grid {$mobileGrid} {$tabletGrid} {$desktopGrid} {$gapClass} w-full";

        return [
            'desktop_columns' => $desktopCols,
            'tablet_columns' => $tabletCols,
            'mobile_columns' => $mobileCols,
            'gap_size' => $gapSize,
            'grid_class' => $gridClass,
            'content_max_width' => 1280,
            'show_description' => (bool) ($settings['show_description'] ?? true),
            'display_variant' => $settings['display_variant'] ?? 'grid',
        ];
    }
}
