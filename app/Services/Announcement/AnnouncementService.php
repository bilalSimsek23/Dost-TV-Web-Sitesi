<?php

namespace App\Services\Announcement;

use App\Models\Announcement;
use Illuminate\Database\Eloquent\Collection;

class AnnouncementService
{
    /**
     * Get all currently visible announcements for a given placement (including global ones).
     * Ordered by pinned status first, then sort_order, latest starts_at, and newest id.
     */
    public function getVisibleForPlacement(string $placement): Collection
    {
        return Announcement::query()
            ->currentlyVisible()
            ->forPlacement($placement)
            ->orderByDesc('is_pinned')
            ->orderBy('sort_order')
            ->orderByDesc('starts_at')
            ->orderByDesc('id')
            ->get();
    }

    public function forHome(): Collection
    {
        return $this->getVisibleForPlacement('home');
    }

    public function forLiveTv(): Collection
    {
        return $this->getVisibleForPlacement('live_tv');
    }

    public function forLiveRadio(): Collection
    {
        return $this->getVisibleForPlacement('live_radio');
    }

    public function forSchedule(): Collection
    {
        return $this->getVisibleForPlacement('schedule');
    }

    public function global(): Collection
    {
        return Announcement::query()
            ->currentlyVisible()
            ->where('placement', 'global')
            ->orderByDesc('is_pinned')
            ->orderBy('sort_order')
            ->orderByDesc('starts_at')
            ->orderByDesc('id')
            ->get();
    }
}
