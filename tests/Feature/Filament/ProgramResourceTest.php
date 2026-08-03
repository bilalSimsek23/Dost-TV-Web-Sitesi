<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Episodes\Pages\CreateEpisode;
use App\Filament\Resources\Programs\Pages\ListPrograms;
use App\Filament\Resources\Programs\ProgramResource;
use App\Models\Episode;
use App\Models\Program;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProgramResourceTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'administrator']);
    }

    public function test_authorized_user_can_access_program_resource(): void
    {
        $this->actingAs($this->admin)
            ->get(ProgramResource::getUrl())
            ->assertSuccessful();
    }

    public function test_programs_are_listed(): void
    {
        $program = Program::create([
            'name' => 'Test Programı',
            'slug' => 'test-programi',
            'status' => 'active',
            'is_active' => true,
        ]);

        Livewire::actingAs($this->admin)
            ->test(ListPrograms::class)
            ->assertCanSeeTableRecords([$program]);
    }

    public function test_can_create_new_program_with_status_and_seo(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ListPrograms::class)
            ->callAction('create', [
                'name' => 'Yeni Program',
                'slug' => 'yeni-program',
                'short_description' => 'Kısa açıklama metni',
                'description' => 'Detaylı açıklama metni',
                'status' => 'season_break',
                'show_on_public' => true,
                'is_featured' => true,
                'sort_order' => 1,
                'meta_title' => 'Yeni Program SEO Başlığı',
                'meta_description' => 'Yeni Program SEO Açıklaması',
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('programs', [
            'name' => 'Yeni Program',
            'status' => 'season_break',
            'meta_title' => 'Yeni Program SEO Başlığı',
            'meta_description' => 'Yeni Program SEO Açıklaması',
            'is_featured' => true,
        ]);
    }

    public function test_program_can_be_archived_and_unarchived_without_data_loss(): void
    {
        $program = Program::create([
            'name' => 'Arşivlenecek Program',
            'slug' => 'arsivlenecek-program',
            'status' => 'active',
            'is_active' => true,
            'show_on_public' => true,
        ]);

        $episode = Episode::create([
            'program_id' => $program->id,
            'title' => 'Bölüm 1',
        ]);

        Livewire::actingAs($this->admin)
            ->test(ListPrograms::class)
            ->callTableAction('archive', $program)
            ->assertHasNoTableActionErrors();

        $this->assertEquals('archived', $program->fresh()->status);
        $this->assertFalse($program->fresh()->show_on_public);
        $this->assertDatabaseHas('episodes', ['id' => $episode->id]);

        Livewire::actingAs($this->admin)
            ->test(ListPrograms::class)
            ->callTableAction('unarchive', $program)
            ->assertHasNoTableActionErrors();

        $this->assertEquals('active', $program->fresh()->status);
        $this->assertTrue($program->fresh()->show_on_public);
    }

    public function test_program_with_linked_episodes_cannot_be_deleted(): void
    {
        $program = Program::create([
            'name' => 'Bölümlü Program',
            'slug' => 'bolumlu-program',
            'status' => 'active',
        ]);

        Episode::create([
            'program_id' => $program->id,
            'title' => 'Bölüm 1',
        ]);

        Livewire::actingAs($this->admin)
            ->test(ListPrograms::class)
            ->assertTableActionHidden('delete', $program);
    }

    public function test_create_episode_page_prefills_program_id_from_query_parameter(): void
    {
        $program = Program::create([
            'name' => 'Özel Seçili Program',
            'slug' => 'ozel-secili-program',
            'status' => 'active',
        ]);

        Livewire::actingAs($this->admin)
            ->withQueryParams(['program_id' => $program->id])
            ->test(CreateEpisode::class)
            ->assertSet('data.program_id', (string) $program->id);
    }

    public function test_public_program_pages_return_status_200(): void
    {
        $program = Program::create([
            'name' => 'Public Program',
            'slug' => 'public-program',
            'status' => 'active',
            'is_active' => true,
            'show_on_public' => true,
        ]);

        $this->get('/programlar')->assertStatus(200);
        $this->get('/programlar/public-program')->assertStatus(200);
    }
}
