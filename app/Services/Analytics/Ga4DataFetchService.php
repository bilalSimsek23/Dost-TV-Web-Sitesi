<?php

namespace App\Services\Analytics;

use App\Models\AnalyticsIntegration;
use App\Models\Program;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class Ga4DataFetchService
{
    /**
     * Synchronizes GA4 metrics into analytics_integrations table for 'ga4' provider.
     */
    public function syncMetrics(): bool
    {
        $integration = AnalyticsIntegration::query()->firstOrCreate(
            ['provider' => 'ga4'],
            ['is_enabled' => false]
        );

        if (! $integration->is_enabled || blank($integration->property_id) || blank($integration->credentials)) {
            return false;
        }

        try {
            $snapshot = $this->fetchMultiRangeMetrics($integration->property_id, $integration->credentials);

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
     * Tests GA4 API connection using given Property ID and credentials JSON.
     */
    public function testConnection(string $propertyId, string $credentialsJson): array
    {
        try {
            $creds = json_decode($credentialsJson, true);
            if (! is_array($creds)) {
                return [
                    'success' => false,
                    'message' => 'Geçersiz JSON formatı. Lütfen geçerli bir Service Account JSON anahtarı girin.',
                ];
            }

            $token = $this->getAccessToken($creds);

            $response = Http::withToken($token)
                ->post("https://analyticsdata.googleapis.com/v1beta/properties/{$propertyId}:runReport", [
                    'dateRanges' => [['startDate' => 'yesterday', 'endDate' => 'today']],
                    'metrics' => [['name' => 'activeUsers']],
                    'limit' => 1,
                ]);

            if (! $response->successful()) {
                $err = $response->json('error.message') ?? $response->body();

                return [
                    'success' => false,
                    'message' => 'Google Analytics Data API Hatası: '.$this->sanitizeError($err),
                ];
            }

            return [
                'success' => true,
                'message' => 'Google Analytics 4 bağlantısı başarılı.',
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Bağlantı hatası: '.$this->sanitizeError($e->getMessage()),
            ];
        }
    }

    /**
     * Fetches multi-range GA4 metrics (7, 30, 90 days).
     */
    public function fetchMultiRangeMetrics(string $propertyId, string $credentialsJson): array
    {
        $creds = json_decode($credentialsJson, true);
        if (! is_array($creds)) {
            throw new \RuntimeException('Geçersiz GA4 Service Account JSON formatı.');
        }

        $token = $this->getAccessToken($creds);
        $programsBySlug = Program::query()->pluck('name', 'slug')->toArray();

        $ranges = [
            '7' => '7daysAgo',
            '30' => '30daysAgo',
            '90' => '90daysAgo',
        ];

        $snapshot = [];

        foreach ($ranges as $key => $startDate) {
            $snapshot[$key] = $this->fetchRangeData($token, $propertyId, $startDate, $programsBySlug);
        }

        return $snapshot;
    }

    /**
     * Fetches single range data using batchRunReports.
     */
    protected function fetchRangeData(string $token, string $propertyId, string $startDate, array $programsBySlug): array
    {
        $response = Http::withToken($token)
            ->post("https://analyticsdata.googleapis.com/v1beta/properties/{$propertyId}:batchRunReports", [
                'requests' => [
                    // Request 0: Overall KPIs
                    [
                        'dateRanges' => [['startDate' => $startDate, 'endDate' => 'yesterday']],
                        'metrics' => [
                            ['name' => 'totalUsers'],
                            ['name' => 'activeUsers'],
                            ['name' => 'sessions'],
                            ['name' => 'screenPageViews'],
                            ['name' => 'averageSessionDuration'],
                        ],
                    ],
                    // Request 1: Daily Timeseries
                    [
                        'dateRanges' => [['startDate' => $startDate, 'endDate' => 'yesterday']],
                        'dimensions' => [['name' => 'date']],
                        'metrics' => [
                            ['name' => 'totalUsers'],
                            ['name' => 'screenPageViews'],
                        ],
                        'orderBys' => [
                            ['dimension' => ['dimensionName' => 'date'], 'desc' => false],
                        ],
                    ],
                    // Request 2: Device Breakdown
                    [
                        'dateRanges' => [['startDate' => $startDate, 'endDate' => 'yesterday']],
                        'dimensions' => [['name' => 'deviceCategory']],
                        'metrics' => [
                            ['name' => 'activeUsers'],
                        ],
                    ],
                    // Request 3: Traffic Channel Group
                    [
                        'dateRanges' => [['startDate' => $startDate, 'endDate' => 'yesterday']],
                        'dimensions' => [['name' => 'sessionDefaultChannelGroup']],
                        'metrics' => [
                            ['name' => 'sessions'],
                        ],
                        'orderBys' => [
                            ['metric' => ['metricName' => 'sessions'], 'desc' => true],
                        ],
                        'limit' => 5,
                    ],
                    // Request 4: Top Pages
                    [
                        'dateRanges' => [['startDate' => $startDate, 'endDate' => 'yesterday']],
                        'dimensions' => [
                            ['name' => 'pagePath'],
                            ['name' => 'pageTitle'],
                        ],
                        'metrics' => [
                            ['name' => 'screenPageViews'],
                        ],
                        'orderBys' => [
                            ['metric' => ['metricName' => 'screenPageViews'], 'desc' => true],
                        ],
                        'limit' => 25,
                    ],
                ],
            ]);

        if (! $response->successful()) {
            $err = $response->json('error.message') ?? $response->body();
            throw new \RuntimeException('GA4 API Rapor Hatası: '.$this->sanitizeError($err));
        }

        $reports = $response->json('reports') ?? [];

        // 1. KPI Parser
        $kpiRows = $reports[0]['rows'][0]['metricValues'] ?? [];
        $totalUsers = (int) ($kpiRows[0]['value'] ?? 0);
        $activeUsers = (int) ($kpiRows[1]['value'] ?? 0);
        $sessions = (int) ($kpiRows[2]['value'] ?? 0);
        $pageviews = (int) ($kpiRows[3]['value'] ?? 0);
        $avgDurationSecs = (float) ($kpiRows[4]['value'] ?? 0);

        // 2. Timeseries Parser
        $timeseries = [];
        foreach ($reports[1]['rows'] ?? [] as $row) {
            $dateStr = $row['dimensionValues'][0]['value'] ?? '';
            // Format YYYYMMDD to YYYY-MM-DD
            $formattedDate = strlen($dateStr) === 8
                ? substr($dateStr, 0, 4).'-'.substr($dateStr, 4, 2).'-'.substr($dateStr, 6, 2)
                : $dateStr;

            $timeseries[] = [
                'date' => $formattedDate,
                'users' => (int) ($row['metricValues'][0]['value'] ?? 0),
                'pageviews' => (int) ($row['metricValues'][1]['value'] ?? 0),
            ];
        }

        // 3. Device Parser
        $devicesRaw = [];
        $totalDeviceUsers = 0;
        foreach ($reports[2]['rows'] ?? [] as $row) {
            $category = strtolower($row['dimensionValues'][0]['value'] ?? 'other');
            $userCount = (int) ($row['metricValues'][0]['value'] ?? 0);
            $devicesRaw[$category] = $userCount;
            $totalDeviceUsers += $userCount;
        }

        $devices = [];
        if ($totalDeviceUsers > 0) {
            $mobPct = round((($devicesRaw['mobile'] ?? 0) / $totalDeviceUsers) * 100);
            $deskPct = round((($devicesRaw['desktop'] ?? 0) / $totalDeviceUsers) * 100);
            $tabPct = max(0, 100 - ($mobPct + $deskPct));

            $devices = [
                'mobile' => ['users' => $devicesRaw['mobile'] ?? 0, 'percentage' => $mobPct],
                'desktop' => ['users' => $devicesRaw['desktop'] ?? 0, 'percentage' => $deskPct],
                'tablet' => ['users' => $devicesRaw['tablet'] ?? 0, 'percentage' => $tabPct],
                'total_users' => $totalDeviceUsers,
            ];
        }

        // 4. Traffic Sources Parser
        $trafficSources = [];
        foreach ($reports[3]['rows'] ?? [] as $row) {
            $trafficSources[] = [
                'source' => $row['dimensionValues'][0]['value'] ?? 'Direct',
                'sessions' => (int) ($row['metricValues'][0]['value'] ?? 0),
            ];
        }

        // 5. Top Pages & Program Mapping Parser
        $topPages = [];
        $topPrograms = [];

        foreach ($reports[4]['rows'] ?? [] as $row) {
            $path = $row['dimensionValues'][0]['value'] ?? '/';
            $title = $row['dimensionValues'][1]['value'] ?? $path;
            $views = (int) ($row['metricValues'][0]['value'] ?? 0);

            $isProgram = false;
            $programName = null;

            if (preg_match('@^/programlar/([^/?#]+)@', $path, $matches)) {
                $slug = $matches[1];
                if (isset($programsBySlug[$slug])) {
                    $isProgram = true;
                    $programName = $programsBySlug[$slug];

                    $topPrograms[] = [
                        'name' => $programName,
                        'slug' => $slug,
                        'views' => $views,
                        'path' => $path,
                    ];
                }
            }

            $topPages[] = [
                'path' => $path,
                'title' => $title,
                'views' => $views,
                'is_program' => $isProgram,
                'program_name' => $programName,
            ];
        }

        return [
            'total_users' => $totalUsers,
            'active_users' => $activeUsers,
            'sessions' => $sessions,
            'pageviews' => $pageviews,
            'avg_session_duration' => $this->formatDuration($avgDurationSecs),
            'timeseries' => $timeseries,
            'devices' => $devices,
            'traffic_sources' => $trafficSources,
            'top_pages' => $topPages,
            'top_programs' => $topPrograms,
        ];
    }

    /**
     * Generates an OAuth2 Access Token for Google APIs using Service Account JSON credentials.
     */
    public function getAccessToken(array $credentials): string
    {
        $clientEmail = $credentials['client_email'] ?? null;
        $privateKey = $credentials['private_key'] ?? null;
        $tokenUri = $credentials['token_uri'] ?? 'https://oauth2.googleapis.com/token';

        if (! $clientEmail || ! $privateKey) {
            throw new \InvalidArgumentException('Service Account JSON eksik: client_email ve private_key alanları zorunludur.');
        }

        $header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
        $now = time();
        $payload = json_encode([
            'iss' => $clientEmail,
            'scope' => 'https://www.googleapis.com/auth/analytics.readonly',
            'aud' => $tokenUri,
            'exp' => $now + 3600,
            'iat' => $now,
        ]);

        $base64Header = $this->base64UrlEncode($header);
        $base64Payload = $this->base64UrlEncode($payload);

        $signature = '';
        $success = openssl_sign(
            $base64Header.'.'.$base64Payload,
            $signature,
            $privateKey,
            OPENSSL_ALGO_SHA256
        );

        if (! $success) {
            throw new \RuntimeException('Google OAuth JWT imzalama başarısız oldu. Private key formatını kontrol edin.');
        }

        $base64Signature = $this->base64UrlEncode($signature);
        $jwt = $base64Header.'.'.$base64Payload.'.'.$base64Signature;

        $response = Http::asForm()->post($tokenUri, [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);

        if (! $response->successful()) {
            $err = $response->json('error_description') ?? $response->json('error') ?? $response->body();
            throw new \RuntimeException('Google OAuth erişim jetonu alınamadı: '.$this->sanitizeError($err));
        }

        $token = $response->json('access_token');
        if (! $token) {
            throw new \RuntimeException('Google OAuth sunucusundan erişim jetonu dönmedi.');
        }

        return $token;
    }

    /**
     * URL-safe Base64 encoding without padding.
     */
    protected function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Formats duration seconds into readable M:SS string (e.g. 92 => "1:32").
     */
    protected function formatDuration(float $seconds): string
    {
        $totalSecs = (int) round($seconds);
        $minutes = (int) floor($totalSecs / 60);
        $secs = $totalSecs % 60;

        return sprintf('%d:%02d', $minutes, $secs);
    }

    /**
     * Sanitizes sensitive data (private key, access tokens, credentials JSON) from messages.
     */
    public function sanitizeError(string $message): string
    {
        $message = preg_replace('/-----BEGIN [A-Z ]+-----[^\-]+-----END [A-Z ]+-----/s', '[REDACTED_PRIVATE_KEY]', $message);
        $message = preg_replace('/"private_key":\s*"[^"]+"/', '"private_key": "[REDACTED]"', $message);
        $message = preg_replace('/ya29\.[a-zA-Z0-9_\-]+/', '[REDACTED_TOKEN]', $message);

        return Str::limit($message, 300);
    }

}

