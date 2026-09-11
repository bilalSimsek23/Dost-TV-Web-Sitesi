<?php

namespace App\Services\Home;

class HomepageBlockRegistry
{
    public const BLOCK_TODAY_SCHEDULE = 'today_schedule';
    public const BLOCK_LIVE_STREAM = 'live_stream';
    public const BLOCK_VIDEO_COLLECTION = 'video_collection';
    public const BLOCK_PROGRAM_SHOWCASE = 'program_showcase';
    public const BLOCK_ANNOUNCEMENTS = 'announcements';
    public const BLOCK_KHATM = 'khatm';
    public const BLOCK_BANNER = 'banner';
    public const BLOCK_CATEGORY_SHELF = 'category_shelf';
    public const BLOCK_CONTENT_SHELF = 'content_shelf';
    public const BLOCK_YOUTUBE_CHANNEL_SHELF = 'youtube_channel_shelf';
    public const BLOCK_INSTAGRAM_VIDEOS = 'instagram_videos';

    // Program Detail Page Blocks
    public const BLOCK_PROGRAM_HERO = 'program_hero';
    public const BLOCK_PROGRAM_DESCRIPTION = 'program_description';
    public const BLOCK_EPISODES_SHELF = 'episodes_shelf';
    public const BLOCK_RELATED_PROGRAMS = 'related_programs';

    // Collection Detail Page Blocks
    public const BLOCK_COLLECTION_HEADER = 'collection_header';
    public const BLOCK_PROGRAM_COLLECTION_GRID = 'program_collection_grid';
    public const BLOCK_VIDEO_COLLECTION_GRID = 'video_collection_grid';

    public static function getBlockTypes(): array
    {
        return [
            self::BLOCK_YOUTUBE_CHANNEL_SHELF => 'YouTube Kanal Rafı',
            self::BLOCK_INSTAGRAM_VIDEOS => 'Instagram Videoları',
            self::BLOCK_CONTENT_SHELF => 'İçerik Rafı (Dinamik)',
            self::BLOCK_TODAY_SCHEDULE => 'Yayın Akışı',
            self::BLOCK_LIVE_STREAM => 'Canlı Yayın (TV / Radyo)',
            self::BLOCK_VIDEO_COLLECTION => 'Video Koleksiyonu / Vitrini',
            self::BLOCK_PROGRAM_SHOWCASE => 'Program Vitrini',
            self::BLOCK_CATEGORY_SHELF => 'Kategori Rafı',
            self::BLOCK_ANNOUNCEMENTS => 'Duyurular',
            self::BLOCK_KHATM => 'Hatim / Cüz Takibi',
            self::BLOCK_BANNER => 'Özel Banner',
        ];
    }

    public static function getProgramDetailBlockTypes(): array
    {
        return [
            self::BLOCK_PROGRAM_HERO => 'Program Hero (Üst Alan)',
            self::BLOCK_PROGRAM_DESCRIPTION => 'Program Açıklaması',
            self::BLOCK_EPISODES_SHELF => 'Bölümler (Episodes) Rafı',
            self::BLOCK_RELATED_PROGRAMS => 'Benzer Programlar',
        ];
    }

    public static function getProgramCollectionBlockTypes(): array
    {
        return [
            self::BLOCK_COLLECTION_HEADER => 'Sayfa Üst Alanı / Koleksiyon Başlığı',
            self::BLOCK_PROGRAM_COLLECTION_GRID => 'Program Koleksiyon Gridi',
            self::BLOCK_CONTENT_SHELF => 'İçerik Rafı (Dinamik)',
            self::BLOCK_PROGRAM_SHOWCASE => 'Program Vitrini',
            self::BLOCK_VIDEO_COLLECTION => 'Video Koleksiyonu / Vitrini',
            self::BLOCK_CATEGORY_SHELF => 'Kategori Rafı',
            self::BLOCK_TODAY_SCHEDULE => 'Yayın Akışı',
        ];
    }

