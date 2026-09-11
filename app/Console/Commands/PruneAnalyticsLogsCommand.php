<?php

namespace App\Console\Commands;

use App\Models\AnalyticsAlert;
use App\Models\NotFoundLog;
use App\Models\SearchLog;
use App\Models\SiteEvent;
use Illuminate\Console\Command;

class PruneAnalyticsLogsCommand extends Command
{
    protected $signature = 'analytics:prune {--days-searches=180} {--days-events=180} {--days-not-found=365} {--days-alerts=180}';

    protected $description = 'Prune analytics search logs, site events, not found logs, and resolved alerts beyond retention limits';

    public function handle(): int
    {
        $searchCutoff = now()->subDays((int) $this->option('days-searches'));
        $eventCutoff = now()->subDays((int) $this->option('days-events'));
        $notFoundCutoff = now()->subDays((int) $this->option('days-not-found'));
        $alertRetentionDays = (int) config('analytics.alerts.retention_days', (int) $this->option('days-alerts'));
        $alertCutoff = now()->subDays($alertRetentionDays);

        $prunedSearches = 0;
        do {
            $ids = SearchLog::query()->where('searched_at', '<', $searchCutoff)->limit(1000)->pluck('id');
            if ($ids->isEmpty()) {
                break;
            }
            $deleted = SearchLog::query()->whereIn('id', $ids)->delete();
            $prunedSearches += $deleted;
        } while ($deleted > 0);

        $prunedEvents = 0;
        do {
            $ids = SiteEvent::query()->where('occurred_at', '<', $eventCutoff)->limit(1000)->pluck('id');
            if ($ids->isEmpty()) {
                break;
            }
            $deleted = SiteEvent::query()->whereIn('id', $ids)->delete();
            $prunedEvents += $deleted;
        } while ($deleted > 0);

        $prunedNotFound = 0;
        do {
            $ids = NotFoundLog::query()->where('last_occurred_at', '<', $notFoundCutoff)->limit(1000)->pluck('id');
            if ($ids->isEmpty()) {
                break;
            }
            $deleted = NotFoundLog::query()->whereIn('id', $ids)->delete();
            $prunedNotFound += $deleted;
        } while ($deleted > 0);

        $prunedAlerts = 0;
        do {
            $ids = AnalyticsAlert::query()
                ->whereIn('status', ['resolved', 'dismissed'])
                ->where('updated_at', '<', $alertCutoff)
                ->limit(1000)
                ->pluck('id');
            if ($ids->isEmpty()) {
                break;
            }
            $deleted = AnalyticsAlert::query()->whereIn('id', $ids)->delete();
            $prunedAlerts += $deleted;
        } while ($deleted > 0);

        $this->info("Analytics logs pruned: {$prunedSearches} search logs, {$prunedEvents} site events, {$prunedNotFound} 404 logs, {$prunedAlerts} alerts.");

        return self::SUCCESS;
    }
}

