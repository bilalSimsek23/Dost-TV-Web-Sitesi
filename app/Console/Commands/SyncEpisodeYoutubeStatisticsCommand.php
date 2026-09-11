<?php

namespace App\Console\Commands;

use App\Models\Episode;
use App\Services\YouTube\YouTubePlaylistImportService;
use App\Support\Youtube;
use Illuminate\Console\Command;

class SyncEpisodeYoutubeStatisticsCommand extends Command
{
    protected $signature = 'youtube:sync-stats {--limit=500 : Maximum number of episodes to process} {--force : Re-sync even if view_count is set}';

    protected $description = 'Syncs YouTube statistics (view_count, like_count, comment_count) for DB episodes via YouTube Data API v3.';

    public function handle(YouTubePlaylistImportService $importService): int
    {
        $query = Episode::query()->whereNotNull('youtube_url');

        if (! $this->option('force')) {
            $query->whereNull('view_count');
        }

        $limit = (int) $this->option('limit');
        if ($limit > 0) {
            $query->limit($limit);
        }

        $episodes = $query->get();

        if ($episodes->isEmpty()) {
            $this->info('Senkronize edilecek video istatistiği bulunamadı veya tüm videolar güncel.');
            return self::SUCCESS;
        }

        $this->info("{$episodes->count()} adet bölüm için YouTube istatistikleri senkronize ediliyor...");

        $episodesByVideoId = [];
        foreach ($episodes as $episode) {
            $videoId = Youtube::extractVideoId($episode->youtube_url);
            if ($videoId) {
                $episodesByVideoId[$videoId][] = $episode;
            }
        }

        $videoIds = array_keys($episodesByVideoId);
        $totalVideos = count($videoIds);
        $this->info("Toplam {$totalVideos} adet benzersiz YouTube Video ID tespit edildi.");

        $statsMap = $importService->fetchVideoStatistics($videoIds);

        $updatedCount = 0;
        foreach ($statsMap as $vId => $stats) {
            if (! isset($episodesByVideoId[$vId])) {
                continue;
            }

            foreach ($episodesByVideoId[$vId] as $ep) {
                $ep->update([
                    'view_count' => $stats['view_count'] ?? null,
                    'like_count' => $stats['like_count'] ?? null,
                    'comment_count' => $stats['comment_count'] ?? null,
                ]);
                $updatedCount++;
            }
        }

        $this->info("İşlem tamamlandı! {$updatedCount} bölümün istatistikleri başarıyla güncellendi.");

        return self::SUCCESS;
    }
}
