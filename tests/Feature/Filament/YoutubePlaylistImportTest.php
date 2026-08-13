<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Episodes\Pages\YoutubePlaylistImportPage;
use App\Models\Episode;
use App\Models\Program;
use App\Models\User;
use App\Services\YouTube\YouTubePlaylistImportService;
use App\Support\Youtube;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class YoutubePlaylistImportTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Program $program;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'administrator']);
        $this->program = Program::create([
            'name' => 'Test Sohbetleri',
            'slug' => 'test-sohbetleri',
            'status' => 'active',
            'is_active' => true,
        ]);

        Config::set('services.youtube.key', 'TEST_YOUTUBE_API_KEY');
    }

    public function test_youtube_helper_extracts_ids_and_generates_urls(): void
    {
        $playlistUrl = 'https://www.youtube.com/playlist?list=PL123456789ABCDEF';
        $this->assertEquals('PL123456789ABCDEF', Youtube::extractPlaylistId($playlistUrl));

        $watchUrl = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ';
        $this->assertEquals('dQw4w9WgXcQ', Youtube::extractVideoId($watchUrl));

        $shortUrl = 'https://youtu.be/dQw4w9WgXcQ';
        $this->assertEquals('dQw4w9WgXcQ', Youtube::extractVideoId($shortUrl));

        $this->assertEquals('https://www.youtube.com/watch?v=dQw4w9WgXcQ', Youtube::canonicalUrl('dQw4w9WgXcQ'));
        $this->assertEquals('https://i.ytimg.com/vi/dQw4w9WgXcQ/hqdefault.jpg', Youtube::thumbnailUrl('dQw4w9WgXcQ'));
    }

    public function test_valid_playlist_url_parsing_and_api_fetching(): void
    {
        Http::fake([
            'https://www.googleapis.com/youtube/v3/playlistItems*' => Http::response([
                'items' => [
                    [
                        'snippet' => [
                            'resourceId' => ['videoId' => 'VIDEO_ID_1'],
                            'title' => 'Ders 1 - Giriş',
                            'description' => 'Açıklama metni 1',
                            'publishedAt' => '2026-08-01T10:00:00Z',
                            'position' => 0,
                        ],
                    ],
                    [
                        'snippet' => [
                            'resourceId' => ['videoId' => 'VIDEO_ID_2'],
                            'title' => 'Ders 2 - Detay',
                            'description' => 'Açıklama metni 2',
                            'publishedAt' => '2026-08-02T10:00:00Z',
                            'position' => 1,
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new YouTubePlaylistImportService();
        $result = $service->fetchPlaylistItems('https://www.youtube.com/playlist?list=PL123456789');

        $this->assertEquals('PL123456789', $result['playlist_id']);
        $this->assertEquals(2, $result['total_items']);
        $this->assertEquals('VIDEO_ID_1', $result['items'][0]['video_id']);
        $this->assertEquals('Ders 1 - Giriş', $result['items'][0]['title']);
        $this->assertEquals('https://www.youtube.com/watch?v=VIDEO_ID_1', $result['items'][0]['canonical_url']);
    }

    public function test_invalid_playlist_url_rejection(): void
    {
        $service = new YouTubePlaylistImportService();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Geçerli bir YouTube playlist bağlantısı girin.');

        $service->fetchPlaylistItems('https://google.com');
    }

    public function test_missing_api_key_throws_exception(): void
    {
        $_ENV['YOUTUBE_API_KEY'] = '';
        $_SERVER['YOUTUBE_API_KEY'] = '';
        putenv('YOUTUBE_API_KEY=');
        Config::set('services.youtube.key', '');

        $service = new YouTubePlaylistImportService();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('YouTube API anahtarı yapılandırılmamış');

        $service->fetchPlaylistItems('https://www.youtube.com/playlist?list=PL123456789');
    }

    public function test_playlist_pagination_fetches_multiple_pages(): void
    {
        Http::fake([
            'https://www.googleapis.com/youtube/v3/playlistItems*pageToken=PAGE2*' => Http::response([
                'items' => [
                    [
                        'snippet' => [
                            'resourceId' => ['videoId' => 'VIDEO_ID_2'],
                            'title' => 'Video Page 2',
                            'position' => 1,
                        ],
                    ],
                ],
            ], 200),
            'https://www.googleapis.com/youtube/v3/playlistItems*' => Http::response([
                'nextPageToken' => 'PAGE2',
                'items' => [
                    [
                        'snippet' => [
                            'resourceId' => ['videoId' => 'VIDEO_ID_1'],
                            'title' => 'Video Page 1',
                            'position' => 0,
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new YouTubePlaylistImportService();
        $result = $service->fetchPlaylistItems('https://www.youtube.com/playlist?list=PL123456789');

        $this->assertEquals(2, $result['total_items']);
        $this->assertEquals('VIDEO_ID_1', $result['items'][0]['video_id']);
        $this->assertEquals('VIDEO_ID_2', $result['items'][1]['video_id']);
    }

    public function test_authorized_user_can_access_import_page(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/episodes/youtube-import')
            ->assertStatus(200);
    }

    public function test_preview_fetches_videos_without_saving_to_database(): void
    {
        Http::fake([
            '*' => Http::response([
                'items' => [
                    [
                        'snippet' => [
                            'resourceId' => ['videoId' => 'PREVIEW_ID_1'],
                            'title' => 'Test Sohbetleri | Bölüm 1',
                            'description' => 'Açıklama 1',
                            'position' => 0,
                        ],
                    ],
                ],
            ], 200),
        ]);

        Livewire::actingAs($this->admin)
            ->test(YoutubePlaylistImportPage::class)
            ->set('data.program_id', $this->program->id)
            ->set('data.playlist_url', 'https://www.youtube.com/playlist?list=PLTEST123')
            ->set('data.strip_program_name', true)
            ->call('fetchPreview')
            ->assertSet('isPreviewLoaded', true)
            ->assertSet('newItemsCount', 1)
            ->assertSet('previewItems.0.processed_title', 'Bölüm 1');

        $this->assertEquals(0, Episode::count());
    }

    public function test_duplicate_youtube_videos_are_skipped(): void
    {
        // Existing episode in DB
        Episode::create([
            'program_id' => $this->program->id,
            'episode_number' => 1,
            'title' => 'Mevcut Bölüm',
            'video_source' => 'youtube',
            'youtube_url' => 'https://www.youtube.com/watch?v=EXISTING_ID',
            'status' => 'published',
        ]);

        Http::fake([
            '*' => Http::response([
                'items' => [
                    [
                        'snippet' => [
                            'resourceId' => ['videoId' => 'EXISTING_ID'],
                            'title' => 'Mevcut Video',
                            'position' => 0,
                        ],
                    ],
                    [
                        'snippet' => [
                            'resourceId' => ['videoId' => 'NEW_ID_999'],
                            'title' => 'Yeni Video',
                            'position' => 1,
                        ],
                    ],
                ],
            ], 200),
        ]);

        Livewire::actingAs($this->admin)
            ->test(YoutubePlaylistImportPage::class)
            ->set('data.program_id', $this->program->id)
            ->set('data.playlist_url', 'https://www.youtube.com/playlist?list=PLTEST123')
            ->call('fetchPreview')
            ->assertSet('newItemsCount', 1)
            ->assertSet('existingItemsCount', 1)
            ->call('importEpisodes')
            ->assertSet('importedCount', 1)
            ->assertSet('skippedCount', 1);

        $this->assertEquals(2, Episode::count());
        $this->assertDatabaseHas('episodes', [
            'youtube_url' => 'https://www.youtube.com/watch?v=NEW_ID_999',
            'program_id' => $this->program->id,
        ]);
    }

    public function test_episodes_are_created_with_canonical_url_and_incremented_episode_number(): void
    {
        Episode::create([
            'program_id' => $this->program->id,
            'season_number' => 1,
            'episode_number' => 5,
            'title' => 'Eski Bölüm 5',
            'video_source' => 'youtube',
            'youtube_url' => 'https://www.youtube.com/watch?v=OLD_EP_5',
            'status' => 'published',
        ]);

        Http::fake([
            '*' => Http::response([
                'items' => [
                    [
                        'snippet' => [
                            'resourceId' => ['videoId' => 'NEW_EP_6'],
                            'title' => 'Bölüm 6',
                            'position' => 0,
                        ],
                    ],
                    [
                        'snippet' => [
                            'resourceId' => ['videoId' => 'NEW_EP_7'],
                            'title' => 'Bölüm 7',
                            'position' => 1,
                        ],
                    ],
                ],
            ], 200),
        ]);

        Livewire::actingAs($this->admin)
            ->test(YoutubePlaylistImportPage::class)
            ->set('data.program_id', $this->program->id)
            ->set('data.season_number', 1)
            ->set('data.playlist_url', 'https://www.youtube.com/playlist?list=PLTEST123')
            ->call('fetchPreview')
            ->call('importEpisodes');


        $this->assertDatabaseHas('episodes', [
            'program_id' => $this->program->id,
            'episode_number' => 6,
            'youtube_url' => 'https://www.youtube.com/watch?v=NEW_EP_6',
            'video_source' => 'youtube',
        ]);

        $this->assertDatabaseHas('episodes', [
            'program_id' => $this->program->id,
            'episode_number' => 7,
            'youtube_url' => 'https://www.youtube.com/watch?v=NEW_EP_7',
            'video_source' => 'youtube',
        ]);
    }

    public function test_quota_error_and_private_playlist_error_handling(): void
    {
        Http::fake([
            'https://www.googleapis.com/youtube/v3/playlistItems*' => Http::response([
                'error' => [
                    'errors' => [['reason' => 'quotaExceeded']],
                    'message' => 'Quota exceeded',
                ],
            ], 403),
        ]);

        $service = new YouTubePlaylistImportService();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('YouTube API günlük kullanım kotası (quota) aşıldı.');

        $service->fetchPlaylistItems('https://www.youtube.com/playlist?list=PLQUOTA123');
    }

    public function test_page_renders_without_oversized_svg_icon(): void
    {
        $response = $this->actingAs($this->admin)
            ->get('/admin/episodes/youtube-import');

        $response->assertStatus(200);
        $response->assertDontSee('x-heroicon-o-arrow-down-tray');
    }

    public function test_program_selection_persists_in_form_state_without_redirect_or_reset(): void
    {
        Livewire::actingAs($this->admin)
            ->test(YoutubePlaylistImportPage::class)
            ->set('data.program_id', $this->program->id)
            ->assertSet('data.program_id', $this->program->id)
            ->assertSet('data.start_episode_number', 1);
    }

    public function test_preview_renders_compact_thumbnails_and_status_badges(): void
    {
        Http::fake([
            '*' => Http::response([
                'items' => [
                    [
                        'snippet' => [
                            'resourceId' => ['videoId' => 'PREVIEW_THUMB_1'],
                            'title' => 'Örnek Program | 1. Bölüm',
                            'publishedAt' => '2026-08-05T12:00:00Z',
                            'thumbnails' => ['high' => ['url' => 'https://img.youtube.com/vi/PREVIEW_THUMB_1/hqdefault.jpg']],
                        ],
                    ],
                ],
            ], 200),
        ]);

        Livewire::actingAs($this->admin)
            ->test(YoutubePlaylistImportPage::class)
            ->set('data.program_id', $this->program->id)
            ->set('data.playlist_url', 'https://www.youtube.com/playlist?list=PLTHUMB123')
            ->call('fetchPreview')
            ->assertSeeHtml('w-[120px] h-[68px]')
            ->assertSeeHtml('05.08.2026')
            ->assertSeeHtml('Yeni')
            ->assertDontSeeHtml('YouTube URL</th>');
    }

    public function test_contextual_import_prefills_program_playlist_url_season_and_start_episode(): void
    {
        $this->program->update(['youtube_playlist_url' => 'https://www.youtube.com/playlist?list=PLPROG123']);

        Episode::create([
            'program_id' => $this->program->id,
            'season_number' => 2,
            'season_year' => 2025,
            'episode_number' => 5,
            'title' => 'S2 B5',
            'status' => 'published',
        ]);

        Livewire::actingAs($this->admin)
            ->withQueryParams(['program_id' => $this->program->id, 'season_number' => 2, 'season_year' => 2025])
            ->test(YoutubePlaylistImportPage::class)
            ->assertSet('program_id', $this->program->id)
            ->assertSet('playlist_url', 'https://www.youtube.com/playlist?list=PLPROG123')
            ->assertSet('season_number', 2)
            ->assertSet('season_year', 2025)
            ->assertSet('start_episode_number', 6);
    }

    public function test_import_with_season_year_persists_season_year_to_episodes(): void
    {
        Http::fake([
            'https://www.googleapis.com/youtube/v3/playlistItems*' => Http::response([
                'items' => [
                    [
                        'snippet' => [
                            'title' => 'Özel Yıl Bölümü',
                            'description' => 'Yıllık Açıklama',
                            'position' => 0,
                            'publishedAt' => '2017-05-01T12:00:00Z',
                            'resourceId' => ['videoId' => 'YEAR_VID_123'],
                            'thumbnails' => ['high' => ['url' => 'https://img.youtube.com/vi/YEAR_VID_123/hqdefault.jpg']],
                        ],
                    ],
                ],
            ], 200),
        ]);

        Livewire::actingAs($this->admin)
            ->test(YoutubePlaylistImportPage::class)
            ->set('data.program_id', $this->program->id)
            ->set('data.playlist_url', 'https://www.youtube.com/playlist?list=PL_YEAR_123')
            ->set('data.season_number', 1)
            ->set('data.season_year', 2017)
            ->call('fetchPreview')
            ->call('importEpisodes')
            ->assertSet('isImported', true);

        $this->assertDatabaseHas('episodes', [
            'program_id' => $this->program->id,
            'season_number' => 1,
            'season_year' => 2017,
            'title' => 'Özel Yıl Bölümü',
        ]);
    }

    public function test_smart_season_and_year_suggestion_when_program_selected(): void
    {
        // Setup a program with Season 5 (2021) having 18 episodes
        $prog = Program::create([
            'name' => 'Hikmet Arayışları Test',
            'slug' => 'hikmet-arayislari-test',
            'status' => 'active',
            'is_active' => true,
        ]);

        for ($i = 1; $i <= 18; $i++) {
            Episode::create([
                'program_id' => $prog->id,
                'title' => "Bölüm {$i}",
                'episode_number' => $i,
                'season_number' => 5,
                'season_year' => 2021,
                'status' => 'published',
            ]);
        }

        // Test auto-suggestion on program selection
        $test = Livewire::actingAs($this->admin)
            ->test(YoutubePlaylistImportPage::class)
            ->set('data.program_id', $prog->id)
            ->assertSet('data.season_number', 6)
            ->assertSet('data.season_year', 2022)
            ->assertSet('data.start_episode_number', 1);

        // User changes to existing Season 5 (2021) -> start episode becomes 19
        $test->set('data.season_number', 5)
            ->set('data.season_year', 2021)
            ->assertSet('data.start_episode_number', 19);

        // User custom changes to new Season 7 -> start episode becomes 1
        $test->set('data.season_number', 7)
            ->set('data.season_year', 2023)
            ->assertSet('data.start_episode_number', 1);
    }

    public function test_smart_suggestion_when_latest_season_year_is_null(): void
    {
        $prog = Program::create([
            'name' => 'Yılsız Program',
            'slug' => 'yilsiz-program',
            'status' => 'active',
            'is_active' => true,
        ]);

        Episode::create([
            'program_id' => $prog->id,
            'title' => 'Bölüm 1',
            'episode_number' => 1,
            'season_number' => 3,
            'season_year' => null,
            'status' => 'published',
        ]);

        Livewire::actingAs($this->admin)
            ->test(YoutubePlaylistImportPage::class)
            ->set('data.program_id', $prog->id)
            ->assertSet('data.season_number', 4)
            ->assertSet('data.season_year', null)
            ->assertSet('data.start_episode_number', 1);
    }

    public function test_smart_suggestion_when_program_has_no_seasons(): void
    {
        $prog = Program::create([
            'name' => 'Hiç Sezonu Olmayan',
            'slug' => 'hic-sezonu-olmayan',
            'status' => 'active',
            'is_active' => true,
        ]);

        Episode::create([
            'program_id' => $prog->id,
            'title' => 'Düz Bölüm 1',
            'episode_number' => 1,
            'season_number' => null,
            'season_year' => null,
            'status' => 'published',
        ]);

        Livewire::actingAs($this->admin)
            ->test(YoutubePlaylistImportPage::class)
            ->set('data.program_id', $prog->id)
            ->assertSet('data.season_number', 1)
            ->assertSet('data.season_year', null)
            ->assertSet('data.start_episode_number', 1);
    }

    public function test_smart_season_and_year_suggestion_when_latest_season_is_a_year_range(): void
    {
        $prog = Program::create([
            'name' => 'Dönemli Playlist Programı',
            'slug' => 'donemli-playlist-programi',
            'status' => 'active',
            'is_active' => true,
        ]);

        for ($i = 1; $i <= 10; $i++) {
            Episode::create([
                'program_id' => $prog->id,
                'title' => "Dönem Bölüm {$i}",
                'episode_number' => $i,
                'season_number' => 5,
                'season_year' => '2021-2022',
                'status' => 'published',
            ]);
        }

        $test = Livewire::actingAs($this->admin)
            ->test(YoutubePlaylistImportPage::class)
            ->set('data.program_id', $prog->id)
            ->assertSet('data.season_number', 6)
            ->assertSet('data.season_year', '2022-2023')
            ->assertSet('data.start_episode_number', 1);

        // User switches back to Season 5 (2021-2022)
        $test->set('data.season_number', 5)
            ->set('data.season_year', '2021-2022')
            ->assertSet('data.start_episode_number', 11);
    }

    public function test_import_saves_program_season_record_without_mutating_other_seasons(): void
    {
        $prog = Program::create([
            'name' => 'Hikmet Arayışları Test Import',
            'slug' => 'hikmet-arayislari-test-import',
            'status' => 'active',
            'is_active' => true,
        ]);

        \App\Models\ProgramSeason::create([
            'program_id' => $prog->id,
            'season_number' => 1,
            'season_year' => '2017',
            'youtube_playlist_url' => 'https://www.youtube.com/playlist?list=PL_EXISTING_S1',
        ]);

        Http::fake([
            'https://www.googleapis.com/youtube/v3/playlistItems*' => Http::response([
                'items' => [
                    [
                        'snippet' => [
                            'title' => 'Hikmet Arayışları S3 Bölüm 1',
                            'description' => 'S3 Açıklama',
                            'position' => 0,
                            'publishedAt' => '2019-05-01T12:00:00Z',
                            'resourceId' => ['videoId' => 'HIKMET_S3_VID_1'],
                            'thumbnails' => ['high' => ['url' => 'https://img.youtube.com/vi/HIKMET_S3_VID_1/hqdefault.jpg']],
                        ],
                    ],
                ],
            ], 200),
        ]);

        Livewire::actingAs($this->admin)
            ->test(YoutubePlaylistImportPage::class)
            ->set('data.program_id', $prog->id)
            ->set('data.playlist_url', 'https://www.youtube.com/playlist?list=PL_NEW_S3_2019')
            ->set('data.season_number', 3)
            ->set('data.season_year', '2019')
            ->call('fetchPreview')
            ->call('importEpisodes')
            ->assertSet('isImported', true);

        // Verify Season 3 record was created with Playlist URL
        $s3 = \App\Models\ProgramSeason::findSeason($prog->id, 3, '2019');
        $this->assertNotNull($s3);
        $this->assertEquals('https://www.youtube.com/playlist?list=PL_NEW_S3_2019', $s3->youtube_playlist_url);

        // Verify Season 1 record was completely untouched
        $s1 = \App\Models\ProgramSeason::findSeason($prog->id, 1, '2017');
        $this->assertNotNull($s1);
        $this->assertEquals('https://www.youtube.com/playlist?list=PL_EXISTING_S1', $s1->youtube_playlist_url);
    }
}




