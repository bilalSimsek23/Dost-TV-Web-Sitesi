<?php

namespace App\Services\Analytics;

use App\Models\AnalyticsIntegration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class GoogleAdsDataFetchService
{
    /**
     * Synchronizes Google Ads metrics into analytics_integrations table for 'google_ads' provider.
     */
    public function syncMetrics(): bool
    {
        $integration = AnalyticsIntegration::query()->firstOrCreate(
            ['provider' => 'google_ads'],
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
     * Tests Google Ads API connection using given Customer ID and credentials JSON.
     */
    public function testConnection(string $customerId, ?string $loginCustomerId, string $credentialsJson): array
    {
        try {
            $creds = is_array($credentialsJson) ? $credentialsJson : json_decode($credentialsJson, true);
            if (! is_array($creds)) {
                return [
                    'success' => false,
                    'message' => 'Geçersiz JSON formatı. Lütfen geçerli Google Ads kimlik bilgilerini girin.',
                ];
            }

            $developerToken = $creds['developer_token'] ?? null;
            if (! $developerToken) {
                return [
                    'success' => false,
                    'message' => 'Developer Token eksik.',
                ];
            }

            $token = $this->getAccessToken($creds);
            $cleanCustomerId = preg_replace('/[^0-9]/', '', $customerId);

            $headers = [
                'developer-token' => $developerToken,
            ];

            $loginId = $loginCustomerId ?: ($creds['login_customer_id'] ?? null);
            if (filled($loginId)) {
                $headers['login-customer-id'] = preg_replace('/[^0-9]/', '', $loginId);
            }

            $response = Http::withToken($token)
                ->withHeaders($headers)
                ->post("https://googleads.googleapis.com/v19/customers/{$cleanCustomerId}/googleAds:search", [
                    'query' => 'SELECT customer.id, customer.descriptive_name, customer.currency_code FROM customer LIMIT 1',
                ]);

            if (! $response->successful()) {
                $err = $response->json('error.message') ?? $response->body();

                return [
                    'success' => false,
                    'message' => 'Google Ads API Hatası: '.$this->sanitizeError($err),
                ];
            }

            return [
                'success' => true,
                'message' => 'Google Ads bağlantısı başarılı.',
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Bağlantı hatası: '.$this->sanitizeError($e->getMessage()),
            ];
        }
    }

    /**
     * Fetches multi-range Google Ads metrics (7, 30, 90 days).
     */
    public function fetchMultiRangeMetrics(string $customerId, string $credentialsJson): array
    {
        $creds = is_array($credentialsJson) ? $credentialsJson : json_decode($credentialsJson, true);
        if (! is_array($creds)) {
            throw new \RuntimeException('Geçersiz Google Ads kimlik formatı.');
        }

        $developerToken = $creds['developer_token'] ?? null;
        if (! $developerToken) {
            throw new \RuntimeException('Developer Token zorunludur.');
        }

        $token = $this->getAccessToken($creds);
        $cleanCustomerId = preg_replace('/[^0-9]/', '', $customerId);
        $loginId = $creds['login_customer_id'] ?? null;

        $ranges = [
            '7' => [now()->subDays(7)->format('Y-m-d'), now()->subDay()->format('Y-m-d')],
            '30' => [now()->subDays(30)->format('Y-m-d'), now()->subDay()->format('Y-m-d')],
            '90' => [now()->subDays(90)->format('Y-m-d'), now()->subDay()->format('Y-m-d')],
        ];

        $snapshot = [];

        foreach ($ranges as $key => [$start, $end]) {
            $snapshot[$key] = $this->fetchRangeData($token, $cleanCustomerId, $loginId, $developerToken, $start, $end);
        }

        return $snapshot;
    }

    /**
     * Fetches single range performance data from Google Ads API.
     */
    protected function fetchRangeData(
        string $token,
        string $customerId,
        ?string $loginCustomerId,
        string $developerToken,
        string $startDate,
        string $endDate
    ): array {
        $headers = [
            'developer-token' => $developerToken,
        ];
        if (filled($loginCustomerId)) {
            $headers['login-customer-id'] = preg_replace('/[^0-9]/', '', $loginCustomerId);
        }

        // Query 1: Campaign details & aggregate metrics
        $campQuery = "SELECT campaign.id, campaign.name, campaign.status, customer.currency_code, metrics.impressions, metrics.clicks, metrics.cost_micros, metrics.conversions, metrics.conversions_value, metrics.ctr, metrics.average_cpc FROM campaign WHERE segments.date BETWEEN '{$startDate}' AND '{$endDate}'";

        $responseCamp = Http::withToken($token)
            ->withHeaders($headers)
            ->post("https://googleads.googleapis.com/v19/customers/{$customerId}/googleAds:search", [
                'query' => $campQuery,
            ]);

        if (! $responseCamp->successful()) {
            $err = $responseCamp->json('error.message') ?? $responseCamp->body();
            throw new \RuntimeException('Google Ads Kampanya Rapor Hatası: '.$this->sanitizeError($err));
        }

        $campResults = $responseCamp->json('results') ?? [];

        $totalImpressions = 0;
        $totalClicks = 0;
        $totalCostMicros = 0;
        $totalConversions = 0.0;
        $totalConversionValue = 0.0;
        $currency = 'TRY';

        $campaigns = [];

        foreach ($campResults as $row) {
            $camp = $row['campaign'] ?? [];
            $met = $row['metrics'] ?? [];
            $cust = $row['customer'] ?? [];

            if (! empty($cust['currencyCode'])) {
                $currency = $cust['currencyCode'];
            }

            $imp = (int) ($met['impressions'] ?? 0);
            $clk = (int) ($met['clicks'] ?? 0);
            $costMicros = (float) ($met['costMicros'] ?? 0);
            $conv = (float) ($met['conversions'] ?? 0);
            $convVal = (float) ($met['conversionsValue'] ?? 0);

            $totalImpressions += $imp;
            $totalClicks += $clk;
            $totalCostMicros += $costMicros;
            $totalConversions += $conv;
            $totalConversionValue += $convVal;

            $campCost = round($costMicros / 1000000.0, 2);
            $campCtr = $imp > 0 ? round(($clk / $imp) * 100, 2) : 0;
            $campCpc = $clk > 0 ? round($campCost / $clk, 2) : 0;

            $status = $camp['status'] ?? 'UNKNOWN';
            $statusLabel = match ($status) {
                'ENABLED' => 'Aktif',
                'PAUSED' => 'Duraklatıldı',
                'REMOVED' => 'Kaldırıldı',
                default => Str::headline($status),
            };

            $campaigns[] = [
                'id' => $camp['id'] ?? '',
                'name' => $camp['name'] ?? 'Bilinmeyen Kampanya',
                'status' => $status,
                'status_label' => $statusLabel,
                'impressions' => $imp,
                'clicks' => $clk,
                'cost' => $campCost,
                'conversions' => round($conv, 2),
                'conversion_value' => round($convVal, 2),
                'ctr' => $campCtr,
                'average_cpc' => $campCpc,
            ];
        }

        // Query 2: Daily Timeseries
        $timeQuery = "SELECT segments.date, metrics.impressions, metrics.clicks, metrics.cost_micros, metrics.conversions FROM campaign WHERE segments.date BETWEEN '{$startDate}' AND '{$endDate}' ORDER BY segments.date ASC";

        $responseTime = Http::withToken($token)
            ->withHeaders($headers)
            ->post("https://googleads.googleapis.com/v19/customers/{$customerId}/googleAds:search", [
                'query' => $timeQuery,
            ]);

        $timeResults = $responseTime->successful() ? ($responseTime->json('results') ?? []) : [];

        $timeseriesByDate = [];
        foreach ($timeResults as $row) {
            $date = $row['segments']['date'] ?? '';
            if (blank($date)) {
                continue;
            }

            $met = $row['metrics'] ?? [];
            $imp = (int) ($met['impressions'] ?? 0);
            $clk = (int) ($met['clicks'] ?? 0);
            $costMicros = (float) ($met['costMicros'] ?? 0);
            $conv = (float) ($met['conversions'] ?? 0);

            if (! isset($timeseriesByDate[$date])) {
                $timeseriesByDate[$date] = [
                    'date' => $date,
                    'impressions' => 0,
                    'clicks' => 0,
                    'cost' => 0.0,
                    'conversions' => 0.0,
                ];
            }

            $timeseriesByDate[$date]['impressions'] += $imp;
            $timeseriesByDate[$date]['clicks'] += $clk;
            $timeseriesByDate[$date]['cost'] += round($costMicros / 1000000.0, 2);
            $timeseriesByDate[$date]['conversions'] += round($conv, 2);
        }

        ksort($timeseriesByDate);
        $timeseries = array_values($timeseriesByDate);

        $totalCost = round($totalCostMicros / 1000000.0, 2);
        $overallCtr = $totalImpressions > 0 ? round(($totalClicks / $totalImpressions) * 100, 2) : 0;
        $overallCpc = $totalClicks > 0 ? round($totalCost / $totalClicks, 2) : 0;

        return [
            'impressions' => $totalImpressions,
            'clicks' => $totalClicks,
            'cost' => $totalCost,
            'currency' => $currency,
            'conversions' => round($totalConversions, 2),
            'conversion_value' => round($totalConversionValue, 2),
            'ctr' => $overallCtr,
            'average_cpc' => $overallCpc,
            'timeseries' => $timeseries,
            'campaigns' => $campaigns,
        ];
    }

    /**
     * Generates an OAuth2 Access Token for Google APIs using refresh token credentials.
     */
    public function getAccessToken(array $credentials): string
    {
        $clientId = $credentials['client_id'] ?? null;
        $clientSecret = $credentials['client_secret'] ?? null;
        $refreshToken = $credentials['refresh_token'] ?? null;

        if (! $clientId || ! $clientSecret || ! $refreshToken) {
            throw new \InvalidArgumentException('Google Ads OAuth bilgileri eksik: Client ID, Client Secret ve Refresh Token zorunludur.');
        }

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'refresh_token',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'refresh_token' => $refreshToken,
        ]);

        if (! $response->successful()) {
            $err = $response->json('error_description') ?? $response->json('error') ?? $response->body();
            throw new \RuntimeException('Google Ads OAuth erişim jetonu alınamadı: '.$this->sanitizeError($err));
        }

        $token = $response->json('access_token');
        if (! $token) {
            throw new \RuntimeException('Google OAuth sunucusundan erişim jetonu dönmedi.');
        }

        return $token;
    }

    /**
     * Sanitizes sensitive data (tokens, secrets, private keys) from error messages.
     */
    public function sanitizeError(string $message): string
    {
        $message = preg_replace('/-----BEGIN [A-Z ]+-----[^\-]+-----END [A-Z ]+-----/s', '[REDACTED_PRIVATE_KEY]', $message);
        $message = preg_replace('/"private_key":\s*"[^"]+"/', '"private_key": "[REDACTED]"', $message);
        $message = preg_replace('/"client_secret":\s*"[^"]+"/', '"client_secret": "[REDACTED]"', $message);
        $message = preg_replace('/"refresh_token":\s*"[^"]+"/', '"refresh_token": "[REDACTED]"', $message);
        $message = preg_replace('/"developer_token":\s*"[^"]+"/', '"developer_token": "[REDACTED]"', $message);
        $message = preg_replace('/developer-token:\s*[\w\-\.\=]+/i', 'developer-token: [REDACTED]', $message);
        $message = preg_replace('/GOCSXX-[a-zA-Z0-9_\-]+/i', '[REDACTED_CLIENT_SECRET]', $message);
        $message = preg_replace('/secret\s+[a-zA-Z0-9_\-]+/i', 'secret [REDACTED_SECRET]', $message);
        $message = preg_replace('/ya29\.[a-zA-Z0-9_\-]+/', '[REDACTED_TOKEN]', $message);
        $message = preg_replace('/1\/\/[a-zA-Z0-9_\-]+/', '[REDACTED_REFRESH_TOKEN]', $message);

        return Str::limit($message, 300);
    }
}
