<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('youtube:sync-playlists')
    ->hourly()
    ->withoutOverlapping();

Schedule::command('audit:prune')
    ->daily()
    ->withoutOverlapping();

Schedule::command('analytics:prune')
    ->daily()
    ->withoutOverlapping();

Schedule::job(new \App\Jobs\SyncGa4MetricsJob)
    ->hourly()
    ->withoutOverlapping();

Schedule::job(new \App\Jobs\SyncGoogleAdsMetricsJob)
    ->hourly()
    ->withoutOverlapping();

Schedule::job(new \App\Jobs\SyncMetaAdsMetricsJob)
    ->hourly()
    ->withoutOverlapping();

Schedule::job(new \App\Jobs\EvaluateAnalyticsAlertsJob)
    ->hourly()
    ->withoutOverlapping();


