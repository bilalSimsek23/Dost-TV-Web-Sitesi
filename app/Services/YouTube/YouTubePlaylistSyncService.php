<?php

namespace App\Services\YouTube;

use App\Models\Episode;
use App\Models\Program;
use App\Models\ProgramSeason;
use App\Models\ProgramSeries;
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
     * Synchronize a specific ProgramSeries's YouTube playlist.
     */
    public function syncSeries(ProgramSeries $series, bool $dryRun = false, bool $updateExistingMetadata = false): array
    {
        $program = $series->program;
        $season = $series->programSeason;
        $playlistUrl = $series->youtube_playlist_url;

        if (blank($playlistUrl)) {
            return [
                'success' => true,
                'program_id' => $series->program_id,
                'program_name' => $program?->name ?? 'Program',
                'program_series_id' => $series->id,
                'series_name' => $series->name,
                'season_number' => $season?->season_number,
                'season_year' => $season?->season_year,
                'total_items' => 0,
                'new_videos' => 0,
                'created_episodes' => 0,
                'updated_episodes' => 0,
                'unchanged_episodes' => 0,
                'skipped_existing' => 0,
                'errors' => 0,
                'dry_run' => $dryRun,
                'message' => 'Bu seriye ait bir YouTube Playlist URL tanımlanmamış.',
                'items' => [],
            ];
        }

        $startedAt = now();

        try {
            $result = $this->importService->fetchPlaylistItems($playlistUrl);
        } catch (\Throwable $e) {
            Log::error("YouTube Playlist Sync Error for Program {$series->program_id} Series {$series->name} (ID: {$series->id}): {$e->getMessage()}", [
                'exception' => $e,
                'playlist_url' => $playlistUrl,
            ]);

            if (! $dryRun) {
                YoutubeSyncLog::create([
                    'program_id' => $series->program_id,
                    'playlist_url' => $playlistUrl,
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'started_at' => $startedAt,
                    'finished_at' => now(),
                ]);
            }

            return [
                'success' => false,
                'program_id' => $series->program_id,
                'program_name' => $program?->name ?? 'Program',
                'program_series_id' => $series->id,
                'series_name' => $series->name,
                'season_number' => $season?->season_number,
                'season_year' => $season?->season_year,
                'playlist_url' => $playlistUrl,
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
                $series->update(['last_youtube_sync_at' => now()]);
                if ($season) {
                    $season->update(['last_youtube_sync_at' => now()]);
                }
                if ($program) {
                    $program->update(['last_youtube_sync_at' => now()]);
                }

                YoutubeSyncLog::create([
                    'program_id' => $series->program_id,
                    'playlist_url' => $playlistUrl,
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
                'program_id' => $series->program_id,
                'program_name' => $program?->name ?? 'Program',
                'program_series_id' => $series->id,
                'series_name' => $series->name,
                'season_number' => $season?->season_number,
                'season_year' => $season?->season_year,
                'playlist_url' => $playlistUrl,
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

        // Sort chronologically published_at ASC (Oldest -> Newest)
        usort($rawItems, function ($a, $b) {
            $timeA = ! empty($a['published_at']) ? strtotime($a['published_at']) : 0;
            $timeB = ! empty($b['published_at']) ? strtotime($b['published_at']) : 0;
            if ($timeA === $timeB) {
                return ($a['position'] ?? 0) <=> ($b['position'] ?? 0);
            }
            return $timeA <=> $timeB;
        });

        // Fetch existing episode URLs in THIS specific series
        $existingSeriesEpisodes = Episode::where('program_series_id', $series->id)
            ->whereNotNull('youtube_url')
            ->get();

        $existingVideoIds = [];
        $existingEpisodesByVideoId = [];
        foreach ($existingSeriesEpisodes as $ep) {
            $vId = Youtube::extractVideoId($ep->youtube_url);
            if ($vId) {
                $existingVideoIds[$vId] = true;
                $existingEpisodesByVideoId[$vId] = $ep;
            }
        }

        // Calculate max episode number in this series
        $maxEpisodeNum = Episode::where('program_id', $series->program_id)
            ->where('program_series_id', $series->id)
            ->max('episode_number') ?? 0;

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
                        $existingEp = $existingEpisodesByVideoId[$videoId] ?? null;

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
                        Log::error("Error updating episode metadata for series video {$videoId}: {$e->getMessage()}");
                    }
                } else {
                    $unchangedCount++;
                }
                continue;
            }

            // Mark as processed in-memory
            $existingVideoIds[$videoId] = true;
            $maxEpisodeNum++;

            $episodeData = [
                'program_id' => $series->program_id,
                'program_series_id' => $series->id,
                'season_number' => $season?->season_number,
                'season_year' => $season?->season_year,
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
            $series->update(['last_youtube_sync_at' => now()]);
            if ($season) {
                $season->update(['last_youtube_sync_at' => now()]);
            }
            if ($program) {
                $program->update(['last_youtube_sync_at' => now()]);
            }

            YoutubeSyncLog::create([
                'program_id' => $series->program_id,
                'playlist_url' => $playlistUrl,
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
            'program_id' => $series->program_id,
            'program_name' => $program?->name ?? 'Program',
            'program_series_id' => $series->id,
            'series_name' => $series->name,
            'season_number' => $season?->season_number,
            'season_year' => $season?->season_year,
            'playlist_url' => $playlistUrl,
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
     * Synchronize a specific ProgramSeason's YouTube playlist.
     */
    public function syncSeason(ProgramSeason $season, bool $dryRun = false, bool $updateExistingMetadata = false): array
    {
        $program = $season->program;
        $playlistUrl = $season->youtube_playlist_url;

        if (blank($playlistUrl)) {
            return [
                'success' => true,
                'program_id' => $season->program_id,
                'program_name' => $program?->name ?? 'Program',
                'season_number' => $season->season_number,
                'season_year' => $season->season_year,
                'total_items' => 0,
                'new_videos' => 0,
                'created_episodes' => 0,
                'updated_episodes' => 0,
                'unchanged_episodes' => 0,
                'skipped_existing' => 0,
                'errors' => 0,
                'dry_run' => $dryRun,
                'message' => 'Bu sezona ait bir YouTube Playlist URL tanımlanmamış.',
                'items' => [],
            ];
        }

        $startedAt = now();

        try {
            $result = $this->importService->fetchPlaylistItems($playlistUrl);
        } catch (\Throwable $e) {
            Log::error("YouTube Playlist Sync Error for Program {$season->program_id} Season {$season->season_number}: {$e->getMessage()}", [
                'exception' => $e,
                'playlist_url' => $playlistUrl,
            ]);

            if (! $dryRun) {
                YoutubeSyncLog::create([
                    'program_id' => $season->program_id,
                    'playlist_url' => $playlistUrl,
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'started_at' => $startedAt,
                    'finished_at' => now(),
                ]);
            }

            return [
                'success' => false,
                'program_id' => $season->program_id,
                'program_name' => $program?->name ?? 'Program',
                'season_number' => $season->season_number,
                'season_year' => $season->season_year,
                'playlist_url' => $playlistUrl,
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
                $season->update(['last_youtube_sync_at' => now()]);
                if ($program) {
                    $program->update(['last_youtube_sync_at' => now()]);
                }

                YoutubeSyncLog::create([
                    'program_id' => $season->program_id,
                    'playlist_url' => $playlistUrl,
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
                'program_id' => $season->program_id,
                'program_name' => $program?->name ?? 'Program',
                'season_number' => $season->season_number,
                'season_year' => $season->season_year,
                'playlist_url' => $playlistUrl,
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

        // Sort chronologically published_at ASC (Oldest -> Newest)
        usort($rawItems, function ($a, $b) {
            $timeA = ! empty($a['published_at']) ? strtotime($a['published_at']) : 0;
            $timeB = ! empty($b['published_at']) ? strtotime($b['published_at']) : 0;
            if ($timeA === $timeB) {
                return ($a['position'] ?? 0) <=> ($b['position'] ?? 0);
            }
            return $timeA <=> $timeB;
        });

        // Calculate max episode number in this season
        $seasonEpisodesQuery = Episode::where('program_id', $season->program_id);
        if ($season->season_number !== null) {
            $hasSeasonEpisodes = Episode::where('program_id', $season->program_id)
                ->where('season_number', $season->season_number)
                ->exists();

            if ($hasSeasonEpisodes) {
                $seasonEpisodesQuery->where('season_number', $season->season_number);
                if (filled($season->season_year)) {
                    $seasonEpisodesQuery->where('season_year', $season->season_year);
                }
            } else {
                $otherSeasonsExist = Episode::where('program_id', $season->program_id)
                    ->whereNotNull('season_number')
                    ->where('season_number', '!=', $season->season_number)
                    ->exists();

                if ($otherSeasonsExist || (int) $season->season_number > 1) {
                    $seasonEpisodesQuery->where('season_number', $season->season_number);
                } else {
                    $seasonEpisodesQuery->where(function ($q) use ($season) {
                        $q->where('season_number', $season->season_number)
                            ->orWhereNull('season_number');
                    });
                }
            }
        } else {
            $seasonEpisodesQuery->whereNull('season_number');
        }

        // Fetch existing episode URLs in THIS specific season
        $existingSeasonEpisodes = (clone $seasonEpisodesQuery)->whereNotNull('youtube_url')->get();
        $existingVideoIds = [];
        $existingEpisodesByVideoId = [];
        foreach ($existingSeasonEpisodes as $ep) {
            $vId = Youtube::extractVideoId($ep->youtube_url);
            if ($vId) {
                $existingVideoIds[$vId] = true;
                $existingEpisodesByVideoId[$vId] = $ep;
            }
        }

        $maxEpisodeNum = $seasonEpisodesQuery->max('episode_number') ?? 0;

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
                        $existingEp = $existingEpisodesByVideoId[$videoId] ?? null;

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

            // Mark as processed in-memory
            $existingVideoIds[$videoId] = true;
            $maxEpisodeNum++;

            $episodeData = [
                'program_id' => $season->program_id,
                'season_number' => $season->season_number,
                'season_year' => $season->season_year,
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
            $season->update(['last_youtube_sync_at' => now()]);
            if ($program) {
                $program->update(['last_youtube_sync_at' => now()]);
            }

            YoutubeSyncLog::create([
                'program_id' => $season->program_id,
                'playlist_url' => $playlistUrl,
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
            'program_id' => $season->program_id,
            'program_name' => $program?->name ?? 'Program',
            'season_number' => $season->season_number,
            'season_year' => $season->season_year,
            'playlist_url' => $playlistUrl,
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
     * Synchronize a single program's YouTube playlist (or specific season / series).
     */
    public function syncProgramPlaylist(
        Program $program,
        bool $dryRun = false,
        bool $updateExistingMetadata = false,
        ?int $seasonNumber = null,
        ?string $seasonYear = null,
        ?int $seriesId = null
    ): array {
        // If specific series requested
        if ($seriesId !== null) {
            $series = ProgramSeries::where('program_id', $program->id)->find($seriesId);
            if ($series && filled($series->youtube_playlist_url)) {
                return $this->syncSeries($series, $dryRun, $updateExistingMetadata);
            }
        }

        // If specific season requested
        if ($seasonNumber !== null || filled($seasonYear)) {
            $season = ProgramSeason::findSeason($program->id, $seasonNumber, $seasonYear);

            // Check if this season has subseries with playlists
            if ($season) {
                $subSeries = ProgramSeries::where('program_id', $program->id)
                    ->where('program_season_id', $season->id)
                    ->whereNotNull('youtube_playlist_url')
                    ->where('youtube_playlist_url', '!=', '')
                    ->get();

                if ($subSeries->isNotEmpty()) {
                    return $this->combineSyncSeriesResults($program, $subSeries, $dryRun, $updateExistingMetadata);
                }
            }

            if ($season && filled($season->youtube_playlist_url)) {
                return $this->syncSeason($season, $dryRun, $updateExistingMetadata);
            }

            // If no season record exists, but program has playlist URL, create a season record or fallback
            if (filled($program->youtube_playlist_url)) {
                $season = ProgramSeason::updateOrCreate(
                    [
                        'program_id' => $program->id,
                        'season_number' => $seasonNumber,
                        'season_year' => $seasonYear,
                    ],
                    [
                        'youtube_playlist_url' => $program->youtube_playlist_url,
                    ]
                );

                return $this->syncSeason($season, $dryRun, $updateExistingMetadata);
            }
        }

        // Check if program has series with playlists
        $allSeries = ProgramSeries::where('program_id', $program->id)
            ->whereNotNull('youtube_playlist_url')
            ->where('youtube_playlist_url', '!=', '')
            ->get();

        if ($allSeries->isNotEmpty()) {
            return $this->combineSyncSeriesResults($program, $allSeries, $dryRun, $updateExistingMetadata);
        }

        // If no series playlists, check if program has season-level playlists
        $seasons = ProgramSeason::where('program_id', $program->id)
            ->whereNotNull('youtube_playlist_url')
            ->where('youtube_playlist_url', '!=', '')
            ->get();

        if ($seasons->isNotEmpty()) {
            $combinedResult = [
                'success' => true,
                'program_id' => $program->id,
                'program_name' => $program->name,
                'playlist_url' => $seasons->first()->youtube_playlist_url,
                'total_items' => 0,
                'new_videos' => 0,
                'created_episodes' => 0,
                'updated_episodes' => 0,
                'unchanged_episodes' => 0,
                'skipped_existing' => 0,
                'errors' => 0,
                'dry_run' => $dryRun,
                'items' => [],
            ];

            foreach ($seasons as $s) {
                $res = $this->syncSeason($s, $dryRun, $updateExistingMetadata);
                $combinedResult['total_items'] += $res['total_items'] ?? 0;
                $combinedResult['new_videos'] += $res['new_videos'] ?? 0;
                $combinedResult['created_episodes'] += $res['created_episodes'] ?? 0;
                $combinedResult['updated_episodes'] += $res['updated_episodes'] ?? 0;
                $combinedResult['unchanged_episodes'] += $res['unchanged_episodes'] ?? 0;
                $combinedResult['skipped_existing'] += $res['skipped_existing'] ?? 0;
                $combinedResult['errors'] += $res['errors'] ?? 0;
                $combinedResult['items'] = array_merge($combinedResult['items'], $res['items'] ?? []);
                if (! ($res['success'] ?? true)) {
                    $combinedResult['success'] = false;
                    $combinedResult['message'] = $res['message'] ?? 'Senkronizasyon hatası';
                }
            }

            return $combinedResult;
        }

        // Fallback for program with only program-level URL
        if (filled($program->youtube_playlist_url)) {
            $season = ProgramSeason::updateOrCreate(
                [
                    'program_id' => $program->id,
                    'season_number' => 1,
                    'season_year' => null,
                ],
                [
                    'youtube_playlist_url' => $program->youtube_playlist_url,
                ]
            );

            return $this->syncSeason($season, $dryRun, $updateExistingMetadata);
        }

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

    protected function combineSyncSeriesResults(Program $program, $seriesCollection, bool $dryRun, bool $updateExistingMetadata): array
    {
        $combinedResult = [
            'success' => true,
            'program_id' => $program->id,
            'program_name' => $program->name,
            'playlist_url' => $seriesCollection->first()->youtube_playlist_url,
            'total_items' => 0,
            'new_videos' => 0,
            'created_episodes' => 0,
            'updated_episodes' => 0,
            'unchanged_episodes' => 0,
            'skipped_existing' => 0,
            'errors' => 0,
            'dry_run' => $dryRun,
            'items' => [],
        ];

        foreach ($seriesCollection as $series) {
            $res = $this->syncSeries($series, $dryRun, $updateExistingMetadata);
            $combinedResult['total_items'] += $res['total_items'] ?? 0;
            $combinedResult['new_videos'] += $res['new_videos'] ?? 0;
            $combinedResult['created_episodes'] += $res['created_episodes'] ?? 0;
            $combinedResult['updated_episodes'] += $res['updated_episodes'] ?? 0;
            $combinedResult['unchanged_episodes'] += $res['unchanged_episodes'] ?? 0;
            $combinedResult['skipped_existing'] += $res['skipped_existing'] ?? 0;
            $combinedResult['errors'] += $res['errors'] ?? 0;
            $combinedResult['items'] = array_merge($combinedResult['items'], $res['items'] ?? []);
            if (! ($res['success'] ?? true)) {
                $combinedResult['success'] = false;
                $combinedResult['message'] = $res['message'] ?? 'Senkronizasyon hatası';
            }
        }

        return $combinedResult;
    }

    /**
     * Synchronize all active program series and program seasons that have a configured YouTube playlist URL.
     */
    public function syncAllPlaylists(bool $dryRun = false): array
    {
        $allSeries = ProgramSeries::whereNotNull('youtube_playlist_url')
            ->where('youtube_playlist_url', '!=', '')
            ->with(['program', 'programSeason'])
            ->get();

        $seriesSeasonIds = $allSeries->pluck('program_season_id')->filter()->unique()->toArray();

        $seasons = ProgramSeason::whereNotNull('youtube_playlist_url')
            ->where('youtube_playlist_url', '!=', '')
            ->whereNotIn('id', $seriesSeasonIds)
            ->with('program')
            ->get();

        // Also check any legacy programs that have playlist URL without program_seasons or series row
        $existingProgIds = array_unique(array_merge(
            $allSeries->pluck('program_id')->toArray(),
            $seasons->pluck('program_id')->toArray()
        ));

        $legacyPrograms = Program::whereNotNull('youtube_playlist_url')
            ->where('youtube_playlist_url', '!=', '')
            ->whereNotIn('id', $existingProgIds)
            ->get();

        foreach ($legacyPrograms as $lp) {
            $s = ProgramSeason::create([
                'program_id' => $lp->id,
                'season_number' => 1,
                'season_year' => null,
                'youtube_playlist_url' => $lp->youtube_playlist_url,
            ]);
            $seasons->push($s);
        }

        $allProgramIds = array_unique(array_merge(
            $allSeries->pluck('program_id')->toArray(),
            $seasons->pluck('program_id')->toArray()
        ));

        $stats = [
            'checked_programs' => count($allProgramIds),
            'checked_playlists' => $allSeries->count() + $seasons->count(),
            'new_videos_found' => 0,
            'created_episodes' => 0,
            'skipped_existing' => 0,
            'errors' => 0,
            'dry_run' => $dryRun,
            'details' => [],
        ];

        foreach ($allSeries as $series) {
            $res = $this->syncSeries($series, $dryRun, false);
            $stats['new_videos_found'] += $res['new_videos'] ?? 0;
            $stats['created_episodes'] += $res['created_episodes'] ?? 0;
            $stats['skipped_existing'] += $res['skipped_existing'] ?? 0;
            $stats['errors'] += $res['errors'] ?? 0;
            $stats['details'][] = $res;
        }

        foreach ($seasons as $season) {
            $res = $this->syncSeason($season, $dryRun, false);
            $stats['new_videos_found'] += $res['new_videos'] ?? 0;
            $stats['created_episodes'] += $res['created_episodes'] ?? 0;
            $stats['skipped_existing'] += $res['skipped_existing'] ?? 0;
            $stats['errors'] += $res['errors'] ?? 0;
            $stats['details'][] = $res;
        }

        return $stats;
    }
}