    public static function getVideoCollectionBlockTypes(): array
    {
        return [
            self::BLOCK_COLLECTION_HEADER => 'Sayfa Üst Alanı / Koleksiyon Başlığı',
            self::BLOCK_VIDEO_COLLECTION_GRID => 'Video Koleksiyon Gridi',
            self::BLOCK_CONTENT_SHELF => 'İçerik Rafı (Dinamik)',
            self::BLOCK_PROGRAM_SHOWCASE => 'Program Vitrini',
            self::BLOCK_VIDEO_COLLECTION => 'Video Koleksiyonu / Vitrini',
            self::BLOCK_CATEGORY_SHELF => 'Kategori Rafı',
            self::BLOCK_TODAY_SCHEDULE => 'Yayın Akışı',
        ];
    }


    public static function getScheduleBlockTypes(): array
    {
        return [
            self::BLOCK_TODAY_SCHEDULE => 'Yayın Akışı',
            self::BLOCK_LIVE_STREAM => 'Canlı Yayın (TV / Radyo)',
            self::BLOCK_CONTENT_SHELF => 'İçerik Rafı (Dinamik)',
            self::BLOCK_BANNER => 'Özel Banner',
        ];
    }

    public static function getProgramIndexBlockTypes(): array
    {
        return [
            self::BLOCK_PROGRAM_SHOWCASE => 'Program Vitrini',
            self::BLOCK_CATEGORY_SHELF => 'Kategori Rafı',
            self::BLOCK_CONTENT_SHELF => 'İçerik Rafı (Dinamik)',
            self::BLOCK_BANNER => 'Özel Banner',
        ];
    }

    public static function getLiveTvBlockTypes(): array
    {
        return [
            self::BLOCK_LIVE_STREAM => 'Canlı Yayın (TV / Radyo)',
            self::BLOCK_TODAY_SCHEDULE => 'Yayın Akışı',
            self::BLOCK_CONTENT_SHELF => 'İçerik Rafı (Dinamik)',
            self::BLOCK_BANNER => 'Özel Banner',
        ];
    }

    public static function getFixedBlocksForPageType(string $pageType = 'home'): array
    {
        if ($pageType === 'home') {
            return ['header', 'hero', 'footer'];
        }

        return [];
    }

    public static function getBlockTypesForPageType(string $pageType = 'home'): array
    {
        return match ($pageType) {
            'program_detail' => self::getProgramDetailBlockTypes(),
            'program_collection' => self::getProgramCollectionBlockTypes(),
            'video_collection' => self::getVideoCollectionBlockTypes(),
            'schedule' => self::getScheduleBlockTypes(),
            'program_index' => self::getProgramIndexBlockTypes(),
            'live_tv' => self::getLiveTvBlockTypes(),
            'home' => self::getBlockTypes(),
            default => self::getBlockTypes(),
        };
    }


    public static function getShelfTypes(): array
    {
        return [
            'program' => 'Program Rafı',
            'video' => 'Video Rafı',
        ];
    }

    public static function getShelfSourceModes(): array
    {
        return [
            'manual' => 'Manuel Seçim',
            'category' => 'Kategoriye Bağlı (Otomatik)',
            'hybrid' => 'Hibrit (Sabitlenenler + Kategori)',
        ];
    }

    public static function getCategoryModes(): array
    {
        return [
            'live_category' => 'Canlı Kategori (Yeni Eklenenler Otomatik)',
            'fixed_list' => 'Sabit Liste (Dondurulmuş İçerik)',
        ];
    }

    public static function getCategoryShelfLimits(): array
    {
        return [4 => '4', 5 => '5', 6 => '6', 8 => '8', 10 => '10', 12 => '12'];
    }

    public static function getDisplayVariants(): array
    {
        return [
            'grid' => 'Izgara Görünümü (Grid)',
            'horizontal_carousel' => 'Yatay Kaydırmalı (Carousel)',
            'featured_and_list' => 'Öne Çıkan + Küçük Kartlar',
            'compact_list' => 'Kompakt Liste',
        ];
    }

