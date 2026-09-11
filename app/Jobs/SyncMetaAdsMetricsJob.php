<?php

namespace App\Jobs;

use App\Services\Analytics\MetaAdsDataFetchService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncMetaAdsMetricsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(MetaAdsDataFetchService $service): void
    {
        $service->syncMetrics();
        \App\Services\Analytics\AnalyticsInsightService::clearCache();
    }
}

