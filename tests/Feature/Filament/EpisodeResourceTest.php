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
                    ->select('program_id', 'season_number', \Illuminate\Support\Facades\DB::raw('COUNT(id) as episodes_count'), \Illuminate\Support\Facades\DB::raw('MIN(id) as id'))
                    ->groupBy('program_id', 'season_number')
                    ->get()
            )
            ->assertSee('Söze Yar Olmak')
            ->assertSee('Sezon 1')
            ->assertSee('90 Bölüm')
            ->assertSee('Playlist Bağlı')
            ->assertActionExists('create');
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
            ->assertActionExists('youtube_import')
            ->assertActionExists('create_episode');
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

