<?php

namespace App\Services\Instagram;

use App\Models\InstagramVideo;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class InstagramSyncService
{
    /**
     * Syncs latest Reels from Instagram Graph API feed.
     */
    public function syncLatestReels(): array
    {
        $token = config('services.instagram.access_token');
        $accountId = env('INSTAGRAM_ACCOUNT_ID') ?: env('META_INSTAGRAM_ACCOUNT_ID');

        if (blank($token) || blank($accountId)) {
            throw new RuntimeException("Instagram Graph API jetonu (INSTAGRAM_ACCESS_TOKEN) veya Hesap ID'si (INSTAGRAM_ACCOUNT_ID) .env dosyasında bulunamadı.");
        }

        $url = "https://graph.facebook.com/v19.0/{$accountId}/media";

        $response = Http::acceptJson()->get($url, [
            'fields' => 'id,caption,media_type,media_product_type,media_url,thumbnail_url,permalink,timestamp,username',
            'access_token' => $token,
        ]);

        if (! $response->successful()) {
            $status = $response->status();
            $error = $response->json('error.message', 'Meta Graph API medya akışını çekemedi.');
            Log::error("Instagram Media Feed Fetch Error ({$status}): {$error}");
            throw new RuntimeException("Instagram Graph API Hatası ({$status}): {$error}");
        }

        $mediaItems = $response->json('data', []);
        $imported = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($mediaItems as $item) {
            $mediaType = strtoupper($item['media_type'] ?? '');
            $productType = strtoupper($item['media_product_type'] ?? '');

            // Filter for Reels / Video content
            if ($mediaType !== 'VIDEO' && $mediaType !== 'REELS' && $productType !== 'REELS') {
                $skipped++;
                continue;
            }

            $mediaId = (string) ($item['id'] ?? '');
            $permalink = $item['permalink'] ?? '';
            $thumbnailUrl = $item['thumbnail_url'] ?? null;
            $mediaUrl = $item['media_url'] ?? null;
            $username = isset($item['username']) ? str_replace('@', '', $item['username']) : null;
            $apiCaption = $item['caption'] ?? null;
            $postedAt = isset($item['timestamp']) ? date('Y-m-d H:i:s', strtotime($item['timestamp'])) : now();

            // Extract shortcode from permalink
            $shortcode = null;
            if (preg_match('#instagram\.com/(reel|p|tv)/([A-Za-z0-9_-]+)#i', $permalink, $matches)) {
                $shortcode = $matches[2];
            }

            // Find existing record by mediaId, permalink, or shortcode
            $existing = InstagramVideo::query()
                ->where('instagram_media_id', $mediaId)
                ->orWhere('permalink', $permalink)
                ->when($shortcode, fn ($q) => $q->orWhere('shortcode', $shortcode))
                ->first();

            if ($existing) {
                // UPDATE RECORD (Protect manual editor fields: speaker_name, custom caption, cover_image)
                $existing->update([
                    'instagram_media_id' => $mediaId,
                    'shortcode' => $shortcode ?: $existing->shortcode ?: $mediaId,
                    'username' => $username ?: $existing->username,
                    'permalink' => $permalink ?: $existing->permalink,
                    'caption' => filled($existing->caption) ? $existing->caption : $apiCaption,
                    'thumbnail_url' => $thumbnailUrl ?: ($mediaUrl ?: $existing->thumbnail_url),
                    'media_url' => $mediaUrl ?: ($thumbnailUrl ?: $existing->media_url),
                    'media_type' => 'reel',
                    'posted_at' => $postedAt,
                ]);
                $updated++;
            } else {
                // CREATE NEW RECORD
                InstagramVideo::create([
                    'instagram_media_id' => $mediaId,
                    'shortcode' => $shortcode ?: $mediaId,
                    'username' => $username,
                    'permalink' => $permalink,
                    'caption' => $apiCaption,
                    'thumbnail_url' => $thumbnailUrl ?: $mediaUrl,
                    'media_url' => $mediaUrl ?: $thumbnailUrl,
                    'media_type' => 'reel',
                    'is_active' => true,
                    'posted_at' => $postedAt,
                ]);
                $imported++;
            }
        }

        return [
            'total_fetched' => count($mediaItems),
            'imported' => $imported,
            'updated' => $updated,
            'skipped' => $skipped,
        ];
    }
}
