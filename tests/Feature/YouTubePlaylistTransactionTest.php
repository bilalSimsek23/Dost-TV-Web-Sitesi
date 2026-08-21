<?php

namespace Tests\Feature;

use App\Models\Episode;
use App\Models\Program;
use App\Models\ProgramSeason;
use App\Models\ProgramSeries;
use App\Models\YoutubeSyncLog;
use App\Services\YouTube\YouTubePlaylistImportService;
use App\Services\YouTube\YouTubePlaylistSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class YouTubePlaylistTransactionTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_successful_sync_commits_all_episodes_and_updates_timestamp_atomically(): void
    {
        $program = Program::create([
            'name' => 'Kuran Dersleri',
            'slug' => 'kuran-dersleri',
            'status' => 'active',
            'is_active' => true,
        ]);

        $season = ProgramSeason::create([
            'program_id' => $program->id,
            'season_number' => 1,
            'youtube_playlist_url' => 'https://www.youtube.com/playlist?list=PL_TEST_SUCCESS',
        ]);

        $mockImportService = Mockery::mock(YouTubePlaylistImportService::class);
        $mockImportService->shouldReceive('fetchPlaylistItems')
            ->once()
            ->with('https://www.youtube.com/playlist?list=PL_TEST_SUCCESS')
            ->andReturn([
                'success' => true,
                'playlist_title' => 'Kuran Dersleri Sezon 1',
                'items' => [
                    ['video_id' => 'vid_01', 'title' => 'Ders 1', 'published_at' => '2026-01-01 10:00:00', 'position' => 0],
                    ['video_id' => 'vid_02', 'title' => 'Ders 2', 'published_at' => '2026-01-02 10:00:00', 'position' => 1],
                    ['video_id' => 'vid_03', 'title' => 'Ders 3', 'published_at' => '2026-01-03 10:00:00', 'position' => 2],
                    ['video_id' => 'vid_04', 'title' => 'Ders 4', 'published_at' => '2026-01-04 10:00:00', 'position' => 3],
                    ['video_id' => 'vid_05', 'title' => 'Ders 5', 'published_at' => '2026-01-05 10:00:00', 'position' => 4],
                ],
            ]);

        $syncService = new YouTubePlaylistSyncService($mockImportService);
        $result = $syncService->syncSeason($season);

        $this->assertTrue($result['success']);
        $this->assertSame(5, $result['created_episodes']);
        $this->assertSame(5, Episode::where('program_id', $program->id)->count());

        $season->refresh();
        $program->refresh();
        $this->assertNotNull($season->last_youtube_sync_at);
        $this->assertNotNull($program->last_youtube_sync_at);

        $this->assertDatabaseHas('youtube_sync_logs', [
            'program_id' => $program->id,
            'playlist_url' => 'https://www.youtube.com/playlist?list=PL_TEST_SUCCESS',
            'status' => 'success',
            'created_episodes' => 5,
        ]);
    }

    public function test_exception_during_episode_creation_rolls_back_all_records(): void
    {
        $program = Program::create([
            'name' => 'Tefsir Programı',
            'slug' => 'tefsir-programi',
            'status' => 'active',
            'is_active' => true,
        ]);

        $season = ProgramSeason::create([
            'program_id' => $program->id,
            'season_number' => 1,
            'youtube_playlist_url' => 'https://www.youtube.com/playlist?list=PL_TEST_FAIL',
        ]);

        $mockImportService = Mockery::mock(YouTubePlaylistImportService::class);
        $mockImportService->shouldReceive('fetchPlaylistItems')
            ->once()
            ->with('https://www.youtube.com/playlist?list=PL_TEST_FAIL')
            ->andReturn([
                'success' => true,
                'playlist_title' => 'Tefsir Sezon 1',
                'items' => [
                    ['video_id' => 'v1', 'title' => 'Tefsir 1', 'published_at' => '2026-01-01 10:00:00', 'position' => 0],
                    ['video_id' => 'v2', 'title' => 'Tefsir 2', 'published_at' => '2026-01-02 10:00:00', 'position' => 1],
                    ['video_id' => 'v3', 'title' => 'Tefsir 3', 'published_at' => '2026-01-03 10:00:00', 'position' => 2],
                ],
            ]);

        // Trigger a simulated exception on saving the second episode
        Episode::saving(function (Episode $episode) {
            if ($episode->title === 'Tefsir 2') {
                throw new \RuntimeException('Simulated Database Write Failure on Episode 2');
            }
        });

        $syncService = new YouTubePlaylistSyncService($mockImportService);
        $result = $syncService->syncSeason($season);

        $this->assertFalse($result['success']);
        $this->assertSame(0, $result['created_episodes']);
        $this->assertSame(1, $result['errors']);

        // Assert COMPLETE ROLLBACK: 0 episodes exist in database
        $this->assertSame(0, Episode::where('program_id', $program->id)->count());

        // Assert timestamp was NOT updated
        $season->refresh();
        $this->assertNull($season->last_youtube_sync_at);

        // Assert failed log recorded
        $this->assertDatabaseHas('youtube_sync_logs', [
            'program_id' => $program->id,
            'playlist_url' => 'https://www.youtube.com/playlist?list=PL_TEST_FAIL',
            'status' => 'failed',
        ]);
    }

    public function test_failed_playlist_does_not_abort_subsequent_playlists_in_sync_all(): void
    {
        $program1 = Program::create(['name' => 'Program 1', 'slug' => 'program-1', 'status' => 'active', 'is_active' => true]);
        $season1 = ProgramSeason::create([
            'program_id' => $program1->id,
            'season_number' => 1,
            'youtube_playlist_url' => 'https://www.youtube.com/playlist?list=PL_FAIL_FIRST',
        ]);

        $program2 = Program::create(['name' => 'Program 2', 'slug' => 'program-2', 'status' => 'active', 'is_active' => true]);
        $season2 = ProgramSeason::create([
            'program_id' => $program2->id,
            'season_number' => 1,
            'youtube_playlist_url' => 'https://www.youtube.com/playlist?list=PL_SUCCESS_SECOND',
        ]);

        $mockImportService = Mockery::mock(YouTubePlaylistImportService::class);
        $mockImportService->shouldReceive('fetchPlaylistItems')
            ->with('https://www.youtube.com/playlist?list=PL_FAIL_FIRST')
            ->andThrow(new \RuntimeException('YouTube API Quota Error for Playlist 1'));

        $mockImportService->shouldReceive('fetchPlaylistItems')
            ->with('https://www.youtube.com/playlist?list=PL_SUCCESS_SECOND')
            ->andReturn([
                'success' => true,
                'playlist_title' => 'Program 2 Playlist',
                'items' => [
                    ['video_id' => 'p2_v1', 'title' => 'P2 Bölüm 1', 'published_at' => '2026-01-01 10:00:00', 'position' => 0],
                    ['video_id' => 'p2_v2', 'title' => 'P2 Bölüm 2', 'published_at' => '2026-01-02 10:00:00', 'position' => 1],
                ],
            ]);

        $syncService = new YouTubePlaylistSyncService($mockImportService);
        $stats = $syncService->syncAllPlaylists();

        // Program 1 failed, Program 2 succeeded
        $this->assertSame(2, $stats['created_episodes']);
        $this->assertSame(1, $stats['errors']);
        $this->assertSame(0, Episode::where('program_id', $program1->id)->count());
        $this->assertSame(2, Episode::where('program_id', $program2->id)->count());

        $season2->refresh();
        $this->assertNotNull($season2->last_youtube_sync_at);
    }
}