    public static function getDesktopColumns(): array
    {
        return [
            1 => '1 Sütun',
            2 => '2 Sütun',
            3 => '3 Sütun',
            4 => '4 Sütun',
            5 => '5 Sütun',
            6 => '6 Sütun',
            'custom' => 'Özel',
        ];
    }

    public static function getTabletColumns(): array
    {
        return [
            1 => '1 Sütun',
            2 => '2 Sütun',
            3 => '3 Sütun',
            4 => '4 Sütun',
        ];
    }

    public static function getMobileColumns(): array
    {
        return [
            1 => '1 Sütun',
            2 => '2 Sütun',
        ];
    }

    public static function getPaddingYOptions(): array
    {
        return [
            'sm' => 'Kompakt (py-4)',
            'md' => 'Normal (py-8)',
            'lg' => 'Geniş (py-12)',
            'xl' => 'Ekstra Geniş (py-16)',
        ];
    }

    public static function getDensityOptions(): array
    {
        return [
            'compact' => 'Kompakt',
            'normal' => 'Normal',
            'spacious' => 'Ferah',
        ];
    }

    public static function getBgStyleOptions(): array
    {
        return [
            'transparent' => 'Şeffaf',
            'dark' => 'Hafif Koyu',
            'light' => 'Hafif Açık',
        ];
    }

    public static function getGapOptions(): array
    {
        return [
            'sm' => 'Dar (gap-3)',
            'md' => 'Normal (gap-4)',
            'lg' => 'Geniş (gap-6)',
        ];
    }

    public static function getCardRatioOptions(): array
    {
        return [
            'default' => 'Varsayılan (3:4)',
            '16:9' => '16:9 Yassı',
            '4:3' => '4:3 Standart',
            '1:1' => '1:1 Kare',
            '3:4' => '3:4 Dikey Poster',
        ];
    }

    public static function getCardWidthOptions(): array
    {
        return [
            'auto' => 'Otomatik',
            'sm' => 'Dar (190px)',
            'md' => 'Orta (230px)',
            'lg' => 'Geniş (270px)',
            'custom' => 'Özel px',
        ];
    }

    public static function resolveCardWidthPx(?string $cardWidth, mixed $customPx = null): ?int
    {
        return match ($cardWidth) {
            'sm' => 190,
            'md' => 230,
            'lg' => 270,
            'custom' => filled($customPx) ? max(100, min(800, (int) $customPx)) : 230,
            default => null,
        };
    }

    public static function getCardRadiusOptions(): array
    {
        return [
            'none' => 'Köşeli (Yok)',
            'sm' => 'Küçük',
            'md' => 'Normal',
            'lg' => 'Yuvarlak',
        ];
    }

    public static function getSectionWidthOptions(): array
    {
        return [
            'boxed' => 'Standart (Kutulu)',
            'full' => 'Tam Genişlik',
        ];
    }

    public static function getSectionWidthModes(): array
    {
        return [
            'full' => 'Tam Ekran (%100)',
            'boxed' => 'Standart Kutulu (1280px)',
            'custom' => 'Özel Genişlik',
        ];
    }

    public static function getContentWidthModes(): array
    {
        return [
            'standard' => 'Standart (1280px)',
            'wide' => 'Geniş (1600px)',
            'full' => 'Tam Genişlik (%100)',
            'custom' => 'Özel Genişlik',
        ];
    }

    public static function getSectionHeightModes(): array
    {
        return [
            'auto' => 'Otomatik',
            'short' => 'Kısa (380px)',
            'medium' => 'Orta (480px)',
            'tall' => 'Uzun (600px)',
            'custom' => 'Özel Yükseklik',
        ];
    }

