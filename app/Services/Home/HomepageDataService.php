<?php

namespace App\Services\Home;

use App\Models\Banner;
use App\Models\Program;
use App\Models\SiteSetting;
use App\Services\Schedule\BroadcastScheduleResolver;
use App\Support\SiteCache;
use Illuminate\Support\Collection;

class HomepageDataService
{
    public function __construct(
        protected BroadcastScheduleResolver $scheduleResolver
    ) {}

    /**
     * Resolves data required for homepage rendering based on normalized section visibility.
     */
    public function getHomepageData(?array $sections = null, ?SiteSetting $settings = null): array
    {
        $settings = $settings ?? SiteSetting::current();
        $sections = $sections ?? $settings->normalized_homepage_sections;

        $sectionsCollection = collect($sections);
        $visibleKeys = $sectionsCollection
            ->filter(fn ($s) => ! empty($s['visible']))
            ->pluck('key')
            ->all();

        $now = now();
        $banners = collect();
        if (in_array('hero', $visibleKeys, true)) {
            $banners = SiteCache::rememberHomeBanners(function () use ($now) {
                return Banner::query()
                    ->where('is_active', true)
                    ->where(function ($query) {
                        $query->whereNull('content_type')
                            ->orWhere('content_type', 'hero');
                    })
                    ->where(function ($query) use ($now) {
                        $query->whereNull('starts_at')
                            ->orWhere('starts_at', '<=', $now);
                    })
                    ->where(function ($query) use ($now) {
                        $query->whereNull('ends_at')
                            ->orWhere('ends_at', '>=', $now);
                    })
                    ->orderBy('sort_order')
                    ->get();
            });
        }

        $featuredPrograms = collect();
        if (in_array('featured_programs', $visibleKeys, true)) {
            $featuredPrograms = SiteCache::rememberHomeFeaturedPrograms(function () {
                return Program::query()
                    ->with('categories')
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->limit(8)
                    ->get();
            });
        }

        $todaySchedule = collect();
        if (in_array('live_intro', $visibleKeys, true) || in_array('today_schedule', $visibleKeys, true)) {
            $todaySchedule = $this->scheduleResolver->getScheduleForDate(now());
        }

        return [
            'settings' => $settings,
            'sections' => $sections,
            'banners' => $banners,
            'featuredPrograms' => $featuredPrograms,
            'todaySchedule' => $todaySchedule,
        ];
    }
}
