<?php

namespace Tests\Feature;

use App\Filament\Resources\Programs\Pages\EditProgram;
use App\Filament\Resources\Programs\RelationManagers\EpisodesRelationManager;
use App\Models\Episode;
use App\Models\Program;
use App\Models\User;
use App\Services\YouTube\YouTubePlaylistSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class YouTubePlaylistSyncTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Program $program;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'administrator']);
        $this->program = Program::create([
            'name' => 'Akla Kapı',
            'slug' => 'akla-kapi',
            'status' => 'active',
            'youtube_playlist_url' => 'https://www.youtube.com/playlist?list=PLAKLAKAPI123',
        ]);
    }

    public function test_sync_creates_episodes_only_for_new_youtube_videos(): void
    {
        Http::fake([
            '*' => Http::response([
                'items' => [
                    [
                        'snippet' => [
                            'resourceId' => ['videoId' => 'SYNC_VID_1'],
                            'title' => 'Akla Kapı | 1. Bölüm',
                            'description' => 'Açıklama 1',
                            'publishedAt' => '2026-08-01T10:00:00Z',
                            'position' => 0,
                        ],
                    ],
                    [
                        'snippet' => [
                            'resourceId' => ['videoId' => 'SYNC_VID_2'],
                            'title' => 'Akla Kapı | 2. Bölüm',
                            'description' => 'Açıklama 2',
                            'publishedAt' => '2026-08-02T10:00:00Z',
                            'position' => 1,
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new YouTubePlaylistSyncService();
        $result = $service->syncProgramPlaylist($this->program);

        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['new_videos']);
        $this->assertEquals(0, $result['skipped_existing']);

        $this->assertDatabaseHas('episodes', [
            'program_id' => $this->program->id,
            'title' => 'Akla Kapı | 1. Bölüm',
            'youtube_url' => 'https://www.youtube.com/watch?v=SYNC_VID_1',
            'episode_number' => 1,
            'status' => 'published',
        ]);

        $this->assertDatabaseHas('episodes', [
            'program_id' => $this->program->id,
            'title' => 'Akla Kapı | 2. Bölüm',
            'youtube_url' => 'https://www.youtube.com/watch?v=SYNC_VID_2',
            'episode_number' => 2,
        ]);
    }

    public function test_sync_skips_existing_episodes_by_youtube_video_id(): void
    {
        Episode::create([
            'program_id' => $this->program->id,
            'title' => 'Eski Kayıtlı Bölüm',
            'episode_number' => 1,
            'video_source' => 'youtube',
            'youtube_url' => 'https://www.youtube.com/watch?v=EXISTING100',
            'status' => 'published',
        ]);

        Http::fake([
            '*' => Http::response([
                'items' => [
                    [
                        'snippet' => [
                            'resourceId' => ['videoId' => 'EXISTING100'],
                            'title' => 'Mevcut Video',
                            'position' => 0,
                        ],
                    ],
                    [
                        'snippet' => [
                            'resourceId' => ['videoId' => 'NEW_VID_101'],
                            'title' => 'Yeni Video',
                            'position' => 1,
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new YouTubePlaylistSyncService();
        $result = $service->syncProgramPlaylist($this->program);

        $this->assertEquals(1, $result['new_videos']);
        $this->assertEquals(1, $result['skipped_existing']);

        // Episode count is 2 (1 existing + 1 new)
        $this->assertEquals(2, Episode::count());
        $this->assertDatabaseHas('episodes', [
            'program_id' => $this->program->id,
            'episode_number' => 2,
            'youtube_url' => 'https://www.youtube.com/watch?v=NEW_VID_101',
        ]);
    }

    public function test_dry_run_does_not_mutate_database(): void
    {
        Http::fake([
            '*' => Http::response([
                'items' => [
                    [
                        'snippet' => [
                            'resourceId' => ['videoId' => 'DRY_RUN_VID'],
                            'title' => 'Dry Run Video',
                            'position' => 0,
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new YouTubePlaylistSyncService();
        $result = $service->syncProgramPlaylist($this->program, true);

        $this->assertTrue($result['dry_run']);
        $this->assertEquals(1, $result['new_videos']);
        $this->assertEquals(0, Episode::count());
    }

    public function test_programs_without_playlist_url_are_skipped(): void
    {
        $noPlaylistProgram = Program::create([
            'name' => 'Playlist URL Yok',
            'slug' => 'playlist-url-yok',
            'status' => 'active',
            'youtube_playlist_url' => null,
        ]);

        $service = new YouTubePlaylistSyncService();
        $result = $service->syncProgramPlaylist($noPlaylistProgram);

        $this->assertTrue($result['success']);
        $this->assertEquals(0, $result['new_videos']);
    }

    public function test_api_error_does_not_fail_other_programs_or_corrupt_data(): void
    {
        $programB = Program::create([
            'name' => 'Program B',
            'slug' => 'program-b',
            'status' => 'active',
            'youtube_playlist_url' => 'https://www.youtube.com/playlist?list=PLPROGRAMB',
        ]);

        Http::fake([
            '*PLAKLAKAPI123*' => Http::response(['error' => ['message' => 'Quota exceeded']], 403),
            '*PLPROGRAMB*' => Http::response([
                'items' => [
                    [
                        'snippet' => [
                            'resourceId' => ['videoId' => 'PROGB_VID_1'],
                            'title' => 'Program B Bölüm 1',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new YouTubePlaylistSyncService();
        $stats = $service->syncAllPlaylists();

        $this->assertEquals(2, $stats['checked_programs']);
        $this->assertEquals(1, $stats['errors']);
        $this->assertEquals(1, $stats['new_videos_found']);
        $this->assertDatabaseHas('episodes', [
            'program_id' => $programB->id,
            'youtube_url' => 'https://www.youtube.com/watch?v=PROGB_VID_1',
        ]);
    }

    public function test_artisan_command_runs_successfully_and_outputs_stats(): void
    {
        Http::fake([
            '*' => Http::response([
                'items' => [
                    [
                        'snippet' => [
                            'resourceId' => ['videoId' => 'ARTISAN_VID_1'],
                            'title' => 'Artisan Video 1',
                        ],
                    ],
                ],
            ], 200),
        ]);

        Artisan::call('youtube:sync-playlists');
        $output = Artisan::output();

        $this->assertStringContainsString('Kontrol edilen program: 1', $output);
        $this->assertStringContainsString('Yeni video bulunan: 1', $output);
        $this->assertDatabaseHas('episodes', [
            'youtube_url' => 'https://www.youtube.com/watch?v=ARTISAN_VID_1',
        ]);
    }

    public function test_artisan_command_dry_run_flag(): void
    {
        Http::fake([
            '*' => Http::response([
                'items' => [
                    [
                        'snippet' => [
                            'resourceId' => ['videoId' => 'DRY_ARTISAN_1'],
                            'title' => 'Dry Artisan Video 1',
                        ],
                    ],
                ],
            ], 200),
        ]);

        Artisan::call('youtube:sync-playlists', ['--dry-run' => true]);
        $output = Artisan::output();

        $this->assertStringContainsString('SIMULATION MODE', $output);
        $this->assertEquals(0, Episode::count());
    }

    public function test_manual_sync_action_in_episodes_relation_manager(): void
    {
        Http::fake([
            '*' => Http::response([
                'items' => [
                    [
                        'snippet' => [
                            'resourceId' => ['videoId' => 'MANUAL_SYNC_1'],
                            'title' => 'Manuel Senkron Bölüm',
                        ],
                    ],
                ],
            ], 200),
        ]);

        Livewire::actingAs($this->admin)
            ->test(EpisodesRelationManager::class, [
                'ownerRecord' => $this->program,
                'pageClass' => EditProgram::class,
            ])
            ->callTableAction('sync_youtube_playlist')
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('episodes', [
            'program_id' => $this->program->id,
            'youtube_url' => 'https://www.youtube.com/watch?v=MANUAL_SYNC_1',
        ]);
    }

    public function test_last_youtube_sync_at_is_updated_on_successful_sync_and_log_is_created(): void
    {
        Http::fake([
            '*' => Http::response([
                'items' => [
                    [
                        'snippet' => [
                            'resourceId' => ['videoId' => 'SYNC_LOG_1'],
                            'title' => 'Sync Log Video 1',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new YouTubePlaylistSyncService();
        $service->syncProgramPlaylist($this->program);

        $this->assertNotNull($this->program->fresh()->last_youtube_sync_at);

        $this->assertDatabaseHas('youtube_sync_logs', [
            'program_id' => $this->program->id,
            'status' => 'success',
            'new_videos' => 1,
            'created_episodes' => 1,
        ]);
    }

    public function test_last_youtube_sync_at_is_not_updated_on_api_error_and_failed_log_is_created(): void
    {
        Http::fake([
            '*' => Http::response(['error' => ['message' => 'API Key invalid']], 400),
        ]);

        $service = new YouTubePlaylistSyncService();
        $service->syncProgramPlaylist($this->program);

        $this->assertNull($this->program->fresh()->last_youtube_sync_at);

        $this->assertDatabaseHas('youtube_sync_logs', [
            'program_id' => $this->program->id,
            'status' => 'failed',
        ]);
    }

    public function test_auto_sync_does_not_modify_existing_episode_metadata(): void
    {
        $existingEpisode = Episode::create([
            'program_id' => $this->program->id,
            'title' => 'Özel Editör Başlığı',
            'description' => 'Özel Editör Açıklaması',
            'episode_number' => 1,
            'video_source' => 'youtube',
            'youtube_url' => 'https://www.youtube.com/watch?v=EXIST_META_1',
            'status' => 'published',
        ]);

        Http::fake([
            '*' => Http::response([
                'items' => [
                    [
                        'snippet' => [
                            'resourceId' => ['videoId' => 'EXIST_META_1'],
                            'title' => 'YouTube Orijinal Başlık',
                            'description' => 'YouTube Orijinal Açıklama',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new YouTubePlaylistSyncService();
        $service->syncProgramPlaylist($this->program);

        $fresh = $existingEpisode->fresh();
        $this->assertEquals('Özel Editör Başlığı', $fresh->title);
        $this->assertEquals('Özel Editör Açıklaması', $fresh->description);
    }

    public function test_prune_logs_command_and_dry_run(): void
    {
        \Illuminate\Support\Facades\DB::table('youtube_sync_logs')->insert([
            'program_id' => $this->program->id,
            'status' => 'success',
            'created_at' => now()->subDays(100),
            'updated_at' => now()->subDays(100),
        ]);

        \Illuminate\Support\Facades\DB::table('youtube_sync_logs')->insert([
            'program_id' => $this->program->id,
            'status' => 'success',
            'created_at' => now()->subDays(10),
            'updated_at' => now()->subDays(10),
        ]);

        // Dry run
        Artisan::call('youtube:prune-sync-logs', ['--dry-run' => true]);
        $this->assertEquals(2, \App\Models\YoutubeSyncLog::count());

        // Actual prune
        Artisan::call('youtube:prune-sync-logs', ['--days' => 90]);
        $this->assertEquals(1, \App\Models\YoutubeSyncLog::count());
    }

    public function test_initial_import_orders_episodes_chronologically_by_published_at_asc(): void
    {
        Http::fake([
            '*' => Http::response([
                'items' => [
                    [
                        'snippet' => [
                            'resourceId' => ['videoId' => 'CHRONO_VID_C'],
                            'title' => 'Akla Kapı | Mart Bölümü',
                            'publishedAt' => '2024-03-01T10:00:00Z',
                            'position' => 0,
                        ],
                    ],
                    [
                        'snippet' => [
                            'resourceId' => ['videoId' => 'CHRONO_VID_A'],
                            'title' => 'Akla Kapı | Ocak Bölümü',
                            'publishedAt' => '2024-01-01T10:00:00Z',
                            'position' => 1,
                        ],
                    ],
                    [
                        'snippet' => [
                            'resourceId' => ['videoId' => 'CHRONO_VID_B'],
                            'title' => 'Akla Kapı | Şubat Bölümü',
                            'publishedAt' => '2024-02-01T10:00:00Z',
                            'position' => 2,
                        ],
                    ],
                ],
            ], 200),
        ]);

        Livewire::actingAs($this->admin)
            ->test(\App\Filament\Resources\Episodes\Pages\YoutubePlaylistImportPage::class)
            ->set('data.program_id', $this->program->id)
            ->set('data.playlist_url', 'https://www.youtube.com/playlist?list=PLCHRONO123')
            ->set('data.start_episode_number', 1)
            ->call('fetchPreview')
            ->call('importEpisodes');

        $janEp = Episode::where('youtube_url', 'like', '%CHRONO_VID_A%')->first();
        $febEp = Episode::where('youtube_url', 'like', '%CHRONO_VID_B%')->first();
        $marEp = Episode::where('youtube_url', 'like', '%CHRONO_VID_C%')->first();

        $this->assertEquals(1, $janEp->episode_number);
        $this->assertEquals(2, $febEp->episode_number);
        $this->assertEquals(3, $marEp->episode_number);
        $this->assertEquals('Akla Kapı | Ocak Bölümü', $janEp->title);
    }

    public function test_sync_orders_multiple_new_videos_chronologically_by_published_at_asc(): void
    {
        Episode::create([
            'program_id' => $this->program->id,
            'episode_number' => 27,
            'title' => 'Mevcut B27',
            'video_source' => 'youtube',
            'youtube_url' => 'https://www.youtube.com/watch?v=EXISTING270',
            'status' => 'published',
        ]);

        Http::fake([
            '*' => Http::response([
                'items' => [
                    [
                        'snippet' => [
                            'resourceId' => ['videoId' => 'EXISTING270'],
                            'title' => 'Mevcut B27',
                            'publishedAt' => '2026-07-01T10:00:00Z',
                        ],
                    ],
                    [
                        'snippet' => [
                            'resourceId' => ['videoId' => 'MULTINEW_103'],
                            'title' => 'Yeni Ağustos 05',
                            'publishedAt' => '2026-08-05T10:00:00Z',
                        ],
                    ],
                    [
                        'snippet' => [
                            'resourceId' => ['videoId' => 'MULTINEW_101'],
                            'title' => 'Yeni Ağustos 01',
                            'publishedAt' => '2026-08-01T10:00:00Z',
                        ],
                    ],
                    [
                        'snippet' => [
                            'resourceId' => ['videoId' => 'MULTINEW_102'],
                            'title' => 'Yeni Ağustos 03',
                            'publishedAt' => '2026-08-03T10:00:00Z',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new YouTubePlaylistSyncService();
        $res = $service->syncProgramPlaylist($this->program);

        $this->assertEquals(3, $res['new_videos']);

        $aug01 = Episode::where('youtube_url', 'like', '%MULTINEW_101%')->first();
        $aug03 = Episode::where('youtube_url', 'like', '%MULTINEW_102%')->first();
        $aug05 = Episode::where('youtube_url', 'like', '%MULTINEW_103%')->first();

        $this->assertEquals(28, $aug01->episode_number);
        $this->assertEquals(29, $aug03->episode_number);
        $this->assertEquals(30, $aug05->episode_number);
    }

    public function test_manual_sync_with_metadata_update_updates_existing_episodes_without_mutating_episode_number_or_slug(): void
    {
        $ep = Episode::create([
            'program_id' => $this->program->id,
            'title' => 'Eski Başlık',
            'description' => 'Eski Açıklama',
            'episode_number' => 12,
            'season_number' => 1,
            'slug' => 'ozel-sabitlemis-slug-12',
            'video_source' => 'youtube',
            'youtube_url' => 'https://www.youtube.com/watch?v=UPDATEMETA1',
            'status' => 'published',
            'is_active' => true,
            'show_on_public' => true,
            'sort_order' => 5,
        ]);

        Http::fake([
            '*' => Http::response([
                'items' => [
                    [
                        'snippet' => [
                            'resourceId' => ['videoId' => 'UPDATEMETA1'],
                            'title' => 'Güncellenmiş YouTube Başlığı',
                            'description' => 'Güncellenmiş YouTube Açıklaması',
                            'publishedAt' => '2026-08-07T12:00:00Z',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new YouTubePlaylistSyncService();
        $result = $service->syncProgramPlaylist($this->program, false, true);

        $this->assertEquals(1, $result['updated_episodes']);
        $this->assertEquals(0, $result['new_videos']);

        $fresh = $ep->fresh();
        $this->assertEquals('Güncellenmiş YouTube Başlığı', $fresh->title);
        $this->assertEquals('Güncellenmiş YouTube Açıklaması', $fresh->description);
        
        // Assert untouchable fields remained completely unchanged
        $this->assertEquals(12, $fresh->episode_number);
        $this->assertEquals(1, $fresh->season_number);
        $this->assertEquals('ozel-sabitlemis-slug-12', $fresh->slug);
        $this->assertEquals($this->program->id, $fresh->program_id);
        $this->assertEquals('published', $fresh->status);
        $this->assertTrue($fresh->show_on_public);
        $this->assertTrue($fresh->is_active);
        $this->assertEquals(5, $fresh->sort_order);
    }
}