    public static function resolveSectionLayout(array $block): array
    {
        $sectionWidthMode = $block['section_width_mode'] ?? ($block['section_width'] ?? 'full');
        $sectionMaxWidth = max(400, min(3840, (int) ($block['section_max_width'] ?? 1440)));

        $contentWidthMode = $block['content_width_mode'] ?? 'standard';
        $contentMaxWidth = max(400, min(3840, (int) ($block['content_max_width'] ?? 1600)));

        $sectionHeightMode = $block['section_height_mode'] ?? 'auto';
        $sectionHeight = max(100, min(2000, (int) ($block['section_height'] ?? 420)));

        $paddingYMode = $block['padding_y_mode'] ?? ($block['padding_y'] ?? 'normal');
        $paddingYCustom = (int) ($block['padding_y_custom'] ?? 32);
        $paddingYPx = match ($paddingYMode) {
            'compact', 'sm' => 16,
            'spacious', 'lg' => 48,
            'custom' => max(0, min(160, $paddingYCustom)),
            default => 32,
        };

        $shellClasses = ['w-full', 'relative'];
        $shellStyles = ["padding-top: {$paddingYPx}px;", "padding-bottom: {$paddingYPx}px;"];

        if ($sectionWidthMode === 'boxed') {
            $shellClasses[] = 'max-w-7xl';
            $shellClasses[] = 'mx-auto';
        } elseif ($sectionWidthMode === 'custom') {
            $shellClasses[] = 'mx-auto';
            $shellStyles[] = "max-width: {$sectionMaxWidth}px;";
        } else {
            $shellStyles[] = 'width: 100%;';
        }

        if ($sectionHeightMode === 'short') {
            $shellStyles[] = 'min-height: 380px;';
        } elseif ($sectionHeightMode === 'medium') {
            $shellStyles[] = 'min-height: 480px;';
        } elseif ($sectionHeightMode === 'tall') {
            $shellStyles[] = 'min-height: 600px;';
        } elseif ($sectionHeightMode === 'custom') {
            $shellStyles[] = "min-height: {$sectionHeight}px;";
        }

        $innerClasses = ['mx-auto'];
        $innerStyles = [];

        if ($sectionWidthMode === 'boxed') {
            $innerClasses[] = 'w-full';
        } else {
            if ($contentWidthMode === 'standard') {
                $innerClasses[] = 'max-w-7xl';
            } elseif ($contentWidthMode === 'wide') {
                $innerClasses[] = 'max-w-[1600px]';
            } elseif ($contentWidthMode === 'full') {
                $innerClasses[] = 'w-full';
            } elseif ($contentWidthMode === 'custom') {
                $innerStyles[] = "max-width: {$contentMaxWidth}px;";
                $innerClasses[] = 'w-full';
            }
        }

        return [
            'section_width_mode' => $sectionWidthMode,
            'section_max_width' => $sectionMaxWidth,
            'content_width_mode' => $contentWidthMode,
            'content_max_width' => $contentMaxWidth,
            'section_height_mode' => $sectionHeightMode,
            'section_height' => $sectionHeight,
            'shell_class' => implode(' ', $shellClasses),
            'shell_style' => implode(' ', $shellStyles),
            'inner_class' => implode(' ', $innerClasses),
            'inner_style' => implode(' ', $innerStyles),
        ];
    }

