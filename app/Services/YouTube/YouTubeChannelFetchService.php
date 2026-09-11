<?php

namespace App\Services\YouTube;

use App\Models\YoutubeChannel;
use App\Support\Youtube;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;

class YouTubeChannelFetchService
{
    /**
     * Fetches raw YouTube Channel details (id, title, handle, canonical url, avatar) via Data API v3.
     * Does NOT perform duplicate database check.
     *
     * @param string $channelUrl
     * @return array{channel_id: string, name: string, handle: string, canonical_url: string, avatar_url: ?string}
     */
    public function fetchChannelDataOnly(string $channelUrl): array
    {
        $apiKey = config('services.youtube.api_key') ?: config('services.youtube.key');

        if (blank($apiKey)) {
            throw new RuntimeException('YouTube API anahtarı yapılandırılmamış (YOUTUBE_API_KEY .env dosyasında bulunamadı).');
        }

        $identifier = Youtube::extractChannelIdentifier($channelUrl);

        if (! $identifier) {
            throw new InvalidArgumentException('Geçerli bir YouTube kanal bağlantısı veya kullanıcı adı girin. (Örn: https://www.youtube.com/@CocukveBiz)');
        }

        $params = [
            'part' => 'snippet',
            'key' => $apiKey,
        ];

        if ($identifier['type'] === 'handle') {
            $params['forHandle'] = $identifier['value'];
        } elseif ($identifier['type'] === 'id') {
            $params['id'] = $identifier['value'];
        } else {
            $params['forUsername'] = $identifier['value'];
        }

        $response = Http::acceptJson()->get('https://www.googleapis.com/youtube/v3/channels', $params);

        if (! $response->successful()) {
            $status = $response->status();
            $errorMsg = $response->json('error.message', 'YouTube API yanıt vermedi.');
            Log::error("YouTube Channel API Hata ({$status}): {$errorMsg}", ['url' => $channelUrl]);

            if ($status === 400 || $status === 403) {
                throw new RuntimeException("YouTube API erişim hatası ({$status}): " . ($status === 403 ? 'API Anahtarı geçersiz veya kota doldu.' : 'Geçersiz istek.'));
            }

            throw new RuntimeException("YouTube sunucusu ile bağlantı kurulamadı ({$status}).");
        }

        $items = $response->json('items', []);

        if (empty($items)) {
            // Fallback for custom handles or username lookup if forHandle fails
            if ($identifier['type'] === 'handle') {
                $cleanHandle = ltrim($identifier['value'], '@');
                $fallbackResponse = Http::acceptJson()->get('https://www.googleapis.com/youtube/v3/channels', [
                    'part' => 'snippet',
                    'forHandle' => $cleanHandle,
                    'key' => $apiKey,
                ]);
                if ($fallbackResponse->successful() && ! empty($fallbackResponse->json('items', []))) {
                    $items = $fallbackResponse->json('items');
                }
            }
        }

        if (empty($items)) {
            throw new InvalidArgumentException('Kanal YouTube üzerinde bulunamadı. Lütfen URL adresini kontrol edin.');
        }

        $channelData = $items[0];
        $channelId = $channelData['id'] ?? null;
        $snippet = $channelData['snippet'] ?? [];

        if (blank($channelId)) {
            throw new InvalidArgumentException('Kanal YouTube üzerinde doğrulanamadı.');
        }

        $rawHandle = $snippet['customUrl'] ?? null;
        $handle = $rawHandle ? ('@' . ltrim($rawHandle, '@')) : ('@' . ($snippet['title'] ?? 'channel'));
        $canonicalUrl = $rawHandle ? ('https://www.youtube.com/' . (str_starts_with($rawHandle, '@') ? $rawHandle : '@' . $rawHandle)) : ("https://www.youtube.com/channel/{$channelId}");

        $thumbnails = $snippet['thumbnails'] ?? [];
        $avatarUrl = $thumbnails['high']['url'] ?? ($thumbnails['medium']['url'] ?? ($thumbnails['default']['url'] ?? null));

        return [
            'channel_id' => $channelId,
            'name' => $snippet['title'] ?? 'YouTube Kanalı',
            'handle' => $handle,
            'canonical_url' => $canonicalUrl,
            'avatar_url' => $avatarUrl,
        ];
    }

    /**
     * Fetches YouTube Channel details and verifies duplicate check for single channel form.
     *
     * @param string $channelUrl
     * @param int|null $ignoreId Optional ID to ignore when checking duplicates
     * @return array{channel_id: string, name: string, handle: string, canonical_url: string, avatar_url: ?string}
     */
    public function fetchChannelInfo(string $channelUrl, ?int $ignoreId = null): array
    {
        $info = $this->fetchChannelDataOnly($channelUrl);

        // Duplicate check for standalone channel creation
        $existsQuery = YoutubeChannel::query()->where('channel_id', $info['channel_id']);
        if ($ignoreId) {
            $existsQuery->where('id', '!=', $ignoreId);
        }

        if ($existsQuery->exists()) {
            $existing = $existsQuery->first();
            throw new InvalidArgumentException("Bu YouTube kanalı ({$existing->name} - {$existing->handle}) zaten sisteme kayıtlı.");
        }

        return $info;
    }
}
