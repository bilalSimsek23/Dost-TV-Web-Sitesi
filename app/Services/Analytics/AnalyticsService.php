<?php

namespace App\Services\Analytics;

use App\Models\NotFoundLog;
use App\Models\SearchLog;
use App\Models\SiteEvent;
use Illuminate\Support\Str;

class AnalyticsService
{
    /**
     * Masks PII sensitive patterns (emails, phone numbers, 11-digit numbers) in search queries.
     */
    public function maskPii(string $text): string
    {
        // 1. Email masking
        $masked = preg_replace('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', '***@***', $text);

        // 2. TC Identity masking (11 digits starting with 1-9)
        $masked = preg_replace('/\b[1-9]\d{10}\b/', '***TC***', $masked);

        // 3. Phone masking (Turkish phone formats: 05xx..., +90 5xx..., 0 5xx..., etc.)
        $masked = preg_replace('/(?:\+?90[\s-]*)?(?:0[\s-]*)?[5][0-9]{2}[\s-]?[0-9]{3}[\s-]?[0-9]{2}[\s-]?[0-9]{2}/', '***PHONE***', $masked);

        return $masked;
    }

    /**
     * Anonymously logs a site search query and its result count.
     */
    public function logSearch(string $query, int $resultCount, ?string $deviceType = null, ?string $sourcePage = null): ?SearchLog
    {
        try {
            $cleanQuery = trim($query);

            if ($cleanQuery === '') {
                return null;
            }

            $maskedQuery = $this->maskPii($cleanQuery);
            $normalized = mb_strtolower($maskedQuery, 'UTF-8');

            return SearchLog::create([
                'search_query' => Str::limit($maskedQuery, 255, ''),
                'normalized_query' => Str::limit($normalized, 255, ''),
                'result_count' => max(0, $resultCount),
                'device_type' => $deviceType ? Str::limit($deviceType, 50, '') : null,
                'source_page' => $sourcePage ? Str::limit($sourcePage, 255, '') : null,
                'searched_at' => now(),
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Search logging failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Anonymously logs a user interaction event (e.g. hero click, live tv click, etc.).
     */
    public function logEvent(string $eventName, ?string $entityType = null, ?int $entityId = null, array $metadata = []): ?SiteEvent
    {
        try {
            $cleanEvent = trim($eventName);

            if ($cleanEvent === '') {
                return null;
            }

            return SiteEvent::create([
                'event_name' => Str::limit($cleanEvent, 100, ''),
                'entity_type' => $entityType ? Str::limit($entityType, 50, '') : null,
                'entity_id' => $entityId,
                'metadata' => array_slice($metadata, 0, 10, true),
                'occurred_at' => now(),
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Event logging failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Logs or increments hit count for a 404 Not Found URL path.
     */
    public function logNotFound(string $path, ?string $referer = null): ?NotFoundLog
    {
        try {
            $cleanPath = '/' . ltrim(trim($path), '/');

            /** @var NotFoundLog $log */
            $log = NotFoundLog::query()->firstOrNew(['path' => Str::limit($cleanPath, 255, '')]);

            if ($log->exists) {
                $log->hit_count += 1;
            } else {
                $log->hit_count = 1;
            }

            if ($referer && blank($log->referer)) {
                $log->referer = Str::limit($referer, 255, '');
            }

            $log->last_occurred_at = now();
            $log->save();

            return $log;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('404 logging failed: ' . $e->getMessage());
            return null;
        }
    }
}
