<?php

namespace App\Services\YouTube;

use App\Models\Episode;
use App\Models\Program;
use App\Models\YoutubeSyncLog;
use App\Support\Youtube;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class YouTubePlaylistSyncService
{
    protected YouTubePlaylistImportService $importService;

    public function __construct(?YouTubePlaylistImportService $importService = null)
    {
        $this->importService = $importService ?? app(YouTubePlaylistImportService::class);
    }

    /**
     * Synchronize a single program's YouTube playlist.
     * If $updateExistingMetadata is true, metadata of existing videos (title, description, thumbnail, aired_at, youtube_url)
     * is updated if changed, while preserving episode_number, season_number, slug, status, show_on_public, is_active, sort_order.
     */
    public function syncProgramPlaylist(Program $program, bool $dryRun = false, bool $updateExistingMetadata = false): array
    {
        if (blank($program->youtube_playlist_url)) {
            return [
                'success' => true,
                'program_id' => $program->id,
                'program_name' => $program->name,
                'total_items' => 0,
                'new_videos' => 0,
                'created_episodes' => 0,
                'updated_episodes' => 0,
                'unchanged_episodes' => 0,
                'skipped_existing' => 0,
                'errors' => 0,
                'dry_run' => $dryRun,
                'message' => 'Bu programa ait bir YouTube Playlist URL tanımlanmamış.',
                'items' => [],
            ];
        }

        $startedAt = now();

        try {
            $result = $this->importService->fetchPlaylistItems($program->youtube_playlist_url);
        } catch (\Throwable $e) {
            Log::error("YouTube Playlist Sync Error for Program {$program->id} ({$program->name}): {$e->getMessage()}", [
                'exception' => $e,
                'playlist_url' => $program->youtube_playlist_url,
            ]);

            if (! $dryRun) {
                YoutubeSyncLog::create([
                    'program_id' => $program->id,
                    'playlist_url' => $program->youtube_playlist_url,
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'started_at' => $startedAt,
                    'finished_at' => now(),
                ]);
            }

            return [
                'success' => false,
                'program_id' => $program->id,
                'program_name' => $program->name,
                'playlist_url' => $program->youtube_playlist_url,
                'total_items' => 0,
                'new_videos' => 0,
                'created_episodes' => 0,
                'updated_episodes' => 0,
                'unchanged_episodes' => 0,
                'skipped_existing' => 0,
                'errors' => 1,
                'dry_run' => $dryRun,
                'message' => $e->getMessage(),
                'items' => [],
            ];
        }

        $rawItems = $result['items'] ?? [];
        if (empty($rawItems)) {
            if (! $dryRun) {
                $program->update(['last_youtube_sync_at' => now()]);

                YoutubeSyncLog::create([
                    'program_id' => $program->id,
                    'playlist_url' => $program->youtube_playlist_url,
                    'status' => 'success',
                    'checked_videos' => 0,
                    'new_videos' => 0,
                    'created_episodes' => 0,
                    'skipped_videos' => 0,
                    'started_at' => $startedAt,
                    'finished_at' => now(),
                ]);
            }

            return [
                'success' => true,
                'program_id' => $program->id,
                'program_name' => $program->name,
                'playlist_url' => $program->youtube_playlist_url,
                'total_items' => 0,
                'new_videos' => 0,
                'created_episodes' => 0,
                'updated_episodes' => 0,
                'unchanged_episodes' => 0,
                'skipped_existing' => 0,
                'errors' => 0,
                'dry_run' => $dryRun,
                'message' => 'Playlist içinde aktarılabilecek video bulunamadı.',
                'items' => [],
            ];
        }

        // Ensure items are sorted chronologically by published_at ASC (Oldest -> Newest)
        usort($rawItems, function ($a, $b) {
            $timeA = ! empty($a['published_at']) ? strtotime($a['published_at']) : 0;
            $timeB = ! empty($b['published_at']) ? strtotime($b['published_at']) : 0;
            if ($timeA === $timeB) {
                return ($a['position'] ?? 0) <=> ($b['position'] ?? 0);
            }
            return $timeA <=> $timeB;
        });

        // Fetch all existing episode youtube_urls from DB in bulk
        $existingUrls = Episode::pluck('youtube_url')->filter()->toArray();
        $existingVideoIds = [];
        foreach ($existingUrls as $url) {
            $vId = Youtube::extractVideoId($url);
            if ($vId) {
                $existingVideoIds[$vId] = true;
            }
        }

        $maxEpisodeNum = Episode::where('program_id', $program->id)->max('episode_number') ?? 0;
        $createdCount = 0;
        $updatedCount = 0;
        $unchangedCount = 0;
        $errorCount = 0;
        $createdEpisodes = [];

        foreach ($rawItems as $item) {
            $videoId = $item['video_id'];
            $canonicalUrl = Youtube::canonicalUrl($videoId);

            if (isset($existingVideoIds[$videoId])) {
                if ($updateExistingMetadata) {
                    try {
                        $existingEp = Episode::where('program_id', $program->id)
                            ->where('youtube_url', 'like', "%{$videoId}%")
                            ->first();

                        if (! $existingEp) {
                            $existingEp = Episode::where('youtube_url', 'like', "%{$videoId}%")->first();
                        }

                        if ($existingEp) {
                            $updates = [];
                            if ($item['title'] !== $existingEp->title) {
                                $updates['title'] = $item['title'];
                            }
                            if (! empty($item['description']) && $item['description'] !== $existingEp->description) {
                                $updates['description'] = $item['description'];
                            }
                            if (! empty($item['thumbnail_url']) && $item['thumbnail_url'] !== $existingEp->thumbnail) {
                                $updates['thumbnail'] = $item['thumbnail_url'];
                            }
                            if (! empty($item['published_at'])) {
                                $newAiredAt = date('Y-m-d', strtotime($item['published_at']));
                                $currAiredAt = $existingEp->aired_at ? $existingEp->aired_at->format('Y-m-d') : null;
                                if ($newAiredAt !== $currAiredAt) {
                                    $updates['aired_at'] = $newAiredAt;
                                }
                            }
                            if ($canonicalUrl !== $existingEp->youtube_url) {
                                $updates['youtube_url'] = $canonicalUrl;
                            }

                            if (! empty($updates)) {
                                if (! $dryRun) {
                                    $existingEp->update($updates);
                                }
                                $updatedCount++;
                            } else {
                                $unchangedCount++;
                            }
                        } else {
                            $unchangedCount++;
                        }
                    } catch (\Throwable $e) {
                        $errorCount++;
                        Log::error("Error updating episode metadata for video {$videoId}: {$e->getMessage()}");
                    }
                } else {
                    $unchangedCount++;
                }
                continue;
            }

            // Mark as processed in-memory so duplicates within the playlist are not re-created
            $existingVideoIds[$videoId] = true;

            $maxEpisodeNum++;

            $episodeData = [
                'program_id' => $program->id,
                'video_source' => 'youtube',
                'youtube_url' => $canonicalUrl,
                'title' => $item['title'],
                'slug' => Str::slug($item['title']) . '-' . Str::random(5),
                'description' => $item['description'] ?? null,
                'thumbnail' => $item['thumbnail_url'] ?? null,
                'horizontal_image' => $item['thumbnail_url'] ?? null,
                'aired_at' => ! empty($item['published_at']) ? date('Y-m-d H:i:s', strtotime($item['published_at'])) : now(),
                'episode_number' => $maxEpisodeNum,
                'status' => 'published',
                'show_on_public' => true,
                'is_active' => true,
            ];

            if (! $dryRun) {
                Episode::create($episodeData);
            }

            $createdCount++;
            $createdEpisodes[] = $episodeData;
        }

        if (! $dryRun) {
            $program->update(['last_youtube_sync_at' => now()]);

            YoutubeSyncLog::create([
                'program_id' => $program->id,
                'playlist_url' => $program->youtube_playlist_url,
                'status' => $errorCount > 0 ? 'partial' : 'success',
                'checked_videos' => count($rawItems),
                'new_videos' => $createdCount,
                'created_episodes' => $createdCount,
                'skipped_videos' => $unchangedCount + $updatedCount,
                'started_at' => $startedAt,
                'finished_at' => now(),
            ]);
        }

        return [
            'success' => true,
            'program_id' => $program->id,
            'program_name' => $program->name,
            'playlist_url' => $program->youtube_playlist_url,
            'total_items' => count($rawItems),
            'new_videos' => $createdCount,
            'created_episodes' => $createdCount,
            'updated_episodes' => $updatedCount,
            'unchanged_episodes' => $unchangedCount,
            'skipped_existing' => $unchangedCount + $updatedCount,
            'errors' => $errorCount,
            'dry_run' => $dryRun,
            'items' => $createdEpisodes,
        ];
    }

    /**
     * Synchronize all active programs that have a configured YouTube playlist URL (Create-only mode).
     */
    public function syncAllPlaylists(bool $dryRun = false): array
    {
        $programs = Program::whereNotNull('youtube_playlist_url')
            ->where('youtube_playlist_url', '!=', '')
            ->get();

        $stats = [
            'checked_programs' => $programs->count(),
            'checked_playlists' => $programs->count(),
            'new_videos_found' => 0,
            'created_episodes' => 0,
            'skipped_existing' => 0,
            'errors' => 0,
            'dry_run' => $dryRun,
            'details' => [],
        ];

        foreach ($programs as $program) {
            $res = $this->syncProgramPlaylist($program, $dryRun, false);
            $stats['new_videos_found'] += $res['new_videos'] ?? 0;
            $stats['created_episodes'] += $res['created_episodes'] ?? 0;
            $stats['skipped_existing'] += $res['skipped_existing'] ?? 0;
            $stats['errors'] += $res['errors'] ?? 0;
            $stats['details'][] = $res;
        }

        return $stats;
    }
}