    /**
     * Resolves dynamic background, surface, and accent colors for homepage sections.
    /**
     * Resolves section background, surface, accent, text, muted, and border colors.
     * When use_custom_colors is false (default), global CSS variables are used.
     * When true, section custom colors are applied with fallback to global variables for empty fields.
     */
    public static function resolveSectionColors(array $block): array
    {
        $useCustom = ! empty($block['use_custom_colors'] ?? false);

        if ($useCustom) {
            $bg = filled($block['section_background'] ?? ($block['custom_colors']['background'] ?? null))
                ? ($block['section_background'] ?? $block['custom_colors']['background'])
                : 'var(--color-bg)';

            $surface = filled($block['section_surface'] ?? ($block['custom_colors']['surface'] ?? null))
                ? ($block['section_surface'] ?? $block['custom_colors']['surface'])
                : 'var(--color-surface)';

            $accent = filled($block['section_accent'] ?? ($block['custom_colors']['accent'] ?? null))
                ? ($block['section_accent'] ?? $block['custom_colors']['accent'])
                : 'var(--color-accent)';

            $text = filled($block['section_text'] ?? ($block['custom_colors']['text'] ?? null))
                ? ($block['section_text'] ?? $block['custom_colors']['text'])
                : 'var(--color-text)';

            $muted = filled($block['section_muted'] ?? ($block['custom_colors']['muted'] ?? null))
                ? ($block['section_muted'] ?? $block['custom_colors']['muted'])
                : 'var(--color-muted)';

            $border = filled($block['section_border'] ?? ($block['custom_colors']['border'] ?? null))
                ? ($block['section_border'] ?? $block['custom_colors']['border'])
                : 'rgba(148, 163, 184, 0.15)';
        } else {
            $bg = 'transparent';
            $surface = 'var(--color-surface)';
            $accent = 'var(--color-accent)';
            $text = 'var(--color-text)';
            $muted = 'var(--color-muted)';
            $border = 'rgba(148, 163, 184, 0.15)';
        }

        $bgStyle = $useCustom ? "background-color: {$bg};" : "background-color: transparent;";

        return [
            'use_custom_colors' => $useCustom,
            'bg' => $bg,
            'surface' => $surface,
            'accent' => $accent,
            'text' => $text,
            'muted' => $muted,
            'border' => $border,
            'shell_style' => "{$bgStyle} color: {$text}; border-color: {$border};" . ($useCustom ? " --color-bg: {$bg}; --color-surface: {$surface}; --color-accent: {$accent}; --color-text: {$text}; --color-muted: {$muted}; --color-border: {$border};" : ''),
            'surface_style' => "background-color: {$surface}; border-color: {$border};",
            'accent_style' => "color: {$accent};",
        ];
    }

    public static function resolveCardRadiusPx(mixed $radius): int
    {
        if (is_numeric($radius)) {
            return max(0, (int) $radius);
        }

        return match ((string) $radius) {
            'none' => 0,
            'sm' => 6,
            'lg' => 20,
            'xl' => 28,
            'full' => 9999,
            default => 12,
        };
    }

    public static function getTitleSizeOptions(): array
    {
        return [
            'sm' => 'Küçük',
            'md' => 'Normal',
            'lg' => 'Büyük',
        ];
    }

    public static function getSimpleDisplayVariants(): array
    {
        return [
            'horizontal_carousel' => 'Yatay',
            'grid' => 'Grid',
        ];
    }

    public static function getRowCountOptions(): array
    {
        return [1 => '1 Satır', 2 => '2 Satır', 3 => '3 Satır'];
    }

    public static function getContentLimitOptions(): array
    {
        return ['all' => 'Tümü', 4 => '4', 6 => '6', 8 => '8', 12 => '12', 'custom' => 'Özel'];
    }

    /**
     * Resolves the actual content limit (null = unlimited) from a block's config.
     */
    public static function resolveContentLimit(array $block, ?int $default = 8): ?int
    {
        $value = $block['content_limit'] ?? $default;

        if ($value === 'all') {
            return null;
        }

        if ($value === 'custom') {
            return max(1, min((int) ($block['content_limit_custom'] ?? $default), 60));
        }

        return max(1, min((int) $value, 60));
    }

    /**
     * Resolves the actual desktop column count (1-8) from a block's config.
     */
    public static function resolveDesktopColumns(array $block, int $default = 4): int
    {
        $value = $block['desktop_columns'] ?? $block['columns'] ?? $default;

        if ($value === 'custom') {
            return max(1, min((int) ($block['desktop_columns_custom'] ?? $default), 8));
        }

        return max(1, min((int) $value, 8));
    }

    public static function getTitleAlignments(): array
    {
        return [
            'left' => 'Sol',
            'center' => 'Orta',
            'right' => 'Sağ',
        ];
    }

