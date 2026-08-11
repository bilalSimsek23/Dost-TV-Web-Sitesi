<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Episodes\EpisodeResource;
use App\Filament\Resources\Episodes\Pages\CreateEpisode;
use App\Filament\Resources\Episodes\Pages\ListEpisodes;
use App\Models\Episode;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EpisodeResourceTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Program $program;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'administrator']);
        $this->program = Program::create([
            'name' => 'Söze Yar Olmak',
            'slug' => 'soze-yar-olmak',
            'status' => 'active',
            'is_active' => true,
            'youtube_playlist_url' => 'https://www.youtube.com/playlist?list=PLTEST123',
        ]);
    }

    public function test_authorized_user_can_access_episode_resource(): void
    {
        $this->actingAs($this->admin)
            ->get(EpisodeResource::getUrl())
            ->assertSuccessful();
    }

    public function test_same_program_and_season_episodes_are_grouped_into_single_row_with_accurate_count(): void
    {
        // 90 episodes created for Program in Season 1
        for ($i = 1; $i <= 90; $i++) {
            Episode::create([
                'program_id' => $this->program->id,
                'season_number' => 1,
                'episode_number' => $i,
                'title' => "Bölüm {$i}",
                'status' => 'published',
            ]);
        }

        Livewire::actingAs($this->admin)
            ->test(ListEpisodes::class)
            ->assertCanSeeTableRecords(
                Episode::query()
                    ->select('program_id', 'season_number', 'season_year', \Illuminate\Support\Facades\DB::raw('COUNT(id) as episodes_count'), \Illuminate\Support\Facades\DB::raw('MIN(id) as id'))
                    ->groupBy('program_id', 'season_number', 'season_year')
                    ->get()
            )
            ->assertSee('Söze Yar Olmak')
            ->assertSee('Sezon 1')
            ->assertSee('90 Bölüm')
            ->assertSee('Playlist Bağlı')
            ->assertActionExists('youtube_import')
            ->assertActionExists('create');
    }

    public function test_episodes_with_season_year_display_formatted_label_and_group_separately(): void
    {
        $prog = Program::create([
            'name' => 'Hikmet Arayışları',
            'slug' => 'hikmet-arayislari',
            'status' => 'active',
        ]);

        Episode::create([
            'program_id' => $prog->id,
            'season_number' => 1,
            'season_year' => 2017,
            'episode_number' => 1,
            'title' => '2017 Bölüm 1',
            'status' => 'published',
        ]);

        Episode::create([
            'program_id' => $prog->id,
            'season_number' => 1,
            'season_year' => 2025,
            'episode_number' => 1,
            'title' => '2025 Bölüm 1',
            'status' => 'published',
        ]);

        Episode::create([
            'program_id' => $prog->id,
            'season_number' => 1,
            'season_year' => null,
            'episode_number' => 1,
            'title' => 'Yılsız Bölüm 1',
            'status' => 'published',
        ]);

        Livewire::actingAs($this->admin)
            ->test(ListEpisodes::class)
            ->assertSee('Sezon 1 (2017)')
            ->assertSee('Sezon 1 (2025)')
            ->assertSee('Sezon 1');

        // Test Season Detail title with year
        Livewire::actingAs($this->admin)
            ->withQueryParams(['program_id' => $prog->id, 'season_number' => 1, 'season_year' => 2017])
            ->test(ListEpisodes::class)
            ->assertSee('Hikmet Arayışları — Sezon 1 (2017)');

        // Test Season Detail title without year
        Livewire::actingAs($this->admin)
            ->withQueryParams(['program_id' => $prog->id, 'season_number' => 1])
            ->test(ListEpisodes::class)
            ->assertSee('Hikmet Arayışları — Sezon 1');
    }

    public function test_create_episode_prefills_season_year_from_query_params(): void
    {
        $prog = Program::create([
            'name' => 'Yıllık Program',
            'slug' => 'yillik-program',
            'status' => 'active',
        ]);

        Livewire::actingAs($this->admin)
            ->withQueryParams(['program_id' => $prog->id, 'season_number' => 2, 'season_year' => 2026])
            ->test(CreateEpisode::class)
            ->assertSet('data.program_id', (string) $prog->id)
            ->assertSet('data.season_number', 2)
            ->assertSet('data.season_year', 2026);
    }

    public function test_edit_season_action_bulk_updates_season_number_and_year(): void
    {
        $prog = Program::create([
            'name' => 'Toplu Güncelleme Programı',
            'slug' => 'toplu-guncelleme-programi',
            'status' => 'active',
        ]);

        $episodes = [];
        for ($i = 1; $i <= 5; $i++) {
            $episodes[] = Episode::create([
                'program_id' => $prog->id,
                'season_number' => 1,
                'season_year' => null,
                'episode_number' => $i,
                'title' => "Bölüm {$i}",
                'youtube_url' => "https://youtube.com/watch?v=TEST_{$i}",
                'status' => 'published',
            ]);
        }

        $representativeRecord = Episode::where('program_id', $prog->id)->where('season_number', 1)->first();

        Livewire::actingAs($this->admin)
            ->test(ListEpisodes::class)
            ->callTableAction('edit_season', $representativeRecord, [
                'season_number' => 2,
                'season_year' => 2017,
            ])
            ->assertHasNoTableActionErrors();

        // Verify all 5 episodes are updated
        $updatedEpisodes = Episode::where('program_id', $prog->id)->get();
        $this->assertCount(5, $updatedEpisodes);
        foreach ($updatedEpisodes as $ep) {
            $this->assertEquals(2, $ep->season_number);
            $this->assertEquals(2017, $ep->season_year);
            $this->assertStringStartsWith('Bölüm ', $ep->title);
            $this->assertStringStartsWith('https://youtube.com/watch?v=TEST_', $ep->youtube_url);
            $this->assertEquals($prog->id, $ep->program_id);
        }
    }

    public function test_edit_season_action_prevents_conflict_with_existing_season(): void
    {
        $prog = Program::create([
            'name' => 'Çakışma Test Programı',
            'slug' => 'caxisma-test-programi',
            'status' => 'active',
        ]);

        $epGroup1 = Episode::create([
            'program_id' => $prog->id,
            'season_number' => 1,
            'season_year' => 2017,
            'episode_number' => 1,
            'title' => 'Grup 1 Bölüm 1',
            'status' => 'published',
        ]);

        $epGroup2 = Episode::create([
            'program_id' => $prog->id,
            'season_number' => 2,
            'season_year' => 2025,
            'episode_number' => 1,
            'title' => 'Grup 2 Bölüm 1',
            'status' => 'published',
        ]);

        // Attempt to rename Group 1 to match Group 2 (Season 2, 2025)
        Livewire::actingAs($this->admin)
            ->test(ListEpisodes::class)
            ->callTableAction('edit_season', $epGroup1, [
                'season_number' => 2,
                'season_year' => 2025,
            ]);

        // Verify Group 1 was NOT merged or modified
        $this->assertEquals(1, $epGroup1->fresh()->season_number);
        $this->assertEquals(2017, $epGroup1->fresh()->season_year);
    }

    public function test_different_seasons_and_programs_display_as_separate_grouped_rows(): void
    {
        $progB = Program::create([
            'name' => 'Akla Kapı',
            'slug' => 'akla-kapi',
            'status' => 'active',
            'is_active' => true,
        ]);

        Episode::create([
            'program_id' => $this->program->id,
            'season_number' => 1,
            'episode_number' => 1,
            'title' => 'Söze Yar Olmak S1',
            'status' => 'published',
        ]);

        Episode::create([
            'program_id' => $this->program->id,
            'season_number' => 2,
            'episode_number' => 1,
            'title' => 'Söze Yar Olmak S2',
            'status' => 'published',
        ]);

        Episode::create([
            'program_id' => $progB->id,
            'season_number' => 1,
            'episode_number' => 1,
            'title' => 'Akla Kapı S1',
            'video_source' => 'upload',
            'status' => 'published',
        ]);

        Livewire::actingAs($this->admin)
            ->test(ListEpisodes::class)
            ->assertSee('Söze Yar Olmak')
            ->assertSee('Sezon 1')
            ->assertSee('Sezon 2')
            ->assertSee('Akla Kapı')
            ->assertSee('Playlist Yok'); // progB has no playlist url and video_source upload
    }

    public function test_playlist_badge_states_differentiate_connected_imported_and_none(): void
    {
        // 1. Connected
        $progConnected = Program::create([
            'name' => 'Bağlı Program',
            'slug' => 'bagli-program',
            'youtube_playlist_url' => 'https://youtube.com/playlist?list=PLCONNECTED',
        ]);
        Episode::create(['program_id' => $progConnected->id, 'season_number' => 1, 'title' => 'Ep 1', 'status' => 'published']);

        // 2. Imported from playlist (no url in program, but youtube episode)
        $progImported = Program::create([
            'name' => 'Aktarılmış Program',
            'slug' => 'aktarilmis-program',
            'youtube_playlist_url' => null,
        ]);
        Episode::create([
            'program_id' => $progImported->id,
            'season_number' => 1,
            'title' => 'Ep 2',
            'video_source' => 'youtube',
            'youtube_url' => 'https://youtube.com/watch?v=TEST1234',
            'status' => 'published',
        ]);

        // 3. No playlist
        $progNone = Program::create([
            'name' => 'Playlist Olmayan Program',
            'slug' => 'playlist-olmayan-program',
            'youtube_playlist_url' => null,
        ]);
        Episode::create([
            'program_id' => $progNone->id,
            'season_number' => 1,
            'title' => 'Ep 3',
            'video_source' => 'upload',
            'status' => 'published',
        ]);

        Livewire::actingAs($this->admin)
            ->test(ListEpisodes::class)
            ->assertSee('Playlist Bağlı')
            ->assertSee('Playlistten Aktarıldı')
            ->assertSee('Playlist Yok');
    }

    public function test_season_detail_mode_filters_episodes_and_shows_contextual_actions(): void
    {
        $epS1 = Episode::create([
            'program_id' => $this->program->id,
            'season_number' => 1,
            'episode_number' => 1,
            'title' => 'Sezon 1 Bölüm',
            'status' => 'published',
        ]);

        $epS2 = Episode::create([
            'program_id' => $this->program->id,
            'season_number' => 2,
            'episode_number' => 1,
            'title' => 'Sezon 2 Bölüm',
            'status' => 'published',
        ]);

        Livewire::actingAs($this->admin)
            ->withQueryParams(['program_id' => $this->program->id, 'season_number' => 1])
            ->test(ListEpisodes::class)
            ->assertCanSeeTableRecords([$epS1])
            ->assertCanNotSeeTableRecords([$epS2])
            ->assertActionExists('back_to_main')
            ->assertActionExists('open_playlist_url')
            ->assertActionExists('sync_youtube_playlist')
            ->assertActionExists('youtube_import')
            ->assertActionExists('create_episode')
            ->assertSee('1 Bölüm')
            ->assertDontSee('Sezon ve Playlist Yönetim Paneli');
    }

    public function test_season_detail_mode_sorts_episodes_in_natural_asc_order(): void
    {
        $ep2 = Episode::create([
            'program_id' => $this->program->id,
            'season_number' => 1,
            'episode_number' => 2,
            'title' => 'Bölüm 2',
            'status' => 'published',
        ]);

        $ep1 = Episode::create([
            'program_id' => $this->program->id,
            'season_number' => 1,
            'episode_number' => 1,
            'title' => 'Bölüm 1',
            'status' => 'published',
        ]);

        Livewire::actingAs($this->admin)
            ->withQueryParams(['program_id' => $this->program->id, 'season_number' => 1])
            ->test(ListEpisodes::class)
            ->assertCanSeeTableRecords([$ep1, $ep2], inOrder: true);
    }

    public function test_season_detail_mode_can_archive_and_unarchive_episode(): void
    {
        $episode = Episode::create([
            'program_id' => $this->program->id,
            'season_number' => 1,
            'title' => 'Arşivlenecek Bölüm',
            'status' => 'published',
            'is_active' => true,
        ]);

        Livewire::actingAs($this->admin)
            ->withQueryParams(['program_id' => $this->program->id, 'season_number' => 1])
            ->test(ListEpisodes::class)
            ->callTableAction('archive', $episode)
            ->assertHasNoTableActionErrors();

        $this->assertEquals('archived', $episode->fresh()->status);
        $this->assertFalse($episode->fresh()->show_on_public);

        Livewire::actingAs($this->admin)
            ->withQueryParams(['program_id' => $this->program->id, 'season_number' => 1])
            ->test(ListEpisodes::class)
            ->callTableAction('unarchive', $episode)
            ->assertHasNoTableActionErrors();

        $this->assertEquals('published', $episode->fresh()->status);
        $this->assertTrue($episode->fresh()->show_on_public);
    }

    public function test_create_episode_prefills_program_id_and_season_number_from_query_parameters(): void
    {
        Livewire::actingAs($this->admin)
            ->withQueryParams(['program_id' => $this->program->id, 'season_number' => 2])
            ->test(CreateEpisode::class)
            ->assertSet('data.program_id', $this->program->id)
            ->assertSet('data.season_number', 2);
    }

    public function test_public_program_detail_page_displays_active_published_episodes(): void
    {
        Episode::create([
            'program_id' => $this->program->id,
            'title' => 'Yayındaki Bölüm',
            'status' => 'published',
            'is_active' => true,
            'show_on_public' => true,
        ]);

        $this->get('/programlar/soze-yar-olmak')
            ->assertStatus(200)
            ->assertSee('Yayındaki Bölüm');
    }
}

