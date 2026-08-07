<?php

namespace App\Services\YouTube;

use App\Support\Youtube;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;

class YouTubePlaylistImportService
{
    public const MAX_PLAYLIST_IMPORT_ITEMS = 500;

    /**
     * Playlist URL'sinden videoları çeker ve normalize eder.
     *
     * @param string $playlistUrl
     * @return array{playlist_id: string, total_items: int, items: array<int, array{video_id: string, title: string, description: string, thumbnail_url: string, published_at: ?string, position: int, canonical_url: string}>}
     */
    public function fetchPlaylistItems(string $playlistUrl): array
    {
        $apiKey = config('services.youtube.key') ?: env('YOUTUBE_API_KEY');

        if (blank($apiKey)) {
            throw new RuntimeException('YouTube API anahtarı yapılandırılmamış (YOUTUBE_API_KEY .env dosyasında bulunamadı).');
        }

        $playlistId = Youtube::extractPlaylistId($playlistUrl);

        if (blank($playlistId)) {
            throw new InvalidArgumentException('Geçerli bir YouTube playlist bağlantısı girin.');
        }

        $items = [];
        $pageToken = null;
        $pageCount = 0;

        do {
            $pageCount++;
            $queryParams = [
                'part' => 'snippet,contentDetails',
                'playlistId' => $playlistId,
                'maxResults' => 50,
                'key' => $apiKey,
            ];

            if (filled($pageToken)) {
                $queryParams['pageToken'] = $pageToken;
            }

            try {
                $response = Http::timeout(10)->get('https://www.googleapis.com/youtube/v3/playlistItems', $queryParams);
            } catch (\Throwable $e) {
                Log::error("YouTube API bağlantı hatası: {$e->getMessage()}", [
                    'playlist_id' => $playlistId,
                    'exception' => $e,
                ]);
                throw new RuntimeException("YouTube API sunucusuna erişilemedi: {$e->getMessage()}");
            }

            if ($response->failed()) {
                $status = $response->status();
                $errorData = $response->json('error') ?? [];
                $errorReason = $errorData['errors'][0]['reason'] ?? '';
                $errorMessage = $errorData['message'] ?? $response->body();

                Log::error("YouTube API Hata ({$status}): {$errorMessage}", [
                    'playlist_id' => $playlistId,
                    'status' => $status,
                    'reason' => $errorReason,
                ]);

                if ($status === 404) {
                    throw new RuntimeException('Belirtilen YouTube playlist bulunamadı veya silinmiş.');
                }

                if ($status === 403) {
                    if ($errorReason === 'quotaExceeded') {
                        throw new RuntimeException('YouTube API günlük kullanım kotası (quota) aşıldı.');
                    }

                    throw new RuntimeException('Bu YouTube playlist gizli (private) veya erişim kısıtlamasına sahip.');
                }

                if ($status === 400) {
                    throw new RuntimeException('Geçersiz YouTube API isteği: ' . ($errorData['message'] ?? ''));
                }

                throw new RuntimeException("YouTube API hatası ({$status}): {$errorMessage}");
            }

            $responseData = $response->json();
            $rawItems = $responseData['items'] ?? [];

            foreach ($rawItems as $rawItem) {
                $snippet = $rawItem['snippet'] ?? [];
                $contentDetails = $rawItem['contentDetails'] ?? [];

                $videoId = $snippet['resourceId']['videoId'] ?? $contentDetails['videoId'] ?? null;
                $title = trim(html_entity_decode(strip_tags($snippet['title'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

                // Silinmiş veya gizli videoları atla
                if (blank($videoId) || in_array($title, ['Private video', 'Deleted video'], true)) {
                    continue;
                }

                $rawDescription = $snippet['description'] ?? '';
                // Script ve iframe etiketlerini temizle, güvenli uzunluğa kırp
                $cleanDescription = strip_tags($rawDescription);
                $cleanDescription = html_entity_decode($cleanDescription, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $description = mb_substr(trim($cleanDescription), 0, 1000, 'UTF-8');

                $thumbnails = $snippet['thumbnails'] ?? [];
                $thumbnailUrl = $thumbnails['high']['url']
                    ?? $thumbnails['medium']['url']
                    ?? $thumbnails['default']['url']
                    ?? Youtube::thumbnailUrl($videoId);

                $publishedAtRaw = $snippet['publishedAt'] ?? null;
                $publishedAt = $publishedAtRaw ? substr($publishedAtRaw, 0, 10) : null;
                $position = (int) ($snippet['position'] ?? count($items));

                $items[] = [
                    'video_id' => $videoId,
                    'title' => $title,
                    'description' => $description,
                    'thumbnail_url' => $thumbnailUrl,
                    'published_at' => $publishedAt,
                    'position' => $position,
                    'canonical_url' => Youtube::canonicalUrl($videoId),
                ];

                if (count($items) >= self::MAX_PLAYLIST_IMPORT_ITEMS) {
                    Log::warning("YouTube Playlist Aktarımı maks limiti (500) aştı. İlk 500 video alındı.", [
                        'playlist_id' => $playlistId,
                    ]);
                    break 2;
                }
            }

            $pageToken = $responseData['nextPageToken'] ?? null;
        } while (filled($pageToken) && count($items) < self::MAX_PLAYLIST_IMPORT_ITEMS);

        // Sort items chronologically by published_at ASC (Oldest -> Newest)
        usort($items, function ($a, $b) {
            $timeA = ! empty($a['published_at']) ? strtotime($a['published_at']) : 0;
            $timeB = ! empty($b['published_at']) ? strtotime($b['published_at']) : 0;
            if ($timeA === $timeB) {
                return ($a['position'] ?? 0) <=> ($b['position'] ?? 0);
            }
            return $timeA <=> $timeB;
        });

        return [
            'playlist_id' => $playlistId,
            'total_items' => count($items),
            'items' => $items,
        ];
    }
}
