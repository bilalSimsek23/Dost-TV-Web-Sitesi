<?php

namespace App\Jobs;

use App\Services\Analytics\AnalyticsAlertService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class EvaluateAnalyticsAlertsJob implements ShouldQueue
{
    use Queueable;

    public function handle(AnalyticsAlertService $alertService): void
    {
        try {
            $result = $alertService->evaluateAlerts(30, false);
            Log::info('EvaluateAnalyticsAlertsJob completed', $result);
        } catch (\Throwable $e) {
            Log::error('EvaluateAnalyticsAlertsJob failed: '.$e->getMessage());
        }
    }
}
