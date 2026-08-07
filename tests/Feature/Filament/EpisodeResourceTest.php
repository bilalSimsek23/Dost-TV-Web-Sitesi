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

    public function test_episodes_are_listed(): void
    {
        $episode = Episode::create([
            'program_id' => $this->program->id,
            'title' => 'Test Bölümü',
            'status' => 'published',
        ]);

        Livewire::actingAs($this->admin)
            ->test(ListEpisodes::class)
            ->assertCanSeeTableRecords([$episode]);
    }

    public function test_can_create_new_episode_with_program_selection_and_seo(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ListEpisodes::class)
            ->callAction('create', [
                'program_id' => $this->program->id,
                'season_number' => 1,
                'episode_number' => 12,
                'title' => 'Harika Bir Bölüm',
                'slug' => 'harika-bir-bolum',
                'description' => 'Bölüm detay açıklaması',
                'video_source' => 'youtube',
                'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'status' => 'published',
                'show_on_public' => true,
                'is_active' => true,
                'meta_title' => 'Harika Bölüm SEO',
                'meta_description' => 'Harika Bölüm SEO Açıklaması',
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('episodes', [
            'program_id' => $this->program->id,
            'title' => 'Harika Bir Bölüm',
            'episode_number' => 12,
            'status' => 'published',
            'meta_title' => 'Harika Bölüm SEO',
        ]);
    }

    public function test_create_episode_prefills_program_id_from_query_parameter(): void
    {
        Livewire::actingAs($this->admin)
            ->withQueryParams(['program_id' => $this->program->id])
            ->test(CreateEpisode::class)
            ->assertSet('data.program_id', (string) $this->program->id);
    }

    public function test_episode_can_be_archived_and_unarchived_without_data_loss(): void
    {
        $episode = Episode::create([
            'program_id' => $this->program->id,
            'title' => 'Arşivlenecek Bölüm',
            'status' => 'published',
            'is_active' => true,
        ]);

        Livewire::actingAs($this->admin)
            ->test(ListEpisodes::class)
            ->callTableAction('archive', $episode)
            ->assertHasNoTableActionErrors();

        $this->assertEquals('archived', $episode->fresh()->status);
        $this->assertFalse($episode->fresh()->show_on_public);

        Livewire::actingAs($this->admin)
            ->test(ListEpisodes::class)
            ->callTableAction('unarchive', $episode)
            ->assertHasNoTableActionErrors();

        $this->assertEquals('published', $episode->fresh()->status);
        $this->assertTrue($episode->fresh()->show_on_public);
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
