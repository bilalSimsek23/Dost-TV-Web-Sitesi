<?php

namespace App\Services\YouTube;

use App\Models\Program;
use App\Models\YoutubeChannel;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class ProgramYoutubeChannelSyncService
{
    /**
     * Automatically synchronizes or creates a YoutubeChannel record from Program's youtube_channel_url.
     * Prevents duplicates by matching channel_id, handle, or canonical URL.
     * Preserves existing channel titles edited in CMS and prevents 500 errors if API is unavailable.
     *
     * @param Program $program
     * @return YoutubeChannel|null
     */
    public function syncFromProgram(Program $program): ?YoutubeChannel
    {
        $url = trim((string) $program->youtube_channel_url);

        if (blank($url)) {
            return null;
        }

        try {
            $fetchService = app(YouTubeChannelFetchService::class);
            $info = $fetchService->fetchChannelDataOnly($url);

            $channelId = $info['channel_id'] ?? null;
            $handle = $info['handle'] ?? null;
            $canonicalUrl = $info['canonical_url'] ?? $url;

            // 1. Search for existing YoutubeChannel by channel_id, url, or handle
            $channel = null;
            if ($channelId) {
                $channel = YoutubeChannel::query()->where('channel_id', $channelId)->first();
            }

            if (! $channel && $canonicalUrl) {
                $channel = YoutubeChannel::query()->where('url', $canonicalUrl)->first();
            }

            if (! $channel && $handle) {
                $channel = YoutubeChannel::query()->where('handle', $handle)->first();
            }

            // 2. If channel already exists, update missing fields while preserving existing custom title
            if ($channel) {
                $updates = [];

                if (blank($channel->channel_id) && $channelId) {
                    $updates['channel_id'] = $channelId;
                }

                if (blank($channel->logo) && ! empty($info['avatar_url'])) {
                    $updates['logo'] = $info['avatar_url'];
                }

                if (blank($channel->handle) && $handle) {
                    $updates['handle'] = $handle;
                }

                if (blank($channel->url) && $canonicalUrl) {
                    $updates['url'] = $canonicalUrl;
                }

                if (! empty($updates)) {
                    $channel->update($updates);
                }

                return $channel;
            }

            // 3. Create new YoutubeChannel record
            $nextSortOrder = ((int) YoutubeChannel::max('sort_order')) + 1;

            return YoutubeChannel::create([
                'channel_id' => $channelId,
                'name' => $info['name'] ?: $program->name,
                'handle' => $handle,
                'url' => $canonicalUrl,
                'logo' => $info['avatar_url'] ?? null,
                'is_active' => true,
                'sort_order' => $nextSortOrder,
            ]);
        } catch (\Throwable $e) {
            Log::warning("Program #{$program->id} YouTube kanalı otomatik senkronizasyon uyarısı: " . $e->getMessage());

            if (class_exists(Notification::class)) {
                try {
                    Notification::make()
                        ->title('Program kaydedildi ancak YouTube kanal bilgileri alınamadı.')
                        ->body($e->getMessage())
                        ->warning()
                        ->send();
                } catch (\Throwable $nt) {
                    // Ignore notification exceptions in cli/testing
                }
            }

            return null;
        }
    }
}