    public static function getVideoSourceModes(): array
    {
        return [
            'video_collections' => 'Video Koleksiyonları',
            'manual_collection' => 'Kayıtlı Video Koleksiyonu',
            'latest_videos' => 'Otomatik — En Yeni Yayınlananlar',
            'program_videos' => 'Otomatik — Belirli Programdan',
            'category_videos' => 'Otomatik — Belirli Kategoriden',
            'random_videos' => 'Otomatik — Rastgele',
            'hybrid_videos' => 'Hibrit — Sabitlenenler + Otomatik Tamamlama',
        ];
    }

    public static function getProgramSourceModes(): array
    {
        return [
            'program_collections' => 'Program Koleksiyonları',
            'active_period_schedule' => 'Aktif Yayın Dönemi Akışından Otomatik',
            'featured_programs' => 'Öne Çıkan Programlar',
            'category_programs' => 'Belirli Kategori',
            'manual_programs' => 'Manuel Seçim',
            'hybrid_programs' => 'Hibrit — Sabitlenenler + Otomatik Tamamlama',
        ];
    }

    /**
     * Maps desktop column count to automatic default tablet and mobile column counts.
     */
    public static function getAutoResponsiveMap(int $desktopColumns): array
    {
        return match ($desktopColumns) {
            1 => ['tablet' => 1, 'mobile' => 1],
            2 => ['tablet' => 2, 'mobile' => 1],
            3 => ['tablet' => 2, 'mobile' => 2],
            4 => ['tablet' => 3, 'mobile' => 2],
            5 => ['tablet' => 3, 'mobile' => 2],
            6 => ['tablet' => 3, 'mobile' => 2],
            7 => ['tablet' => 4, 'mobile' => 2],
            8 => ['tablet' => 4, 'mobile' => 2],
            9 => ['tablet' => 5, 'mobile' => 3],
            10 => ['tablet' => 5, 'mobile' => 3],
            11 => ['tablet' => 6, 'mobile' => 3],
            default => ['tablet' => max(1, min(6, (int) ceil($desktopColumns / 2))), 'mobile' => 3],
        };
    }

    /**
     * Resolves numeric pixel value for gap size (preset or free numeric value).
     */
    public static function resolveGapPx($gapValue): int
    {
        if (is_numeric($gapValue)) {
            return max(0, min(100, (int) $gapValue));
        }

        return match ($gapValue) {
            'none', '0' => 0,
            'sm', 'small' => 12,
            'lg', 'large' => 24,
            'xl' => 32,
            default => 16,
        };
    }

    /**
     * Resolves numeric pixel value for padding Y (preset or free numeric value).
     */
    public static function resolvePaddingYPx($paddingValue): int
    {
        if (is_numeric($paddingValue)) {
            return max(0, min(200, (int) $paddingValue));
        }

        return match ($paddingValue) {
            'sm' => 16,
            'lg' => 48,
            'xl' => 64,
            default => 32,
        };
    }

