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
            'name' => 'Özel Test Programı',
            'slug' => 'ozel-test-programi',
            'status' => 'active',
            'is_active' => true,
        ]);
    }

    public function test_authorized_user_can_access_episode_resource(): void
    {
        $this->actingAs($this->admin)
            ->get(EpisodeResource::getUrl())
            ->assertSuccessful();
    }

    public function test_main_episodes_index_has_no_global_create_or_import_header_actions(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ListEpisodes::class)
            ->assertActionDoesNotExist('create')
            ->assertActionDoesNotExist('youtube_import');
    }

    public function test_episodes_main_index_groups_by_program_and_season(): void
    {
        $episode = Episode::create([
            'program_id' => $this->program->id,
            'season_number' => 1,
            'title' => 'Test Bölümü',
            'status' => 'published',
            'aired_at' => now(),
        ]);

        Livewire::actingAs($this->admin)
            ->test(ListEpisodes::class)
            ->assertCanSeeTableRecords([$episode]);
    }

    public function test_season_detail_mode_has_contextual_create_and_import_header_actions(): void
    {
        Livewire::actingAs($this->admin)
            ->withQueryParams(['program_id' => $this->program->id, 'season_number' => 1])
            ->test(ListEpisodes::class)
            ->assertActionExists('create_episode')
            ->assertActionExists('youtube_import')
            ->assertActionExists('back_to_main');
    }

    public function test_season_detail_mode_filters_episodes_by_program_and_season(): void
    {
        $epA = Episode::create([
            'program_id' => $this->program->id,
            'season_number' => 1,
            'title' => 'Sezon 1 Bölüm',
            'status' => 'published',
        ]);

        $epB = Episode::create([
            'program_id' => $this->program->id,
            'season_number' => 2,
            'title' => 'Sezon 2 Bölüm',
            'status' => 'published',
        ]);

        Livewire::actingAs($this->admin)
            ->withQueryParams(['program_id' => $this->program->id, 'season_number' => 1])
            ->test(ListEpisodes::class)
            ->assertCanSeeTableRecords([$epA])
            ->assertCanNotSeeTableRecords([$epB]);
    }

    public function test_can_create_new_episode_with_contextual_program_and_seo(): void
    {
        Livewire::actingAs($this->admin)
            ->withQueryParams(['program_id' => $this->program->id, 'season_number' => 1])
            ->test(CreateEpisode::class)
            ->fillForm([
                'episode_number' => 12,
                'title' => 'Harika Bir Bölüm',
                'slug' => 'harika-bir-bolum',
                'description' => 'Bölüm detay açıklaması',
                'video_source' => 'youtube',
                'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'meta_title' => 'Harika Bölüm SEO',
                'meta_description' => 'Harika Bölüm SEO Açıklaması',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('episodes', [
            'program_id' => $this->program->id,
            'season_number' => 1,
            'title' => 'Harika Bir Bölüm',
            'episode_number' => 12,
            'status' => 'published',
            'meta_title' => 'Harika Bölüm SEO',
        ]);
    }

    public function test_creating_episode_without_status_and_toggles_assigns_automatic_defaults(): void
    {
        Livewire::actingAs($this->admin)
            ->withQueryParams(['program_id' => $this->program->id, 'season_number' => 1])
            ->test(CreateEpisode::class)
            ->fillForm([
                'episode_number' => 15,
                'title' => 'Sadeleştirilmiş Form Bölümü',
                'slug' => 'sadelestirilmis-form-bolumu',
                'description' => 'Açıklama',
                'video_source' => 'youtube',
                'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('episodes', [
            'program_id' => $this->program->id,
            'season_number' => 1,
            'title' => 'Sadeleştirilmiş Form Bölümü',
            'episode_number' => 15,
            'status' => 'published',
            'show_on_public' => true,
            'is_active' => true,
            'sort_order' => 0,
        ]);
    }

    public function test_create_episode_prefills_program_id_and_season_number_from_query_parameters(): void
    {
        Livewire::actingAs($this->admin)
            ->withQueryParams(['program_id' => $this->program->id, 'season_number' => 3])
            ->test(CreateEpisode::class)
            ->assertSet('data.program_id', $this->program->id)
            ->assertSet('data.season_number', 3);
    }

    public function test_direct_create_without_context_allows_selecting_program_and_season(): void
    {
        Livewire::actingAs($this->admin)
            ->test(CreateEpisode::class)
            ->fillForm([
                'program_id' => $this->program->id,
                'season_number' => 2,
                'episode_number' => 1,
                'title' => 'Doğrudan Eklenen Bölüm',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('episodes', [
            'program_id' => $this->program->id,
            'season_number' => 2,
            'title' => 'Doğrudan Eklenen Bölüm',
        ]);
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

        $this->get('/programlar/ozel-test-programi')
            ->assertStatus(200)
            ->assertSee('Yayındaki Bölüm');
    }
}
