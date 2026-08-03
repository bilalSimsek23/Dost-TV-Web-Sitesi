<?php

namespace App\Services\Schedule;

use App\Models\Schedule;
use App\Models\ScheduleTemplate;
use App\Models\ScheduleTemplateItem;
use App\Models\ScheduleVersionHistory;
use App\Support\SiteCache;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ScheduleCalendarService
{
    public function getTemplates(): Collection
    {
        return ScheduleTemplate::query()
            ->where('is_active', true)
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getActiveOrSelectedTemplate(?int $templateId = null): ?ScheduleTemplate
    {
        if ($templateId) {
            $template = ScheduleTemplate::with(['items.program', 'versionHistories'])->find($templateId);
            if ($template) {
                return $template;
            }
        }

        return ScheduleTemplate::with(['items.program', 'versionHistories'])
            ->where('status', 'published')
            ->where('is_active', true)
            ->orderBy('priority', 'desc')
            ->first()
            ?: ScheduleTemplate::with(['items.program', 'versionHistories'])->first();
    }

    public function getDayCounts(ScheduleTemplate $template): array
    {
        $counts = [];
        for ($day = 0; $day < 7; $day++) {
            $counts[$day] = $template->items()->where('day_of_week', $day)->count();
        }
        return $counts;
    }

    public function checkOverlap(int $templateId, int $dayOfWeek, string $startTime, ?string $endTime, ?int $ignoreItemId = null): bool
    {
        $query = ScheduleTemplateItem::query()
            ->where('schedule_template_id', $templateId)
            ->where('day_of_week', $dayOfWeek);

        if ($ignoreItemId) {
            $query->where('id', '!=', $ignoreItemId);
        }

        $start = Carbon::parse($startTime)->format('H:i:s');
        $end = $endTime ? Carbon::parse($endTime)->format('H:i:s') : '23:59:59';

        return $query->where(function ($q) use ($start, $end) {
            $q->where(function ($q2) use ($start, $end) {
                $q2->where('start_time', '<=', $start)
                   ->where('end_time', '>', $start);
            })->orWhere(function ($q3) use ($start, $end) {
                $q3->where('start_time', '<', $end)
                   ->where('end_time', '>=', $end);
            })->orWhere(function ($q4) use ($start, $end) {
                $q4->where('start_time', '>=', $start)
                   ->where('end_time', '<=', $end);
            });
        })->exists();
    }

    public function shiftTimes(ScheduleTemplate $template, int $minutes, ?int $targetDay = null): void
    {
        $itemsQuery = $template->items();
        if ($targetDay !== null) {
            $itemsQuery->where('day_of_week', $targetDay);
        }

        $items = $itemsQuery->get();

        foreach ($items as $item) {
            $start = Carbon::parse($item->start_time)->addMinutes($minutes)->format('H:i');
            $end = $item->end_time ? Carbon::parse($item->end_time)->addMinutes($minutes)->format('H:i') : null;

            $item->update([
                'start_time' => $start,
                'end_time' => $end,
            ]);
        }

        if ($template->status === 'published') {
            $template->update(['status' => 'draft']);
        }

        SiteCache::forgetMenu('header_primary');
    }

    public function copyDay(ScheduleTemplate $template, int $sourceDay, int $targetDay): void
    {
        $sourceItems = $template->items()->where('day_of_week', $sourceDay)->get();

        $template->items()->where('day_of_week', $targetDay)->delete();

        foreach ($sourceItems as $item) {
            $newItem = $item->replicate(['schedule_template_id', 'day_of_week']);
            $newItem->schedule_template_id = $template->id;
            $newItem->day_of_week = $targetDay;
            $newItem->save();
        }

        if ($template->status === 'published') {
            $template->update(['status' => 'draft']);
        }

        SiteCache::forgetMenu('header_primary');
    }

    public function copyWeek(ScheduleTemplate $sourceTemplate, int $targetTemplateId): void
    {
        $targetTemplate = ScheduleTemplate::find($targetTemplateId);
        if (! $targetTemplate) {
            return;
        }

        $targetTemplate->items()->delete();

        foreach ($sourceTemplate->items as $item) {
            $newItem = $item->replicate(['schedule_template_id']);
            $newItem->schedule_template_id = $targetTemplate->id;
            $newItem->save();
        }

        if ($targetTemplate->status === 'published') {
            $targetTemplate->update(['status' => 'draft']);
        }

        SiteCache::forgetMenu('header_primary');
    }

    public function publishTemplate(ScheduleTemplate $template, ?int $userId = null, ?string $summary = null): ScheduleVersionHistory
    {
        SiteCache::forgetMenu('header_primary');
        return $template->publish($userId, $summary);
    }
}