    /**
     * Centralized responsive typography, spacing, and schedule settings fallback map.
     */
    public static function getAutoTypographyMap(array $block): array
    {
        $desktopHeading = (int) ($block['heading_size'] ?? 28);
        $desktopSubtitle = (int) ($block['subtitle_size'] ?? 16);
        $desktopCta = (int) ($block['cta_size'] ?? 15);
        $desktopCardTitle = (int) ($block['card_title_size'] ?? 16);
        $desktopMeta = (int) ($block['meta_size'] ?? 13);

        $desktopPaddingY = self::resolvePaddingYPx($block['padding_y'] ?? 'md');
        $desktopPaddingTop = (int) ($block['section_padding_top'] ?? $desktopPaddingY);
        $desktopPaddingBottom = (int) ($block['section_padding_bottom'] ?? $desktopPaddingY);
        // 32px = diğer ana sayfa bölümlerinin kullandığı ortak `lg:px-8` genişliğiyle hizalı.
        $desktopPaddingX = (int) ($block['section_padding_x'] ?? 32);
        $desktopHeadingMarginB = (int) ($block['heading_margin_bottom'] ?? 16);
        $desktopCtaMarginT = (int) ($block['cta_margin_top'] ?? 16);

        $desktopSchedTime = (int) ($block['schedule_time_size'] ?? 15);
        $desktopSchedProg = (int) ($block['schedule_program_size'] ?? 16);
        $desktopSchedHeight = (int) ($block['schedule_item_height'] ?? 80);
        $desktopSchedGap = (int) ($block['schedule_item_gap'] ?? 16);
        $desktopSchedPaddingX = (int) ($block['schedule_horizontal_padding'] ?? 16);

        return [
            'desktop' => [
                'heading_size' => $desktopHeading,
                'subtitle_size' => $desktopSubtitle,
                'cta_size' => $desktopCta,
                'card_title_size' => $desktopCardTitle,
                'meta_size' => $desktopMeta,
                'section_padding_top' => $desktopPaddingTop,
                'section_padding_bottom' => $desktopPaddingBottom,
                'section_padding_x' => $desktopPaddingX,
                'heading_margin_bottom' => $desktopHeadingMarginB,
                'cta_margin_top' => $desktopCtaMarginT,
                'schedule_time_size' => $desktopSchedTime,
                'schedule_program_size' => $desktopSchedProg,
                'schedule_item_height' => $desktopSchedHeight,
                'schedule_item_gap' => $desktopSchedGap,
                'schedule_horizontal_padding' => $desktopSchedPaddingX,
            ],
            'tablet' => [
                'heading_size' => (int) ($block['tablet_heading_size'] ?? max(18, (int) round($desktopHeading * 0.85))),
                'subtitle_size' => (int) ($block['tablet_subtitle_size'] ?? max(14, $desktopSubtitle - 1)),
                'cta_size' => (int) ($block['tablet_cta_size'] ?? max(13, $desktopCta - 1)),
                'card_title_size' => (int) ($block['tablet_card_title_size'] ?? max(14, $desktopCardTitle - 1)),
                'meta_size' => (int) ($block['tablet_meta_size'] ?? max(12, $desktopMeta - 1)),
                'section_padding_top' => (int) round($desktopPaddingTop * 0.85),
                'section_padding_bottom' => (int) round($desktopPaddingBottom * 0.85),
                'section_padding_x' => 12,
                'heading_margin_bottom' => (int) round($desktopHeadingMarginB * 0.85),
                'cta_margin_top' => (int) round($desktopCtaMarginT * 0.85),
                'schedule_time_size' => 14,
                'schedule_program_size' => 14,
                'schedule_item_height' => 72,
                'schedule_item_gap' => 12,
                'schedule_horizontal_padding' => 12,
            ],
            'mobile' => [
                'heading_size' => (int) ($block['mobile_heading_size'] ?? max(16, (int) round($desktopHeading * 0.72))),
                'subtitle_size' => (int) ($block['mobile_subtitle_size'] ?? max(13, $desktopSubtitle - 2)),
                'cta_size' => (int) ($block['mobile_cta_size'] ?? max(12, $desktopCta - 2)),
                'card_title_size' => (int) ($block['mobile_card_title_size'] ?? max(13, $desktopCardTitle - 2)),
                'meta_size' => (int) ($block['mobile_meta_size'] ?? max(11, $desktopMeta - 1)),
                'section_padding_top' => (int) round($desktopPaddingTop * 0.65),
                'section_padding_bottom' => (int) round($desktopPaddingBottom * 0.65),
                'section_padding_x' => 10,
                'heading_margin_bottom' => (int) round($desktopHeadingMarginB * 0.65),
                'cta_margin_top' => (int) round($desktopCtaMarginT * 0.65),
                'schedule_time_size' => 13,
                'schedule_program_size' => 13,
                'schedule_item_height' => 64,
                'schedule_item_gap' => 8,
                'schedule_horizontal_padding' => 10,
            ],
        ];
    }

