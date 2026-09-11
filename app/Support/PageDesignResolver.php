<?php

namespace App\Support;

use App\Models\Page;
use App\Models\SiteSetting;

class PageDesignResolver
{
    public static function resolve(?Page $page, ?array $customOverride = null): array
    {
        $siteSettings = SiteSetting::current();
        $globalTheme = $siteSettings->normalized_theme_settings ?? [];
        $activeMode = $globalTheme['mode'] ?? 'dark';

        $globalBg = $globalTheme[$activeMode]['background'] ?? '#030712';
        $globalSurface = $globalTheme[$activeMode]['surface'] ?? '#0f172a';
        $globalAccent = $globalTheme[$activeMode]['accent'] ?? '#f43f5e';
        $globalText = '#f8fafc';
        $globalMutedText = '#94a3b8';

        $pageSettings = $page?->settings ?? [];
        $designSettings = $customOverride ?? ($pageSettings['design'] ?? []);

        if ($customOverride === null && $page && auth()->check() && auth()->user()?->hasAnyRole(['super_admin', 'administrator', 'designer', 'editor'])) {
            $previewToken = request('page_preview_token') ?? session('page_preview_token');
            if ($previewToken) {
                $previewData = \Illuminate\Support\Facades\Cache::get('page_preview_' . $previewToken) ?? session('page_preview_data');
                if ($previewData && is_array($previewData)) {
                    $previewPageId = $previewData['page_id'] ?? null;
                    $previewSlug = $previewData['page_slug'] ?? null;

                    if ($previewPageId === $page->id || $previewSlug === $page->slug) {
                        $designSettings = $previewData['design'] ?? [];
                    }
                }
            }
        }

        $useCustomDesign = (bool) ($designSettings['use_custom_design'] ?? false);

        if (! $useCustomDesign) {
            $defaultMaxWidth = $page?->slug === 'iletisim' ? 1152 : 768;

            return [
                'use_custom_design' => false,
                'background_color' => $globalBg,
                'surface_color' => $globalSurface,
                'accent_color' => $globalAccent,
                'text_color' => $globalText,
                'muted_text_color' => $globalMutedText,
                'content_max_width' => $defaultMaxWidth,
                'card_radius' => 16,
                'padding_top' => 32,
                'padding_bottom' => 48,
                'show_surface' => true,
                'wrapper_style' => 'background-color: ' . $globalBg . ';',
                'surface_style' => 'background-color: ' . $globalSurface . '; border-radius: 16px; max-width: ' . $defaultMaxWidth . 'px;',
                'is_custom' => false,
            ];
        }

        $bgColor = filled($designSettings['background_color'] ?? null) ? $designSettings['background_color'] : $globalBg;
        $surfaceColor = filled($designSettings['surface_color'] ?? null) ? $designSettings['surface_color'] : $globalSurface;
        $accentColor = filled($designSettings['accent_color'] ?? null) ? $designSettings['accent_color'] : $globalAccent;
        $textColor = filled($designSettings['text_color'] ?? null) ? $designSettings['text_color'] : $globalText;
        $mutedTextColor = filled($designSettings['muted_text_color'] ?? null) ? $designSettings['muted_text_color'] : $globalMutedText;

        $contentMaxWidth = (int) ($designSettings['content_max_width'] ?? ($page?->slug === 'iletisim' ? 1152 : 768));
        if ($contentMaxWidth < 320 || $contentMaxWidth > 2000) {
            $contentMaxWidth = $page?->slug === 'iletisim' ? 1152 : 768;
        }

        $cardRadius = (int) ($designSettings['card_radius'] ?? 16);
        if ($cardRadius < 0 || $cardRadius > 100) {
            $cardRadius = 16;
        }

        $paddingTop = (int) ($designSettings['padding_top'] ?? 32);
        if ($paddingTop < 0 || $paddingTop > 200) {
            $paddingTop = 32;
        }

        $paddingBottom = (int) ($designSettings['padding_bottom'] ?? 48);
        if ($paddingBottom < 0 || $paddingBottom > 200) {
            $paddingBottom = 48;
        }

        $showSurface = (bool) ($designSettings['show_surface'] ?? true);

        $wrapperStyle = "background-color: {$bgColor};";

        if ($showSurface) {
            $surfaceStyle = "background-color: {$surfaceColor}; border-radius: {$cardRadius}px; max-width: {$contentMaxWidth}px;";
        } else {
            $surfaceStyle = "background-color: transparent !important; border-color: transparent !important; max-width: {$contentMaxWidth}px;";
        }

        return [
            'use_custom_design' => true,
            'background_color' => $bgColor,
            'surface_color' => $surfaceColor,
            'accent_color' => $accentColor,
            'text_color' => $textColor,
            'muted_text_color' => $mutedTextColor,
            'content_max_width' => $contentMaxWidth,
            'card_radius' => $cardRadius,
            'padding_top' => $paddingTop,
            'padding_bottom' => $paddingBottom,
            'show_surface' => $showSurface,
            'wrapper_style' => $wrapperStyle,
            'surface_style' => $surfaceStyle,
            'is_custom' => true,
        ];
    }
}
