<?php

namespace Tests\Feature;

use App\Filament\Resources\Episodes\Pages\YoutubePlaylistImportPage;
use App\Models\Episode;
use App\Models\Program;
use App\Models\ProgramSeason;
use App\Models\ProgramSeries;
use App\Models\User;
use App\Services\YouTube\YouTubePlaylistImportService;
use App\Services\YouTube\YouTubePlaylistSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class ProgramSeriesManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.youtube.key', 'TEST_YOUTUBE_API_KEY');
    }

    public function test_series_creation_and_deduplication(): void
    {
        $program = Program::create(['name' => 'Beraber Okuyalım', 'slug' => 'beraber-okuyalim']);
        $season = ProgramSeason::create([
            'program_id' => $program->id,
            'season_number' => 1,
            'season_year' => '2026',
        ]);

        $series1 = ProgramSeries::findOrCreateSeries(
            $program->id,
            $season->id,
            'Lemalar',
            'https://www.youtube.com/playlist?list=PL_LEMALAR_1'
        );

        $this->assertNotNull($series1->id);
        $this->assertEquals('Lemalar', $series1->name);
        $this->assertEquals('lemalar', $series1->slug);
        $this->assertEquals('https://www.youtube.com/playlist?list=PL_LEMALAR_1', $series1->youtube_playlist_url);

        // Calling again with same name should return the existing series without creating duplicate
        $series1Duplicate = ProgramSeries::findOrCreateSeries(
            $program->id,
            $season->id,
            'lemalar',
            'https://www.youtube.com/playlist?list=PL_LEMALAR_1'
        );

        $this->assertEquals($series1->id, $series1Duplicate->id);
        $this->assertEquals(1, ProgramSeries::where('program_id', $program->id)->count());

        // Creating a second distinct series under the same program/season
        $series2 = ProgramSeries::findOrCreateSeries(
            $program->id,
            $season->id,
            'Sözler',
            'https://www.youtube.com/playlist?list=PL_SOZLER_1'
        );

        $this->assertNotNull($series2->id);
        $this->assertEquals('Sözler', $series2->name);
        $this->assertEquals(2, ProgramSeries::where('program_id', $program->id)->count());
    }

    public function test_import_with_subseries_creates_series_and_assigns_episodes(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);
        $program = Program::create(['name' => 'Beraber Okuyalım', 'slug' => 'beraber-okuyalim']);

        Http::fake([
            '*' => Http::response([
                'items' => [
                    [
                        'snippet' => [
                            'title' => 'Lemalar 1. Bölüm',
                            'description' => 'Birinci Lema tefsiri.',
                            'publishedAt' => '2026-01-10T10:00:00Z',
                            'position' => 0,
                            'resourceId' => ['videoId' => 'LEM_VID_001'],
                            'thumbnails' => ['high' => ['url' => 'https://img.youtube.com/vi/LEM_VID_001/hqdefault.jpg']],
                        ],
                    ],
                    [
                        'snippet' => [
                            'title' => 'Lemalar 2. Bölüm',
                            'description' => 'İkinci Lema tefsiri.',
                            'publishedAt' => '2026-01-17T10:00:00Z',
                            'position' => 1,
                            'resourceId' => ['videoId' => 'LEM_VID_002'],
                            'thumbnails' => ['high' => ['url' => 'https://img.youtube.com/vi/LEM_VID_002/hqdefault.jpg']],
                        ],
                    ],
                ],
                'nextPageToken' => null,
            ], 200),
        ]);

        Livewire::actingAs($admin)
            ->test(YoutubePlaylistImportPage::class)
            ->set('data.program_id', $program->id)
            ->set('data.season_number', 1)
            ->set('data.season_year', '2026')
            ->set('data.series_name', 'Lemalar')
            ->set('data.playlist_url', 'https://www.youtube.com/playlist?list=PL_LEM_TEST')
            ->call('fetchPreview')
            ->assertHasNoErrors()
            ->call('importEpisodes')
            ->assertHasNoErrors();

        // Verify Series created
        $series = ProgramSeries::where('program_id', $program->id)->where('name', 'Lemalar')->first();
        $this->assertNotNull($series);
        $this->assertEquals('https://www.youtube.com/playlist?list=PL_LEM_TEST', $series->youtube_playlist_url);

        // Verify Episodes created and linked to series
        $episodes = Episode::where('program_id', $program->id)->get();
        $this->assertCount(2, $episodes);

        foreach ($episodes as $ep) {
            $this->assertEquals(1, $ep->season_number);
            $this->assertEquals('2026', $ep->season_year);
            $this->assertEquals($series->id, $ep->program_series_id);
            $this->assertTrue($ep->programSeries->is($series));
        }
    }

    public function test_multiple_series_in_same_program_and_season_have_independent_playlists(): void
    {
        $program = Program::create(['name' => 'Beraber Okuyalım', 'slug' => 'beraber-okuyalim']);
        $season = ProgramSeason::create([
            'program_id' => $program->id,
            'season_number' => 1,
            'season_year' => '2026',
        ]);

        $lemalarSeries = ProgramSeries::create([
            'program_id' => $program->id,
            'program_season_id' => $season->id,
            'name' => 'Lemalar',
            'youtube_playlist_url' => 'https://www.youtube.com/playlist?list=PL_LEMALAR_PLAYLIST',
        ]);

        $sozlerSeries = ProgramSeries::create([
            'program_id' => $program->id,
            'program_season_id' => $season->id,
            'name' => 'Sözler',
            'youtube_playlist_url' => 'https://www.youtube.com/playlist?list=PL_SOZLER_PLAYLIST',
        ]);

        Episode::create([
            'program_id' => $program->id,
            'program_series_id' => $lemalarSeries->id,
            'season_number' => 1,
            'season_year' => '2026',
            'episode_number' => 1,
            'title' => 'Lemalar Bölüm 1',
            'youtube_url' => 'https://www.youtube.com/watch?v=LEM_001',
        ]);

        Episode::create([
            'program_id' => $program->id,
            'program_series_id' => $sozlerSeries->id,
            'season_number' => 1,
            'season_year' => '2026',
            'episode_number' => 1,
            'title' => 'Sözler Bölüm 1',
            'youtube_url' => 'https://www.youtube.com/watch?v=SOZ_001',
        ]);

        $this->assertEquals(1, $lemalarSeries->episodes()->count());
        $this->assertEquals(1, $sozlerSeries->episodes()->count());
        $this->assertEquals('https://www.youtube.com/playlist?list=PL_LEMALAR_PLAYLIST', $lemalarSeries->youtube_playlist_url);
        $this->assertEquals('https://www.youtube.com/playlist?list=PL_SOZLER_PLAYLIST', $sozlerSeries->youtube_playlist_url);
    }

    public function test_manual_sync_series_only_syncs_that_series(): void
    {
        $program = Program::create(['name' => 'Beraber Okuyalım', 'slug' => 'beraber-okuyalim']);
        $season = ProgramSeason::create([
            'program_id' => $program->id,
            'season_number' => 1,
            'season_year' => '2026',
        ]);

        $lemalarSeries = ProgramSeries::create([
            'program_id' => $program->id,
            'program_season_id' => $season->id,
            'name' => 'Lemalar',
            'youtube_playlist_url' => 'https://www.youtube.com/playlist?list=PL_LEMALAR',
        ]);

        $sozlerSeries = ProgramSeries::create([
            'program_id' => $program->id,
            'program_season_id' => $season->id,
            'name' => 'Sözler',
            'youtube_playlist_url' => 'https://www.youtube.com/playlist?list=PL_SOZLER',
        ]);

        Episode::create([
            'program_id' => $program->id,
            'program_series_id' => $lemalarSeries->id,
            'season_number' => 1,
            'season_year' => '2026',
            'episode_number' => 1,
            'title' => 'Lemalar 1',
            'youtube_url' => 'https://www.youtube.com/watch?v=LEM_OLD_001',
        ]);

        Http::fake([
            'https://www.googleapis.com/youtube/v3/playlistItems*playlistId=PL_LEMALAR*' => Http::response([
                'items' => [
                    [
                        'snippet' => [
                            'title' => 'Lemalar 1',
                            'description' => 'Mevcut video',
                            'publishedAt' => '2026-01-01T10:00:00Z',
                            'position' => 0,
                            'resourceId' => ['videoId' => 'LEM_OLD_001'],
                            'thumbnails' => ['high' => ['url' => 'https://img.youtube.com/vi/LEM_OLD_001/hqdefault.jpg']],
                        ],
                    ],
                    [
                        'snippet' => [
                            'title' => 'Lemalar 2 Yeni',
                            'description' => 'Yeni video',
                            'publishedAt' => '2026-01-08T10:00:00Z',
                            'position' => 1,
                            'resourceId' => ['videoId' => 'LEM_NEW_002'],
                            'thumbnails' => ['high' => ['url' => 'https://img.youtube.com/vi/LEM_NEW_002/hqdefault.jpg']],
                        ],
                    ],
                ],
                'nextPageToken' => null,
            ], 200),
            '*' => Http::response(['items' => []], 200),
        ]);

        $syncService = app(YouTubePlaylistSyncService::class);
        $result = $syncService->syncSeries($lemalarSeries);

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['created_episodes']);
        $this->assertEquals(1, $result['skipped_existing']);

        // Check new episode is assigned to Lemalar series with episode_number 2
        $newEp = Episode::where('youtube_url', 'like', '%LEM_NEW_002%')->first();
        $this->assertNotNull($newEp);
        $this->assertEquals($lemalarSeries->id, $newEp->program_series_id);
        $this->assertEquals(2, $newEp->episode_number);
        $this->assertEquals(1, $newEp->season_number);
        $this->assertEquals('2026', $newEp->season_year);

        // Sözler series has 0 episodes
        $this->assertEquals(0, $sozlerSeries->episodes()->count());
    }

    public function test_hourly_sync_scans_series_playlists_independently(): void
    {
        $program = Program::create(['name' => 'Beraber Okuyalım', 'slug' => 'beraber-okuyalim']);
        $season = ProgramSeason::create([
            'program_id' => $program->id,
            'season_number' => 1,
            'season_year' => '2026',
        ]);

        $lemalar = ProgramSeries::create([
            'program_id' => $program->id,
            'program_season_id' => $season->id,
            'name' => 'Lemalar',
            'youtube_playlist_url' => 'https://www.youtube.com/playlist?list=PL_LEMALAR',
        ]);

        $sozler = ProgramSeries::create([
            'program_id' => $program->id,
            'program_season_id' => $season->id,
            'name' => 'Sözler',
            'youtube_playlist_url' => 'https://www.youtube.com/playlist?list=PL_SOZLER',
        ]);

        // Regular program without series
        $aklaKapi = Program::create(['name' => 'Akla Kapı', 'slug' => 'akla-kapi']);
        $aklaKapiSeason = ProgramSeason::create([
            'program_id' => $aklaKapi->id,
            'season_number' => 1,
            'youtube_playlist_url' => 'https://www.youtube.com/playlist?list=PL_AKLA_KAPI',
        ]);

        Http::fake([
            'https://www.googleapis.com/youtube/v3/playlistItems*playlistId=PL_LEMALAR*' => Http::response([
                'items' => [
                    [
                        'snippet' => [
                            'title' => 'Lema 1',
                            'description' => 'Desc',
                            'publishedAt' => '2026-01-01T10:00:00Z',
                            'position' => 0,
                            'resourceId' => ['videoId' => 'LEM_AUTO_1'],
                            'thumbnails' => ['high' => ['url' => 'https://img.youtube.com/vi/LEM_AUTO_1/hqdefault.jpg']],
                        ],
                    ],
                ],
                'nextPageToken' => null,
            ], 200),
            'https://www.googleapis.com/youtube/v3/playlistItems*playlistId=PL_SOZLER*' => Http::response([
                'items' => [
                    [
                        'snippet' => [
                            'title' => 'Söz 1',
                            'description' => 'Desc',
                            'publishedAt' => '2026-01-02T10:00:00Z',
                            'position' => 0,
                            'resourceId' => ['videoId' => 'SOZ_AUTO_1'],
                            'thumbnails' => ['high' => ['url' => 'https://img.youtube.com/vi/SOZ_AUTO_1/hqdefault.jpg']],
                        ],
                    ],
                ],
                'nextPageToken' => null,
            ], 200),
            'https://www.googleapis.com/youtube/v3/playlistItems*playlistId=PL_AKLA_KAPI*' => Http::response([
                'items' => [
                    [
                        'snippet' => [
                            'title' => 'Akla Kapı B1',
                            'description' => 'Desc',
                            'publishedAt' => '2026-01-03T10:00:00Z',
                            'position' => 0,
                            'resourceId' => ['videoId' => 'AKLA_AUTO_1'],
                            'thumbnails' => ['high' => ['url' => 'https://img.youtube.com/vi/AKLA_AUTO_1/hqdefault.jpg']],
                        ],
                    ],
                ],
                'nextPageToken' => null,
            ], 200),
            '*' => Http::response(['items' => []], 200),
        ]);

        Artisan::call('youtube:sync-playlists');

        $this->assertEquals(1, Episode::where('program_id', $program->id)->where('program_series_id', $lemalar->id)->count());
        $this->assertEquals(1, Episode::where('program_id', $program->id)->where('program_series_id', $sozler->id)->count());
        $this->assertEquals(1, Episode::where('program_id', $aklaKapi->id)->whereNull('program_series_id')->count());
    }

    public function test_backward_compatibility_for_programs_without_series(): void
    {
        $program = Program::create(['name' => 'Hikmet Arayışları', 'slug' => 'hikmet-arayislari']);
        $season = ProgramSeason::create([
            'program_id' => $program->id,
            'season_number' => 1,
            'season_year' => '2017',
            'youtube_playlist_url' => 'https://www.youtube.com/playlist?list=PL_HIKMET',
        ]);

        $ep = Episode::create([
            'program_id' => $program->id,
            'season_number' => 1,
            'season_year' => '2017',
            'episode_number' => 1,
            'title' => 'Hikmet 1',
            'youtube_url' => 'https://www.youtube.com/watch?v=HIK_01',
        ]);

        $this->assertNull($ep->program_series_id);
        $this->assertNull($ep->programSeries);

        // Check public program route works without errors
        $response = $this->get('/programlar/hikmet-arayislari');
        $response->assertStatus(200);
        $response->assertSee('Hikmet 1');
    }

    public function test_public_program_controller_series_filter(): void
    {
        $program = Program::create(['name' => 'Beraber Okuyalım', 'slug' => 'beraber-okuyalim']);
        $season = ProgramSeason::create([
            'program_id' => $program->id,
            'season_number' => 1,
            'season_year' => '2026',
        ]);

        $lemalar = ProgramSeries::create([
            'program_id' => $program->id,
            'program_season_id' => $season->id,
            'name' => 'Lemalar',
            'slug' => 'lemalar',
        ]);

        $sozler = ProgramSeries::create([
            'program_id' => $program->id,
            'program_season_id' => $season->id,
            'name' => 'Sözler',
            'slug' => 'sozler',
        ]);

        Episode::create([
            'program_id' => $program->id,
            'program_series_id' => $lemalar->id,
            'season_number' => 1,
            'season_year' => '2026',
            'episode_number' => 1,
            'title' => 'Lemalar Özel Dersi',
            'youtube_url' => 'https://www.youtube.com/watch?v=LEM_SPEC_1',
            'show_on_public' => true,
        ]);

        Episode::create([
            'program_id' => $program->id,
            'program_series_id' => $sozler->id,
            'season_number' => 1,
            'season_year' => '2026',
            'episode_number' => 1,
            'title' => 'Sözler Özel Dersi',
            'youtube_url' => 'https://www.youtube.com/watch?v=SOZ_SPEC_1',
            'show_on_public' => true,
        ]);

        // Default: returns all episodes in the season
        $resDefault = $this->get('/programlar/beraber-okuyalim?season=1&year=2026');
        $resDefault->assertStatus(200);
        $resDefault->assertSee('Lemalar Özel Dersi');
        $resDefault->assertSee('Sözler Özel Dersi');

        // Filtered by series=lemalar
        $resLemalar = $this->get('/programlar/beraber-okuyalim?season=1&year=2026&series=lemalar');
        $resLemalar->assertStatus(200);
        $resLemalar->assertSee('Lemalar Özel Dersi');
        $resLemalar->assertDontSee('Sözler Özel Dersi');

        // Filtered by series=sozler
        $resSozler = $this->get('/programlar/beraber-okuyalim?season=1&year=2026&series=sozler');
        $resSozler->assertStatus(200);
        $resSozler->assertSee('Sözler Özel Dersi');
        $resSozler->assertDontSee('Lemalar Özel Dersi');
    }

    public function test_public_program_page_hides_season_numbers_and_displays_series_names_in_season_desc_order(): void
    {
        $program = Program::create(['name' => 'Beraber Okuyalım', 'slug' => 'beraber-okuyalim']);

        $s4 = ProgramSeason::create([
            'program_id' => $program->id,
            'season_number' => 4,
            'season_year' => '2026',
        ]);
        $s3 = ProgramSeason::create([
            'program_id' => $program->id,
            'season_number' => 3,
            'season_year' => '2026',
        ]);
        $s2 = ProgramSeason::create([
            'program_id' => $program->id,
            'season_number' => 2,
            'season_year' => '2026',
        ]);

        $sozler = ProgramSeries::create([
            'program_id' => $program->id,
            'program_season_id' => $s4->id,
            'name' => 'Sözler',
            'slug' => 'sozler',
        ]);

        $lemalar = ProgramSeries::create([
            'program_id' => $program->id,
            'program_season_id' => $s3->id,
            'name' => 'Lemalar',
            'slug' => 'lemalar',
        ]);

        $mektubat = ProgramSeries::create([
            'program_id' => $program->id,
            'program_season_id' => $s2->id,
            'name' => 'Mektubat',
            'slug' => 'mektubat',
        ]);

        Episode::create([
            'program_id' => $program->id,
            'program_series_id' => $sozler->id,
            'season_number' => 4,
            'episode_number' => 1,
            'title' => 'Sözler 1. Video',
            'youtube_url' => 'https://www.youtube.com/watch?v=SOZ_PUB_01',
            'show_on_public' => true,
        ]);

        Episode::create([
            'program_id' => $program->id,
            'program_series_id' => $lemalar->id,
            'season_number' => 3,
            'episode_number' => 1,
            'title' => 'Lemalar 1. Video',
            'youtube_url' => 'https://www.youtube.com/watch?v=LEM_PUB_01',
            'show_on_public' => true,
        ]);

        Episode::create([
            'program_id' => $program->id,
            'program_series_id' => $mektubat->id,
            'season_number' => 2,
            'episode_number' => 1,
            'title' => 'Mektubat 1. Video',
            'youtube_url' => 'https://www.youtube.com/watch?v=MEK_PUB_01',
            'show_on_public' => true,
        ]);

        $response = $this->get('/programlar/beraber-okuyalim');
        $response->assertStatus(200);

        // 1. Season numbers MUST NOT be displayed for public users
        $response->assertDontSee('Sezon 4');
        $response->assertDontSee('Sezon 3');
        $response->assertDontSee('Sezon 2');
        $response->assertDontSee('Sezon 1');
        $response->assertDontSee('Sezonlar');

        // 2. Series names must be displayed as headers
        $response->assertSee('Sözler');
        $response->assertSee('Lemalar');
        $response->assertSee('Mektubat');

        // 3. Must be ordered in season_number DESC order (Sözler -> Lemalar -> Mektubat)
        $content = $response->getContent();
        $posSozler = strpos($content, 'Sözler');
        $posLemalar = strpos($content, 'Lemalar');
        $posMektubat = strpos($content, 'Mektubat');

        $this->assertNotFalse($posSozler);
        $this->assertNotFalse($posLemalar);
        $this->assertNotFalse($posMektubat);
        $this->assertTrue($posSozler < $posLemalar, 'Sözler (Season 4) should appear before Lemalar (Season 3)');
        $this->assertTrue($posLemalar < $posMektubat, 'Lemalar (Season 3) should appear before Mektubat (Season 2)');

        // 4. Each series must display its own videos
        $response->assertSee('Sözler 1. Video');
        $response->assertSee('Lemalar 1. Video');
        $response->assertSee('Mektubat 1. Video');
    }

    public function test_series_video_grid_renders_all_episodes_with_short_labels(): void
    {
        $program = Program::create(['name' => 'Beraber Okuyalım', 'slug' => 'beraber-okuyalim']);

        $season = ProgramSeason::create([
            'program_id' => $program->id,
            'season_number' => 1,
            'season_year' => '2026',
        ]);

        // Series A with 40 videos
        $seriesA = ProgramSeries::create([
            'program_id' => $program->id,
            'program_season_id' => $season->id,
            'name' => '1–10. Söz',
            'slug' => '1-10-soz',
        ]);

        for ($i = 1; $i <= 40; $i++) {
            $vidId = sprintf('SOZ%02dVID123', $i);
            Episode::create([
                'program_id' => $program->id,
                'program_series_id' => $seriesA->id,
                'season_number' => 1,
                'episode_number' => $i,
                'title' => "Risale-i Nur Külliyatı 1–10. Söz {$i}. Uzun Başlık Dersi",
                'youtube_url' => "https://www.youtube.com/watch?v={$vidId}",
                'show_on_public' => true,
            ]);
        }

        // Series B with 3 videos
        $seriesB = ProgramSeries::create([
            'program_id' => $program->id,
            'program_season_id' => $season->id,
            'name' => 'Lemalar',
            'slug' => 'lemalar',
        ]);

        for ($i = 1; $i <= 3; $i++) {
            $vidId = sprintf('LEM%02dVID123', $i);
            Episode::create([
                'program_id' => $program->id,
                'program_series_id' => $seriesB->id,
                'season_number' => 1,
                'episode_number' => $i,
                'title' => "Lemalar {$i}. Uzun Başlık Dersi",
                'youtube_url' => "https://www.youtube.com/watch?v={$vidId}",
                'aired_at' => '2025-01-09',
                'show_on_public' => true,
            ]);
        }

        $response = $this->get('/programlar/beraber-okuyalim');
        $response->assertStatus(200);

        // 1. Total counts are preserved in badges
        $response->assertSee('40 Video');
        $response->assertSee('3 Video');

        // 2. Short episode labels (1. Bölüm, 2. Bölüm, ...) are rendered
        for ($i = 1; $i <= 40; $i++) {
            $response->assertSee("{$i}. Bölüm");
        }

        // 3. Grid classes, 16:9 aspect ratio, and lazy loading thumbnails are present
        $response->assertSee('grid-cols-2');
        $response->assertSee('lg:grid-cols-4');
        $response->assertSee('aspect-video');
        $response->assertSee('loading="lazy"', false);
        $response->assertSee('i.ytimg.com/vi/SOZ01VID123/hqdefault.jpg');

        // 4. Sliders and arrows are removed
        $response->assertDontSee('slider-track');
        $response->assertDontSee('slider-prev');
        $response->assertDontSee('slider-next');

        // 5. Long video titles are in title="..." tooltip attribute, NOT in card visible label
        $response->assertSee('title="Risale-i Nur Külliyatı 1–10. Söz 1. Uzun Başlık Dersi"', false);

        // 6. Broadcast dates (aired_at) are NOT rendered on public video cards
        $response->assertDontSee('09.01.2025');
    }

    public function test_import_allows_and_creates_episodes_for_videos_existing_in_other_series(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);
        $program = Program::create(['name' => 'Beraber Okuyalım', 'slug' => 'beraber-okuyalim']);

        $season1 = ProgramSeason::create([
            'program_id' => $program->id,
            'season_number' => 1,
            'season_year' => '2026',
        ]);

        $seriesSozler = ProgramSeries::create([
            'program_id' => $program->id,
            'program_season_id' => $season1->id,
            'name' => 'Sözler',
            'slug' => 'sozler',
        ]);

        // Create 40 episodes in Sözler
        for ($i = 1; $i <= 40; $i++) {
            $vidId = sprintf('SOZ%02dCOMMON', $i); // 11 characters
            Episode::create([
                'program_id' => $program->id,
                'program_series_id' => $seriesSozler->id,
                'season_number' => 1,
                'season_year' => '2026',
                'episode_number' => $i,
                'title' => "Sözler {$i}. Bölüm",
                'youtube_url' => "https://www.youtube.com/watch?v={$vidId}",
                'status' => 'published',
            ]);
        }

        $this->assertEquals(40, Episode::where('program_series_id', $seriesSozler->id)->count());

        // Prepare 40 items for 1-10. Söz playlist (37 overlap with Sözler, 3 are brand new)
        $playlistItems = [];
        for ($i = 1; $i <= 40; $i++) {
            $vidId = ($i <= 37) ? sprintf('SOZ%02dCOMMON', $i) : sprintf('NEW%02dCOMMON', $i); // 11 characters
            $playlistItems[] = [
                'snippet' => [
                    'resourceId' => ['videoId' => $vidId],
                    'title' => "1-10. Söz {$i}. Bölüm",
                    'description' => "1-10. Söz Ders {$i}",
                    'publishedAt' => '2026-08-01T10:00:00Z',
                    'position' => $i - 1,
                ],
            ];
        }

        Http::fake([
            '*' => Http::response(['items' => $playlistItems], 200),
        ]);

        // Test Livewire import page
        $livewire = Livewire::actingAs($admin)
            ->test(\App\Filament\Resources\Episodes\Pages\YoutubePlaylistImportPage::class)
            ->set('data.program_id', $program->id)
            ->set('data.season_number', 2)
            ->set('data.season_year', '2026')
            ->set('data.series_name', '1-10. Söz')
            ->set('data.playlist_url', 'https://www.youtube.com/playlist?list=PL_1_10_SOZ_40')
            ->set('data.start_episode_number', 1)
            ->call('fetchPreview');

        // Check preview counts
        $this->assertEquals(40, $livewire->get('totalItemsCount'));
        $this->assertEquals(3, $livewire->get('newItemsCount'));
        $this->assertEquals(37, $livewire->get('otherSeriesItemsCount'));
        $this->assertEquals(0, $livewire->get('targetExistingItemsCount'));
        $this->assertEquals(40, $livewire->get('willImportCount'));

        // Import the 40 episodes
        $livewire->call('importEpisodes');

        $series1to10 = ProgramSeries::where('program_id', $program->id)->where('name', '1-10. Söz')->first();
        $this->assertNotNull($series1to10);

        // 1. Target series contains all 40 episodes
        $this->assertEquals(40, Episode::where('program_series_id', $series1to10->id)->count());

        // 2. Original series still contains all its 40 episodes
        $this->assertEquals(40, Episode::where('program_series_id', $seriesSozler->id)->count());

        // 3. Program now has 80 episodes in total
        $this->assertEquals(80, Episode::where('program_id', $program->id)->count());

        // 4. Test re-importing the same playlist skips target duplicates
        $reLivewire = Livewire::actingAs($admin)
            ->test(\App\Filament\Resources\Episodes\Pages\YoutubePlaylistImportPage::class)
            ->set('data.program_id', $program->id)
            ->set('data.season_number', 2)
            ->set('data.season_year', '2026')
            ->set('data.series_name', '1-10. Söz')
            ->set('data.playlist_url', 'https://www.youtube.com/playlist?list=PL_1_10_SOZ_40')
            ->call('fetchPreview');

        $this->assertEquals(40, $reLivewire->get('totalItemsCount'));
        $this->assertEquals(40, $reLivewire->get('targetExistingItemsCount'));
        $this->assertEquals(0, $reLivewire->get('willImportCount'));

        $reLivewire->call('importEpisodes');
        $this->assertEquals(40, Episode::where('program_series_id', $series1to10->id)->count());
    }
}
