<?php

namespace App\Services\Analytics;

use App\Models\AnalyticsIntegration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class MetaAdsDataFetchService
{
    /**
     * Graph API version to use.
     */
    protected function getApiVersion(): string
    {
        return config('services.meta_ads.graph_version', 'v22.0');
    }

    /**
     * Synchronizes Meta Ads metrics into analytics_integrations table for 'meta_ads' provider.
     */
    public function syncMetrics(): bool
    {
        $integration = AnalyticsIntegration::query()->firstOrCreate(
            ['provider' => 'meta_ads'],
            ['is_enabled' => false]
        );

        if (! $integration->is_enabled || blank($integration->property_id) || blank($integration->credentials)) {
            return false;
        }

        try {
            $snapshot = $this->fetchMultiRangeMetrics(
                $integration->property_id,
                $integration->credentials
            );

            $integration->update([
                'last_synced_at' => now(),
                'last_error' => null,
                'metrics_snapshot' => $snapshot,
            ]);

            return true;
        } catch (Throwable $e) {
            $integration->update([
                'last_error' => $this->sanitizeError($e->getMessage()),
            ]);

            return false;
        }
    }

    /**
     * Tests Meta Marketing API connection using given Ad Account ID and credentials.
     */
    public function testConnection(string $adAccountId, string $credentialsJson): array
    {
        try {
            $creds = is_array($credentialsJson) ? $credentialsJson : json_decode($credentialsJson, true);
            if (! is_array($creds)) {
                return [
                    'success' => false,
                    'message' => 'Geçersiz JSON formatı. Lütfen geçerli Meta Ads kimlik bilgilerini girin.',
                ];
            }

            $accessToken = $creds['access_token'] ?? null;
            if (blank($accessToken)) {
                return [
                    'success' => false,
                    'message' => 'Meta Access Token eksik.',
                ];
            }

            $cleanId = preg_replace('/^act_/', '', trim($adAccountId));
            $version = $this->getApiVersion();

            $response = Http::get("https://graph.facebook.com/{$version}/act_{$cleanId}", [
                'fields' => 'id,name,currency,account_status,business_name',
                'access_token' => $accessToken,
            ]);

            if (! $response->successful()) {
                $err = $this->parseMetaApiError($response);

                return [
                    'success' => false,
                    'message' => 'Meta Graph API Hatası: '.$this->sanitizeError($err),
                ];
            }

            $accountData = $response->json();
            $accountName = $accountData['name'] ?? "act_{$cleanId}";
            $currency = $accountData['currency'] ?? 'TRY';

            return [
                'success' => true,
                'message' => "Meta Ads bağlantısı başarılı. (Hesap: {$accountName}, Para Birimi: {$currency})",
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Bağlantı testi başarısız: '.$this->sanitizeError($e->getMessage()),
            ];
        }
    }

    /**
     * Fetches metrics for 7, 30, and 90 day ranges.
     */
    public function fetchMultiRangeMetrics(string $adAccountId, mixed $credentials): array
    {
        $creds = is_array($credentials) ? $credentials : (json_decode($credentials, true) ?? []);
        $accessToken = $creds['access_token'] ?? null;

        if (blank($accessToken)) {
            throw new \InvalidArgumentException('Meta Access Token bulunamadı.');
        }

        $cleanId = preg_replace('/^act_/', '', trim($adAccountId));

        $snapshot = [];
        foreach (['7', '30', '90'] as $days) {
            $startDate = now()->subDays((int) $days)->format('Y-m-d');
            $endDate = now()->subDay()->format('Y-m-d');

            $snapshot[$days] = $this->fetchRangeData($cleanId, $accessToken, $startDate, $endDate);
        }

        return $snapshot;
    }

    /**
     * Fetches data for a single date range from Meta Marketing API.
     */
    public function fetchRangeData(string $cleanAdAccountId, string $accessToken, string $startDate, string $endDate): array
    {
        $version = $this->getApiVersion();
        $timeRangeParam = json_encode(['since' => $startDate, 'until' => $endDate]);

        // 1. Account Info for Currency
        $accResponse = Http::get("https://graph.facebook.com/{$version}/act_{$cleanAdAccountId}", [
            'fields' => 'id,name,currency',
            'access_token' => $accessToken,
        ]);
        $currency = $accResponse->successful() ? ($accResponse->json('currency') ?? 'TRY') : 'TRY';

        // 2. Account Overview Insights
        $overviewResponse = Http::get("https://graph.facebook.com/{$version}/act_{$cleanAdAccountId}/insights", [
            'time_range' => $timeRangeParam,
            'fields' => 'spend,impressions,reach,clicks,ctr,cpc,cpm,frequency,actions,action_values',
            'access_token' => $accessToken,
        ]);

        if (! $overviewResponse->successful()) {
            $err = $this->parseMetaApiError($overviewResponse);
            throw new \RuntimeException('Meta Insights çekilemedi: '.$err);
        }

        $overviewData = $overviewResponse->json('data.0') ?? [];

        $spend = (float) ($overviewData['spend'] ?? 0);
        $impressions = (int) ($overviewData['impressions'] ?? 0);
        $reach = (int) ($overviewData['reach'] ?? 0);
        $clicks = (int) ($overviewData['clicks'] ?? 0);
        $ctr = (float) ($overviewData['ctr'] ?? ($impressions > 0 ? round(($clicks / $impressions) * 100, 2) : 0));
        $cpc = (float) ($overviewData['cpc'] ?? ($clicks > 0 ? round($spend / $clicks, 2) : 0));
        $cpm = (float) ($overviewData['cpm'] ?? ($impressions > 0 ? round(($spend / $impressions) * 1000, 2) : 0));
        $frequency = (float) ($overviewData['frequency'] ?? ($reach > 0 ? round($impressions / $reach, 2) : 0));

        $actions = $overviewData['actions'] ?? [];
        $actionValues = $overviewData['action_values'] ?? [];

        $linkClicks = $this->extractActionValue($actions, 'link_click') ?: $clicks;
        $conversions = $this->extractConversionsCount($actions);
        $conversionValue = $this->extractConversionsValue($actionValues);

        // 3. Daily Timeseries
        $timeResponse = Http::get("https://graph.facebook.com/{$version}/act_{$cleanAdAccountId}/insights", [
            'time_range' => $timeRangeParam,
            'time_increment' => 1,
            'fields' => 'spend,impressions,reach,clicks,actions',
            'access_token' => $accessToken,
        ]);

        $timeData = $timeResponse->successful() ? ($timeResponse->json('data') ?? []) : [];
        $timeseries = [];

        foreach ($timeData as $row) {
            $date = $row['date_start'] ?? '';
            if (blank($date)) {
                continue;
            }

            $timeseries[] = [
                'date' => $date,
                'spend' => (float) ($row['spend'] ?? 0),
                'impressions' => (int) ($row['impressions'] ?? 0),
                'reach' => (int) ($row['reach'] ?? 0),
                'clicks' => (int) ($row['clicks'] ?? 0),
                'conversions' => $this->extractConversionsCount($row['actions'] ?? []),
            ];
        }

        // 4. Campaign Statuses Map
        $campStatusResponse = Http::get("https://graph.facebook.com/{$version}/act_{$cleanAdAccountId}/campaigns", [
            'fields' => 'id,name,status,effective_status',
            'access_token' => $accessToken,
        ]);

        $campStatusMap = [];
        if ($campStatusResponse->successful()) {
            foreach ($campStatusResponse->json('data') ?? [] as $cObj) {
                if (! empty($cObj['id'])) {
                    $campStatusMap[$cObj['id']] = $cObj['effective_status'] ?? $cObj['status'] ?? 'UNKNOWN';
                }
            }
        }

        // 5. Campaign Level Insights
        $campResponse = Http::get("https://graph.facebook.com/{$version}/act_{$cleanAdAccountId}/insights", [
            'level' => 'campaign',
            'time_range' => $timeRangeParam,
            'fields' => 'campaign_id,campaign_name,spend,impressions,reach,clicks,ctr,cpc,cpm,frequency,actions',
            'access_token' => $accessToken,
        ]);

        $campData = $campResponse->successful() ? ($campResponse->json('data') ?? []) : [];
        $campaigns = [];

        foreach ($campData as $cRow) {
            $cId = $cRow['campaign_id'] ?? '';
            $cName = $cRow['campaign_name'] ?? 'Bilinmeyen Kampanya';
            $cSpend = (float) ($cRow['spend'] ?? 0);
            $cImp = (int) ($cRow['impressions'] ?? 0);
            $cReach = (int) ($cRow['reach'] ?? 0);
            $cClk = (int) ($cRow['clicks'] ?? 0);
            $cCtr = (float) ($cRow['ctr'] ?? ($cImp > 0 ? round(($cClk / $cImp) * 100, 2) : 0));
            $cCpc = (float) ($cRow['cpc'] ?? ($cClk > 0 ? round($cSpend / $cClk, 2) : 0));
            $cCpm = (float) ($cRow['cpm'] ?? ($cImp > 0 ? round(($cSpend / $cImp) * 1000, 2) : 0));
            $cFreq = (float) ($cRow['frequency'] ?? ($cReach > 0 ? round($cImp / $cReach, 2) : 0));
            $cConv = $this->extractConversionsCount($cRow['actions'] ?? []);

            $status = $campStatusMap[$cId] ?? 'ACTIVE';
            $statusLabel = match ($status) {
                'ACTIVE' => 'Aktif',
                'PAUSED' => 'Duraklatıldı',
                'ARCHIVED' => 'Arşivlendi',
                'DELETED' => 'Silindi',
                default => Str::headline($status),
            };

            $campaigns[] = [
                'id' => $cId,
                'name' => $cName,
                'status' => $status,
                'status_label' => $statusLabel,
                'spend' => $cSpend,
                'impressions' => $cImp,
                'reach' => $cReach,
                'clicks' => $cClk,
                'ctr' => round($cCtr, 2),
                'cpc' => round($cCpc, 2),
                'cpm' => round($cCpm, 2),
                'frequency' => round($cFreq, 2),
                'conversions' => $cConv,
            ];
        }

        return [
            'spend' => round($spend, 2),
            'currency' => $currency,
            'impressions' => $impressions,
            'reach' => $reach,
            'clicks' => $clicks,
            'link_clicks' => $linkClicks,
            'ctr' => round($ctr, 2),
            'cpc' => round($cpc, 2),
            'cpm' => round($cpm, 2),
            'frequency' => round($frequency, 2),
            'conversions' => $conversions,
            'conversion_value' => round($conversionValue, 2),
            'timeseries' => $timeseries,
            'campaigns' => $campaigns,
        ];
    }

    /**
     * Extracts numerical value for a specific action_type from Meta actions array.
     */
    protected function extractActionValue(array $actions, string $actionType): int
    {
        foreach ($actions as $act) {
            if (($act['action_type'] ?? '') === $actionType) {
                return (int) ($act['value'] ?? 0);
            }
        }

        return 0;
    }

    /**
     * Computes total conversions by summing conversion-related action types.
     */
    protected function extractConversionsCount(array $actions): int
    {
        $conversionTypes = [
            'purchase',
            'lead',
            'complete_registration',
            'landing_page_view',
            'offsite_conversion.fb_pixel_purchase',
            'offsite_conversion.fb_pixel_lead',
            'omni_purchase',
            'omni_complete_registration',
        ];

        $total = 0;
        foreach ($actions as $act) {
            $type = $act['action_type'] ?? '';
            if (in_array($type, $conversionTypes, true) || str_contains($type, 'conversion')) {
                $total += (int) ($act['value'] ?? 0);
            }
        }

        return $total;
    }

    /**
     * Computes total conversion value from Meta action_values array.
     */
    protected function extractConversionsValue(array $actionValues): float
    {
        $conversionTypes = [
            'purchase',
            'offsite_conversion.fb_pixel_purchase',
            'omni_purchase',
        ];

        $total = 0.0;
        foreach ($actionValues as $act) {
            $type = $act['action_type'] ?? '';
            if (in_array($type, $conversionTypes, true) || str_contains($type, 'purchase')) {
                $total += (float) ($act['value'] ?? 0);
            }
        }

        return $total;
    }

    /**
     * Parses human-readable Meta Graph API error response.
     */
    protected function parseMetaApiError(\Illuminate\Http\Client\Response $response): string
    {
        $errObj = $response->json('error');
        if (is_array($errObj)) {
            $msg = $errObj['message'] ?? 'Bilinmeyen Graph API hatası.';
            $code = $errObj['code'] ?? null;

            if ($code === 190) {
                return 'Access token süresi dolmuş veya geçersiz. Lütfen yenileyin. (Code 190)';
            }
            if ($code === 200 || $code === 294) {
                return 'Reklam hesabına erişim izni bulunmuyor veya Reklam Hesabı ID hatalı. (Code '.$code.')';
            }
            if ($code === 17 || $code === 613) {
                return 'Meta Marketing API istek limiti (rate limit) aşıldı. Lütfen bir süre bekleyin. (Code '.$code.')';
            }

            return $msg.' (Code '.($code ?? 'N/A').')';
        }

        return $response->body();
    }

    /**
     * Sanitizes sensitive tokens and secrets from error strings.
     */
    public function sanitizeError(string $message): string
    {
        $message = preg_replace('/-----BEGIN [A-Z ]+-----[^\-]+-----END [A-Z ]+-----/s', '[REDACTED_PRIVATE_KEY]', $message);
        $message = preg_replace('/"private_key":\s*"[^"]+"/', '"private_key": "[REDACTED]"', $message);
        $message = preg_replace('/"app_secret":\s*"[^"]+"/', '"app_secret": "[REDACTED]"', $message);
        $message = preg_replace('/"access_token":\s*"[^"]+"/', '"access_token": "[REDACTED]"', $message);
        $message = preg_replace('/access_token=[^&\s]+/i', 'access_token=[REDACTED_TOKEN]', $message);
        $message = preg_replace('/EAA[a-zA-Z0-9_\-]+/i', '[REDACTED_META_TOKEN]', $message);
        $message = preg_replace('/GOCSXX-[a-zA-Z0-9_\-]+/i', '[REDACTED_CLIENT_SECRET]', $message);
        $message = preg_replace('/1\/\/[a-zA-Z0-9_\-]+/', '[REDACTED_REFRESH_TOKEN]', $message);

        return Str::limit($message, 300);
    }
}
