<?php

namespace App\Services\Instagram;

use App\Models\InstagramVideo;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;

class InstagramFetchService
{
    /**
     * Extracts shortcode, media type, and clean canonical permalink from various Instagram URL formats.
     *
     * @param string $url
     * @return array{shortcode: string, type: string, permalink: string}
     * @throws InvalidArgumentException
     */
    public function extractIdentifier(string $url): array
    {
        $url = trim($url);

        if (blank($url)) {
            throw new InvalidArgumentException('Lütfen bir Instagram Reel veya Video bağlantısı girin.');
        }

        // Clean query strings & trailing slash
        $cleanUrl = strtok($url, '?');
        $cleanUrl = rtrim($cleanUrl, '/');

        // Match patterns: instagram.com/reel/CODE, /p/CODE, /tv/CODE, instagr.am/p/CODE
        if (! preg_match('#instagram\.com/(reel|p|tv)/([A-Za-z0-9_-]+)#i', $cleanUrl, $matches) &&
            ! preg_match('#instagr\.am/(reel|p|tv)/([A-Za-z0-9_-]+)#i', $cleanUrl, $matches) &&
            ! preg_match('#instagram\.com/(reel|p|tv)/([A-Za-z0-9_-]+)#i', $url, $matches)) {
            throw new InvalidArgumentException('Geçerli bir Instagram Reel veya Video bağlantısı girin. (Örn: https://www.instagram.com/reel/C123456789/)');
        }

        $type = strtolower($matches[1]);
        $shortcode = $matches[2];
        $permalink = "https://www.instagram.com/{$type}/{$shortcode}/";

        return [
            'shortcode' => $shortcode,
            'type' => $type,
            'permalink' => $permalink,
        ];
    }

    /**
     * Fetches Instagram video metadata via Meta / Instagram Graph API or oEmbed.
     * Checks for duplicates in the database before making API calls.
     *
     * @param string $url
     * @param int|string|null $ignoreId
     * @return array{instagram_media_id: string, shortcode: string, username: string, permalink: string, caption: ?string, thumbnail_url: string, media_type: string, posted_at: ?string}
     */
    public function fetchMediaData(string $url, $ignoreId = null): array
    {
        $info = $this->extractIdentifier($url);
        $permalink = $info['permalink'];
        $shortcode = $info['shortcode'];

        // Duplicate check across permalinks, shortcodes, or raw URL variations
        $existsQuery = InstagramVideo::query()
            ->where(function ($q) use ($permalink, $shortcode, $url) {
                $q->where('permalink', 'like', "%{$shortcode}%")
                  ->orWhere('shortcode', $shortcode)
                  ->orWhere('permalink', $permalink)
                  ->orWhere('permalink', $url);
            });

        if ($ignoreId) {
            $existsQuery->where('id', '!=', $ignoreId);
        }

        $exists = $existsQuery->first();

        if ($exists) {
            throw new InvalidArgumentException("Bu Instagram içeriği zaten eklenmiş.");
        }

        $accessToken = config('services.instagram.access_token');
        $appId = config('services.instagram.app_id');
        $clientToken = config('services.instagram.client_token');

        $token = $accessToken ?: ($appId && $clientToken ? "{$appId}|{$clientToken}" : null);

        $mediaId = null;
        $username = null;
        $caption = null;
        $thumbnailUrl = null;
        $embedHtml = null;
        $mediaType = $info['type'] === 'reel' ? 'reel' : 'video';
        $postedAt = null;

        $mediaUrl = null;

        // If Access Token is provided, attempt Graph API Direct Media Lookup
        if (! empty($token)) {
            try {
                $graphRes = Http::acceptJson()->get("https://graph.facebook.com/v19.0/{$shortcode}", [
                    'fields' => 'id,caption,media_type,media_url,thumbnail_url,permalink,timestamp,username',
                    'access_token' => $token,
                ]);

                if ($graphRes->successful()) {
                    $gData = $graphRes->json();
                    $mediaId = $gData['id'] ?? null;
                    $username = isset($gData['username']) ? str_replace('@', '', $gData['username']) : null;
                    $caption = $gData['caption'] ?? null;
                    $thumbnailUrl = $gData['thumbnail_url'] ?? null;
                    $mediaUrl = $gData['media_url'] ?? null;
                    $postedAt = $gData['timestamp'] ?? null;
                }
            } catch (\Throwable $e) {
                Log::warning("Instagram Graph API direct media fetch failed: {$e->getMessage()}");
            }
        }

        // oEmbed Call
        $queryParams = ['url' => $permalink];
        if (! empty($token)) {
            $queryParams['access_token'] = $token;
        }

        $response = Http::acceptJson()->get('https://graph.facebook.com/v19.0/instagram_oembed', $queryParams);

        if ($response->successful()) {
            $data = $response->json();
            $mediaId = $mediaId ?: ($data['media_id'] ?? null);
            $username = $username ?: (isset($data['author_name']) ? str_replace('@', '', $data['author_name']) : null);
            $caption = $caption ?: ($data['title'] ?? null);
            $thumbnailUrl = $thumbnailUrl ?: ($data['thumbnail_url'] ?? null);
            $embedHtml = $data['html'] ?? null;
        } else {
            // Secondary fallback attempt via public oEmbed endpoint
            try {
                $fallbackResponse = Http::acceptJson()->get('https://api.instagram.com/oembed', [
                    'url' => $permalink,
                ]);

                if ($fallbackResponse->successful()) {
                    $data = $fallbackResponse->json();
                    $mediaId = $mediaId ?: ($data['media_id'] ?? null);
                    $username = $username ?: (isset($data['author_name']) ? str_replace('@', '', $data['author_name']) : null);
                    $caption = $caption ?: ($data['title'] ?? null);
                    $thumbnailUrl = $thumbnailUrl ?: ($data['thumbnail_url'] ?? null);
                    $embedHtml = $data['html'] ?? null;
                }
            } catch (\Throwable $e) {
                Log::warning('Instagram Public oEmbed fallback failed: ' . $e->getMessage());
            }

            if (blank($embedHtml) && blank($mediaId)) {
                $status = $response->status();
                $errorMsg = $response->json('error.message', 'Meta oEmbed API içerik verisini dönmedi.');
                Log::error("Instagram oEmbed API Hata ({$status}): {$errorMsg}", ['url' => $permalink]);

                if ($status === 400 || $status === 403) {
                    throw new RuntimeException("Instagram içeriği doğrulanamadı ({$status}): Geçersiz Reel/Video URL'si veya erişim kısıtlaması.");
                }
            }
        }

        return [
            'instagram_media_id' => (string) ($mediaId ?: "ig_{$shortcode}"),
            'shortcode' => $shortcode,
            'username' => $username,
            'permalink' => $permalink,
            'embed_html' => $embedHtml,
            'caption' => $caption,
            'thumbnail_url' => $thumbnailUrl ?: $mediaUrl,
            'media_type' => $mediaType,
            'posted_at' => $postedAt ?: now()->toDateTimeString(),
        ];
    }
}
