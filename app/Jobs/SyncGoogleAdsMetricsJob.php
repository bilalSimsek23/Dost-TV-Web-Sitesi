<?php

namespace App\Jobs;

use App\Services\Analytics\GoogleAdsDataFetchService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncGoogleAdsMetricsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(GoogleAdsDataFetchService $service): void
    {
        $service->syncMetrics();
        \App\Services\Analytics\AnalyticsInsightService::clearCache();
    }
}
