<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\LiveBroadcastPage;
use App\Models\SiteSetting;
use App\Services\Schedule\BroadcastScheduleResolver;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

class LiveBroadcastStatsWidget extends Widget
{
    protected string $view = 'filament.widgets.live-broadcast-stats-widget';

    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    protected function getViewData(): array
    {
        $settings = SiteSetting::current();
        $resolver = app(BroadcastScheduleResolver::class);
        $todayBroadcasts = $resolver->getScheduleForDate(now());

        $now = now();
        $currentTime = $now->format('H:i');

        $nowPlaying = null;
        $nextUpcoming = null;

        if ($todayBroadcasts->isNotEmpty()) {
            foreach ($todayBroadcasts as $item) {
                $startTime = Carbon::parse($item->start_time)->format('H:i');
                $endTime = $item->end_time ? Carbon::parse($item->end_time)->format('H:i') : null;

                $isCurrentlyPlaying = false;
                if ($endTime === null || $endTime === '00:00') {
                    $isCurrentlyPlaying = ($currentTime >= $startTime);
                } elseif ($endTime > $startTime) {
                    $isCurrentlyPlaying = ($currentTime >= $startTime && $currentTime < $endTime);
                } else {
                    $isCurrentlyPlaying = ($currentTime >= $startTime || $currentTime < $endTime);
                }

                if ($isCurrentlyPlaying && $nowPlaying === null) {
                    $nowPlaying = $item;
                }

                if ($startTime > $currentTime && $nextUpcoming === null) {
                    $nextUpcoming = $item;
                }
            }
        }

        // TV Status
        $tvIsActive = (bool) ($settings->live_tv_is_active ?? true);
        $tvIsPublic = (bool) ($settings->live_tv_is_public ?? true);
        $tvHasMaintenance = filled($settings->live_tv_maintenance_message) && ! $tvIsActive;

        $tvStatus = match (true) {
            $tvHasMaintenance => 'Bakımda',
            $tvIsActive => 'Aktif',
            default => 'Pasif',
        };

        $tvTypeLabel = match ($settings->live_tv_type) {
            'hls' => 'HLS Stream',
            'iframe' => 'iFrame Gömülü',
            'custom' => 'Harici Link',
            default => 'HLS Stream',
        };

        // Radio Status
        $radioIsActive = (bool) ($settings->radio_is_active ?? true);
        $radioIsPublic = (bool) ($settings->radio_is_public ?? true);
        $radioHasMaintenance = filled($settings->radio_maintenance_message) && ! $radioIsActive;

        $radioStatus = match (true) {
            $radioHasMaintenance => 'Bakımda',
            $radioIsActive => 'Aktif',
            default => 'Pasif',
        };

        return [
            // TV
            'tvStatus' => $tvStatus,
            'tvIsActive' => $tvIsActive,
            'tvIsPublic' => $tvIsPublic,
            'tvTypeLabel' => $tvTypeLabel,
            'tvLiveUrl' => LiveBroadcastPage::getUrl(),

            // FM
            'radioStatus' => $radioStatus,
            'radioIsActive' => $radioIsActive,
            'radioIsPublic' => $radioIsPublic,
            'radioName' => $settings->radio_name ?: 'Dost FM Canlı Radyo',
            'radioLiveUrl' => LiveBroadcastPage::getUrl(),

            // Schedule
            'totalBroadcastsCount' => $todayBroadcasts->count(),
            'nowPlaying' => $nowPlaying,
            'nextUpcoming' => $nextUpcoming,
            'scheduleUrl' => '/admin/schedule-calendar',
        ];
    }
}
