<?php

namespace App\Filament\Widgets;

use App\Models\Announcement;
use App\Models\Episode;
use App\Models\Khatm;
use App\Models\Program;
use Filament\Widgets\Widget;

class ContentSummaryWidget extends Widget
{
    protected string $view = 'filament.widgets.content-summary-widget';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = [
        'default' => 'full',
        'lg' => 1,
    ];

    protected function getViewData(): array
    {
        $totalPrograms = Program::count();
        $activePrograms = Program::where('is_active', true)->where('status', 'active')->count();

        $totalEpisodes = Episode::count();
        $publishedEpisodes = Episode::where('status', 'published')->where('is_active', true)->count();

        $now = now();
        $activeAnnouncements = Announcement::where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            })
            ->count();

        $activeKhatms = Khatm::where('status', 'active')->count();

        return [
            'totalPrograms' => $totalPrograms,
            'activePrograms' => $activePrograms,
            'programsUrl' => '/admin/programs',

            'totalEpisodes' => $totalEpisodes,
            'publishedEpisodes' => $publishedEpisodes,
            'episodesUrl' => '/admin/episodes',

            'activeAnnouncements' => $activeAnnouncements,
            'announcementsUrl' => '/admin/announcements',

            'activeKhatms' => $activeKhatms,
            'khatmsUrl' => '/admin/khatms',
        ];
    }
}
