<?php

namespace Tests\Feature;

use App\Models\Episode;
use App\Models\Program;
use App\Models\ProgramSeason;
use App\Models\ProgramSeries;
use App\Services\YouTube\YouTubePlaylistImportService;
use App\Services\YouTube\YouTubePlaylistSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class YouTubeAutoSyncStatusFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function createMockImportService(array $mockItems = []): YouTubePlaylistImportService
    {
        $mock = Mockery::mock(YouTubePlaylistImportService::class);
        $mock->shouldReceive('fetchPlaylistItems')
            ->andReturn([
                'success' => true,
                'playlist_title' => 'Test Playlist',
                'total_items' => count($mockItems),
                'items' => $mockItems,
            ]);

        return $mock;
    }

    public function test_active_program_is_included_in_auto_sync(): void
    {
        $program = Program::create([
            'name' => 'Aktif Program',
            'slug' => 'aktif-program',
            'status' => 'active',
            'show_on_public' => true,
        ]);

        $season = ProgramSeason::create([
            'program_id' => $program->id,
            'season_number' => 1,
            'youtube_playlist_url' => 'https://www.youtube.com/playlist?list=PL_ACTIVE_TEST',
        ]);

        $mockItems = [
            [
                'title' => 'Aktif Video 1',
                'description' => 'Desc 1',
                'video_id' => 'vid_active_1',
                'youtube_url' => 'https://www.youtube.com/watch?v=vid_active_1',
                'thumbnail_url' => 'https://img.youtube.com/vi/vid_active_1/hqdefault.jpg',
                'published_at' => '2026-08-01 10:00:00',
            ],
        ];

        $service = new YouTubePlaylistSyncService($this->createMockImportService($mockItems));
        $stats = $service->syncAllPlaylists();

        $this->assertEquals(1, $stats['checked_programs']);
        $this->assertEquals(1, $stats['created_episodes']);
        $this->assertDatabaseHas('episodes', [
            'program_id' => $program->id,
            'youtube_url' => 'https://www.youtube.com/watch?v=vid_active_1',
        ]);

        $season->refresh();
        $this->assertNotNull($season->last_youtube_sync_at);
    }

    public function test_season_break_program_is_included_in_auto_sync(): void
    {
        $program = Program::create([
            'name' => 'Sezon Arası Program',
            'slug' => 'sezon-arasi-program',
            'status' => 'season_break',
            'show_on_public' => true,
        ]);

        $season = ProgramSeason::create([
            'program_id' => $program->id,
            'season_number' => 2,
            'youtube_playlist_url' => 'https://www.youtube.com/playlist?list=PL_SEASON_BREAK_TEST',
        ]);

        $mockItems = [
            [
                'title' => 'Sezon Arası Video',
                'description' => 'Desc SB',
                'video_id' => 'vid_sb_1',
                'youtube_url' => 'https://www.youtube.com/watch?v=vid_sb_1',
                'thumbnail_url' => 'https://img.youtube.com/vi/vid_sb_1/hqdefault.jpg',
                'published_at' => '2026-08-01 10:00:00',
            ],
        ];

        $service = new YouTubePlaylistSyncService($this->createMockImportService($mockItems));
        $stats = $service->syncAllPlaylists();

        $this->assertEquals(1, $stats['checked_programs']);
        $this->assertEquals(1, $stats['created_episodes']);
        $this->assertDatabaseHas('episodes', [
            'program_id' => $program->id,
            'youtube_url' => 'https://www.youtube.com/watch?v=vid_sb_1',
        ]);
    }

    public function test_season_break_with_show_on_public_false_is_still_included_in_auto_sync(): void
    {
        $program = Program::create([
            'name' => 'Gizli Sezon Arası Program',
            'slug' => 'gizli-sezon-arasi-program',
            'status' => 'season_break',
            'show_on_public' => false,
        ]);

        $this->assertFalse($program->is_active);

        $season = ProgramSeason::create([
            'program_id' => $program->id,
            'season_number' => 1,
            'youtube_playlist_url' => 'https://www.youtube.com/playlist?list=PL_HIDDEN_SB_TEST',
        ]);

        $mockItems = [
            [
                'title' => 'Gizli SB Video',
                'description' => 'Desc Hidden SB',
                'video_id' => 'vid_hidden_sb_1',
                'youtube_url' => 'https://www.youtube.com/watch?v=vid_hidden_sb_1',
                'thumbnail_url' => 'https://img.youtube.com/vi/vid_hidden_sb_1/hqdefault.jpg',
                'published_at' => '2026-08-01 10:00:00',
            ],
        ];

        $service = new YouTubePlaylistSyncService($this->createMockImportService($mockItems));
        $stats = $service->syncAllPlaylists();

        $this->assertEquals(1, $stats['checked_programs']);
        $this->assertEquals(1, $stats['created_episodes']);
        $this->assertDatabaseHas('episodes', [
            'program_id' => $program->id,
            'youtube_url' => 'https://www.youtube.com/watch?v=vid_hidden_sb_1',
        ]);
    }

    public function test_completed_program_is_excluded_from_auto_sync(): void
    {
        $program = Program::create([
            'name' => 'Biten Program',
            'slug' => 'biten-program',
            'status' => 'completed',
            'show_on_public' => false,
        ]);

        $season = ProgramSeason::create([
            'program_id' => $program->id,
            'season_number' => 1,
            'youtube_playlist_url' => 'https://www.youtube.com/playlist?list=PL_COMPLETED_TEST',
        ]);

        // Mock service should NOT receive any fetchPlaylistItems calls
        $mock = Mockery::mock(YouTubePlaylistImportService::class);
        $mock->shouldNotReceive('fetchPlaylistItems');

        $service = new YouTubePlaylistSyncService($mock);
        $stats = $service->syncAllPlaylists();

        $this->assertEquals(0, $stats['checked_programs']);
        $this->assertEquals(0, $stats['checked_playlists']);
        $this->assertEquals(0, $stats['created_episodes']);
        $this->assertDatabaseMissing('episodes', ['program_id' => $program->id]);

        $season->refresh();
        $this->assertNull($season->last_youtube_sync_at);
    }

    public function test_archived_program_is_excluded_from_auto_sync(): void
    {
        $program = Program::create([
            'name' => 'Arşivlenmiş Program',
            'slug' => 'arsivlenmis-program',
            'status' => 'archived',
            'show_on_public' => false,
        ]);

        $season = ProgramSeason::create([
            'program_id' => $program->id,
            'season_number' => 1,
            'youtube_playlist_url' => 'https://www.youtube.com/playlist?list=PL_ARCHIVED_TEST',
        ]);

        $series = ProgramSeries::create([
            'program_id' => $program->id,
            'name' => 'Arşiv Seri',
            'slug' => 'arsiv-seri',
            'youtube_playlist_url' => 'https://www.youtube.com/playlist?list=PL_ARCHIVED_SERIES_TEST',
        ]);

        // Mock service should NOT receive any fetchPlaylistItems calls
        $mock = Mockery::mock(YouTubePlaylistImportService::class);
        $mock->shouldNotReceive('fetchPlaylistItems');

        $service = new YouTubePlaylistSyncService($mock);
        $stats = $service->syncAllPlaylists();

        $this->assertEquals(0, $stats['checked_programs']);
        $this->assertEquals(0, $stats['checked_playlists']);
        $this->assertEquals(0, $stats['created_episodes']);
        $this->assertDatabaseMissing('episodes', ['program_id' => $program->id]);

        $season->refresh();
        $this->assertNull($season->last_youtube_sync_at);

        $series->refresh();
        $this->assertNull($series->last_youtube_sync_at);
    }

    public function test_manual_sync_works_on_completed_and_archived_programs(): void
    {
        $completedProgram = Program::create([
            'name' => 'Tamamlanan Program',
            'slug' => 'tamamlanan-program',
            'status' => 'completed',
            'show_on_public' => false,
        ]);

        $completedSeason = ProgramSeason::create([
            'program_id' => $completedProgram->id,
            'season_number' => 1,
            'youtube_playlist_url' => 'https://www.youtube.com/playlist?list=PL_COMPLETED_MANUAL',
        ]);

        $archivedProgram = Program::create([
            'name' => 'Arşiv Program',
            'slug' => 'arsiv-program',
            'status' => 'archived',
            'show_on_public' => false,
        ]);

        $archivedSeason = ProgramSeason::create([
            'program_id' => $archivedProgram->id,
            'season_number' => 1,
            'youtube_playlist_url' => 'https://www.youtube.com/playlist?list=PL_ARCHIVED_MANUAL',
        ]);

        $mockItems = [
            [
                'title' => 'Manuel Video 1',
                'description' => 'Desc Manual',
                'video_id' => 'vid_man_1',
                'youtube_url' => 'https://www.youtube.com/watch?v=vid_man_1',
                'thumbnail_url' => 'https://img.youtube.com/vi/vid_man_1/hqdefault.jpg',
                'published_at' => '2026-08-01 10:00:00',
            ],
        ];

        $service = new YouTubePlaylistSyncService($this->createMockImportService($mockItems));

        // Manual sync on completed program
        $resultCompleted = $service->syncProgramPlaylist($completedProgram, false, true);
        $this->assertTrue($resultCompleted['success']);
        $this->assertEquals(1, $resultCompleted['created_episodes']);
        $this->assertDatabaseHas('episodes', [
            'program_id' => $completedProgram->id,
            'youtube_url' => 'https://www.youtube.com/watch?v=vid_man_1',
        ]);

        // Manual sync on archived program
        $resultArchived = $service->syncProgramPlaylist($archivedProgram, false, true);
        $this->assertTrue($resultArchived['success']);
        $this->assertEquals(1, $resultArchived['created_episodes']);
        $this->assertDatabaseHas('episodes', [
            'program_id' => $archivedProgram->id,
            'youtube_url' => 'https://www.youtube.com/watch?v=vid_man_1',
        ]);
    }
}
