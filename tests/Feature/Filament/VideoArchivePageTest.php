<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\VideoArchivePage;
use App\Models\Category;
use App\Models\Episode;
use App\Models\Program;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VideoArchivePageTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'administrator']);
    }

    public function test_authorized_user_can_access_video_archive_page(): void
    {
        $this->actingAs($this->admin)
            ->get(VideoArchivePage::getUrl())
            ->assertSuccessful();
    }

    public function test_only_archived_programs_are_listed_in_video_archive(): void
    {
        $activeProgram = Program::create([
            'name' => 'Aktif Program',
            'slug' => 'aktif-program',
            'status' => 'active',
            'is_active' => true,
        ]);

        $archivedProgram = Program::create([
            'name' => 'Arşivli Program',
            'slug' => 'arsivli-program',
            'status' => 'archived',
            'is_active' => false,
        ]);

        Livewire::actingAs($this->admin)
            ->test(VideoArchivePage::class)
            ->assertCanSeeTableRecords([$archivedProgram])
            ->assertCanNotSeeTableRecords([$activeProgram]);
    }

    public function test_archived_program_data_episodes_and_categories_are_preserved(): void
    {
        $category = Category::create(['name' => 'Tarih', 'slug' => 'tarih']);
        $program = Program::create([
            'name' => 'Tarih Belgeseli',
            'slug' => 'tarih-belgeseli',
            'status' => 'archived',
        ]);
        $program->categories()->attach($category);

        $episode = Episode::create([
            'program_id' => $program->id,
            'title' => 'Tarih Bölüm 1',
            'status' => 'published',
        ]);

        $this->assertEquals(1, $program->episodes()->count());
        $this->assertEquals(1, $program->categories()->count());
        $this->assertDatabaseHas('episodes', ['id' => $episode->id, 'status' => 'published']);
        $this->assertDatabaseHas('category_program', ['category_id' => $category->id, 'program_id' => $program->id]);
    }

    public function test_unarchive_action_updates_status_to_chosen_value_and_removes_from_archive(): void
    {
        $program = Program::create([
            'name' => 'Yeniden Başlayacak Program',
            'slug' => 'yeniden-baslayacak-program',
            'status' => 'archived',
            'is_active' => false,
            'show_on_public' => false,
        ]);

        Livewire::actingAs($this->admin)
            ->test(VideoArchivePage::class)
            ->callTableAction('unarchive', $program, [
                'new_status' => 'season_break',
            ])
            ->assertHasNoTableActionErrors();

        $this->assertEquals('season_break', $program->fresh()->status);
        $this->assertDatabaseHas('programs', ['id' => $program->id, 'status' => 'season_break']);

        Livewire::actingAs($this->admin)
            ->test(VideoArchivePage::class)
            ->assertCanNotSeeTableRecords([$program]);
    }

    public function test_unarchive_to_active_sets_public_and_is_active_flags(): void
    {
        $program = Program::create([
            'name' => 'Aktife Dönecek Program',
            'slug' => 'aktife-donecek-program',
            'status' => 'archived',
            'is_active' => false,
            'show_on_public' => false,
        ]);

        Livewire::actingAs($this->admin)
            ->test(VideoArchivePage::class)
            ->callTableAction('unarchive', $program, [
                'new_status' => 'active',
            ])
            ->assertHasNoTableActionErrors();

        $this->assertEquals('active', $program->fresh()->status);
        $this->assertTrue($program->fresh()->show_on_public);
        $this->assertTrue($program->fresh()->is_active);
    }
}