    /**
     * Resolves full per-device responsive presentation settings for a block or collection layout config.
     */
    public static function resolveResponsiveSettings(array $block): array
    {
        $desktopColumns = self::resolveDesktopColumns($block, 4);
        $displayVariant = $block['display_variant'] ?? 'grid';
        $gapSize = $block['gap_size'] ?? 'md';
        $paddingY = $block['padding_y'] ?? 'md';
        $showArrows = ! empty($block['show_arrows'] ?? true);
        $showDots = ! empty($block['show_dots'] ?? false);
        $rowCount = (int) ($block['row_count'] ?? 1);

        $customResponsive = ! empty($block['custom_responsive'] ?? false);
        $responsiveSettings = (array) ($block['responsive_settings'] ?? []);

        $autoMap = self::getAutoResponsiveMap($desktopColumns);
        $autoTypo = self::getAutoTypographyMap($block);

        $tabletColumns = (int) ($customResponsive
            ? ($responsiveSettings['tablet']['columns'] ?? $block['tablet_columns'] ?? $autoMap['tablet'])
            : ($block['tablet_columns'] ?? $autoMap['tablet']));

        $mobileColumns = (int) ($customResponsive
            ? ($responsiveSettings['mobile']['columns'] ?? $block['mobile_columns'] ?? $autoMap['mobile'])
            : ($block['mobile_columns'] ?? $autoMap['mobile']));

        $tabletDisplayVariant = $customResponsive
            ? ($responsiveSettings['tablet']['display_variant'] ?? $displayVariant)
            : $displayVariant;

        $mobileDisplayVariant = $customResponsive
            ? ($responsiveSettings['mobile']['display_variant'] ?? $displayVariant)
            : $displayVariant;

        $tabletGapSize = $customResponsive
            ? ($responsiveSettings['tablet']['gap_size'] ?? $gapSize)
            : $gapSize;

        $mobileGapSize = $customResponsive
            ? ($responsiveSettings['mobile']['gap_size'] ?? $gapSize)
            : $gapSize;

        $tabletPaddingY = $customResponsive
            ? ($responsiveSettings['tablet']['padding_y'] ?? $paddingY)
            : $paddingY;

        $mobilePaddingY = $customResponsive
            ? ($responsiveSettings['mobile']['padding_y'] ?? $paddingY)
            : $paddingY;

        $tabletShowArrows = $customResponsive
            ? ($responsiveSettings['tablet']['show_arrows'] ?? $showArrows)
            : $showArrows;

        $mobileShowArrows = $customResponsive
            ? ($responsiveSettings['mobile']['show_arrows'] ?? $showArrows)
            : $showArrows;

        // Resolve device specific typography & spacing parameters
        $getDeviceParam = function (string $device, string $key) use ($customResponsive, $responsiveSettings, $autoTypo) {
            if ($customResponsive && isset($responsiveSettings[$device][$key])) {
                return $responsiveSettings[$device][$key];
            }
            return $autoTypo[$device][$key] ?? null;
        };

        $buildDevicePayload = function (string $device, int $cols, string $variant, $gap, $padY, bool $arrows) use ($getDeviceParam, $autoTypo, $showDots, $rowCount) {
            $typos = $autoTypo[$device];
            $params = [
                'columns' => $cols,
                'display_variant' => $variant,
                'gap_size' => $gap,
                'padding_y' => $padY,
                'show_arrows' => $arrows,
                'show_dots' => $showDots,
                'row_count' => $rowCount,
            ];

            foreach (array_keys($typos) as $key) {
                $resolvedVal = $getDeviceParam($device, $key);
                $params[$key] = $resolvedVal !== null ? (int) $resolvedVal : $typos[$key];
            }

            return $params;
        };

        return [
            'custom_responsive' => $customResponsive,
            'desktop' => $buildDevicePayload('desktop', max(1, min(12, $desktopColumns)), $displayVariant, $gapSize, $paddingY, $showArrows),
            'tablet' => $buildDevicePayload('tablet', max(1, min(12, $tabletColumns)), $tabletDisplayVariant, $tabletGapSize, $tabletPaddingY, $tabletShowArrows),
            'mobile' => $buildDevicePayload('mobile', max(1, min(6, $mobileColumns)), $mobileDisplayVariant, $mobileGapSize, $mobilePaddingY, $mobileShowArrows),
        ];
    }
}
