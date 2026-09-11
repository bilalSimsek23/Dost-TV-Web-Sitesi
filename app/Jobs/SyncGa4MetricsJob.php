<?php

namespace App\Jobs;

use App\Services\Analytics\Ga4DataFetchService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncGa4MetricsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(Ga4DataFetchService $ga4Service): void
    {
        $ga4Service->syncMetrics();
    }
}
