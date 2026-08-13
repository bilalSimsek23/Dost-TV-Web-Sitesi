<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Episodes\Pages\CreateEpisode;
use App\Filament\Resources\Programs\Pages\EditProgram;
use App\Filament\Resources\Programs\Pages\ListPrograms;
use App\Filament\Resources\Programs\ProgramResource;
use App\Filament\Resources\Programs\RelationManagers\EpisodesRelationManager;
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

    public function test_can_create_new_program_through_simplified_form(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ListPrograms::class)
            ->callAction('create', [
                'name' => 'Yeni Program',
                'slug' => 'yeni-program',
                'short_description' => 'Kısa açıklama metni',
                'description' => 'Detaylı açıklama metni',
                'youtube_channel_url' => 'https://www.youtube.com/@dosttv',
                'is_featured' => true,
                'meta_title' => 'Yeni Program SEO Başlığı',
                'meta_description' => 'Yeni Program SEO Açıklaması',
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('programs', [
            'name' => 'Yeni Program',
            'status' => 'active',
            'show_on_public' => true,
            'is_active' => true,
            'youtube_channel_url' => 'https://www.youtube.com/@dosttv',
            'meta_title' => 'Yeni Program SEO Başlığı',
            'meta_description' => 'Yeni Program SEO Açıklaması',
            'is_featured' => true,
        ]);
    }

    public function test_program_form_persists_channel_url_and_preserves_existing_playlist_url(): void
    {
        $program = Program::create([
            'name' => 'Kanal URL Program',
            'slug' => 'kanal-url-program',
            'status' => 'active',
            'youtube_playlist_url' => 'https://www.youtube.com/playlist?list=PLPRESERVE123',
            'youtube_channel_url' => 'https://www.youtube.com/@eski',
        ]);

        Livewire::actingAs($this->admin)
            ->test(EditProgram::class, ['record' => $program->slug])
            ->fillForm([
                'youtube_channel_url' => 'https://www.youtube.com/@yeni_kanal',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $fresh = $program->fresh();
        $this->assertEquals('https://www.youtube.com/@yeni_kanal', $fresh->youtube_channel_url);
        $this->assertEquals('https://www.youtube.com/playlist?list=PLPRESERVE123', $fresh->youtube_playlist_url);
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

    public function test_removed_program_table_actions_are_not_rendered(): void
    {
        $program = Program::create([
            'name' => 'Sade Program',
            'slug' => 'sade-program',
            'status' => 'active',
        ]);

        Livewire::actingAs($this->admin)
            ->test(ListPrograms::class)
            ->assertTableActionExists('edit')
            ->assertTableActionExists('preview')
            ->assertTableActionExists('archive')
            ->assertTableActionDoesNotExist('open_episodes')
            ->assertTableActionDoesNotExist('add_episode')
            ->assertTableActionDoesNotExist('open_schedule')
            ->assertTableActionDoesNotExist('toggle_public');
    }

    public function test_public_toggle_column_toggles_show_on_public_and_is_active(): void
    {
        $program = Program::create([
            'name' => 'Görünür Program',
            'slug' => 'gorunur-program',
            'status' => 'active',
            'show_on_public' => true,
            'is_active' => true,
        ]);

        // Toggle to false
        Livewire::actingAs($this->admin)
            ->test(ListPrograms::class)
            ->callTableColumnAction('show_on_public', $program);

        $fresh = $program->fresh();
        $this->assertFalse($fresh->show_on_public);
        $this->assertFalse($fresh->is_active);

        // Toggle back to true
        Livewire::actingAs($this->admin)
            ->test(ListPrograms::class)
            ->callTableColumnAction('show_on_public', $fresh);

        $fresh2 = $program->fresh();
        $this->assertTrue($fresh2->show_on_public);
        $this->assertTrue($fresh2->is_active);
    }

    public function test_archived_program_cannot_be_made_public_directly_via_public_toggle(): void
    {
        $program = Program::create([
            'name' => 'Arşivli Program',
            'slug' => 'arsivli-program',
            'status' => 'archived',
            'show_on_public' => false,
            'is_active' => false,
        ]);

        Livewire::actingAs($this->admin)
            ->test(ListPrograms::class)
            ->callTableColumnAction('show_on_public', $program);

        $fresh = $program->fresh();
        $this->assertFalse($fresh->show_on_public);
        $this->assertFalse($fresh->is_active);
        $this->assertEquals('archived', $fresh->status);
    }

    public function test_featured_toggle_column_toggles_is_featured_on_single_click(): void
    {
        $program = Program::create([
            'name' => 'Öne Çıkan Aday Program',
            'slug' => 'one-cikan-aday-program',
            'status' => 'active',
            'is_featured' => false,
        ]);

        // First click: false -> true
        Livewire::actingAs($this->admin)
            ->test(ListPrograms::class)
            ->callTableColumnAction('is_featured', $program);

        $this->assertTrue($program->fresh()->is_featured);

        // Second click: true -> false
        Livewire::actingAs($this->admin)
            ->test(ListPrograms::class)
            ->callTableColumnAction('is_featured', $program);

        $this->assertFalse($program->fresh()->is_featured);
    }

    public function test_archive_category_and_management_archive_status_are_independent(): void
    {
        $archiveCategory = \App\Models\Category::create([
            'name' => 'Arşiv',
            'slug' => 'arsiv',
            'is_active' => true,
        ]);

        $program = Program::create([
            'name' => 'Bab-ı Reyyan',
            'slug' => 'bab-i-reyyan',
            'status' => 'active',
            'is_active' => true,
            'show_on_public' => true,
        ]);
        $program->categories()->attach($archiveCategory);

        $this->assertEquals('active', $program->fresh()->status);
        $this->assertTrue($program->fresh()->show_on_public);
        $this->assertTrue($program->fresh()->is_active);

        // Management archive action explicitly archives
        Livewire::actingAs($this->admin)
            ->test(ListPrograms::class)
            ->callTableAction('archive', $program)
            ->assertHasNoTableActionErrors();

        $fresh = $program->fresh();
        $this->assertEquals('archived', $fresh->status);
        $this->assertFalse($fresh->show_on_public);
        $this->assertEquals('Arşivlenmiş', Program::STATUSES[$fresh->status]);
        $this->assertTrue($fresh->categories->contains($archiveCategory));
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

    public function test_turkish_collation_sorting_uses_correct_collate_sql_statement(): void
    {
        $query = Program::query();
        
        // SQLite test
        \App\Filament\Resources\Programs\Tables\ProgramsTable::applyTurkishSort($query, 'name', 'asc');
        $this->assertStringContainsString("CASE WHEN status = 'archived' THEN 1 ELSE 0 END ASC", $query->toSql());
        $this->assertStringContainsString('LOWER(name) ASC', $query->toSql());
    }

    public function test_programs_default_sorting_places_non_archived_first_and_archived_at_bottom_in_alphabetical_order(): void
    {
        $archiveCategory = \App\Models\Category::create([
            'name' => 'Arşiv',
            'slug' => 'arsiv',
            'is_active' => true,
        ]);

        $progAkla = Program::create(['name' => 'Akla Kapı', 'slug' => 'akla-kapi', 'status' => 'active']);
        $progZ = Program::create(['name' => 'Z Programı', 'slug' => 'z-programi', 'status' => 'active']);
        $progBabi = Program::create(['name' => 'Bab-ı Reyyan', 'slug' => 'bab-i-reyyan', 'status' => 'active']);
        $progBabi->categories()->attach($archiveCategory);

        $progArchivedA = Program::create(['name' => 'A Eski Program', 'slug' => 'a-eski-program', 'status' => 'archived', 'show_on_public' => false, 'is_active' => false]);
        $progArchivedB = Program::create(['name' => 'B Eski Program', 'slug' => 'b-eski-program', 'status' => 'archived', 'show_on_public' => false, 'is_active' => false]);

        Livewire::actingAs($this->admin)
            ->test(ListPrograms::class)
            ->assertCanSeeTableRecords([
                $progAkla,
                $progBabi,
                $progZ,
                $progArchivedA,
                $progArchivedB,
            ], inOrder: true);
    }

    public function test_program_edit_episodes_relation_manager_only_shows_episodes_for_that_program(): void
    {
        $programA = Program::create([
            'name' => 'Program A',
            'slug' => 'program-a',
            'status' => 'active',
        ]);

        $programB = Program::create([
            'name' => 'Program B',
            'slug' => 'program-b',
            'status' => 'active',
        ]);

        $epA = Episode::create([
            'program_id' => $programA->id,
            'title' => 'Program A Bölüm 1',
            'episode_number' => 1,
            'status' => 'published',
        ]);

        $epB = Episode::create([
            'program_id' => $programB->id,
            'title' => 'Program B Bölüm 1',
            'episode_number' => 1,
            'status' => 'published',
        ]);

        Livewire::actingAs($this->admin)
            ->test(EpisodesRelationManager::class, [
                'ownerRecord' => $programA,
                'pageClass' => EditProgram::class,
            ])
            ->assertCanSeeTableRecords([$epA])
            ->assertCanNotSeeTableRecords([$epB]);
    }

    public function test_create_episode_in_relation_manager_defaults_to_owner_program(): void
    {
        $program = Program::create([
            'name' => 'Program X',
            'slug' => 'program-x',
            'status' => 'active',
        ]);

        Livewire::actingAs($this->admin)
            ->test(EpisodesRelationManager::class, [
                'ownerRecord' => $program,
                'pageClass' => EditProgram::class,
            ])
            ->callTableAction('create', data: [
                'title' => 'Yeni Bölüm X',
                'slug' => 'yeni-bolum-x',
                'episode_number' => 5,
                'status' => 'published',
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('episodes', [
            'program_id' => $program->id,
            'title' => 'Yeni Bölüm X',
            'episode_number' => 5,
        ]);
    }

    public function test_preview_action_generates_correct_public_url(): void
    {
        $hikmet = Program::create([
            'name' => 'Hikmet Arayışları',
            'slug' => 'hikmet-arayislari',
            'status' => 'active',
            'is_active' => true,
            'show_on_public' => true,
        ]);

        $akla = Program::create([
            'name' => 'Akla Kapı',
            'slug' => 'akla-kapi',
            'status' => 'active',
            'is_active' => true,
            'show_on_public' => true,
        ]);

        $this->assertEquals('/programlar/hikmet-arayislari', parse_url(route('programs.show', $hikmet), PHP_URL_PATH));
        $this->assertEquals('/programlar/akla-kapi', parse_url(route('programs.show', $akla), PHP_URL_PATH));

        $this->get(route('programs.show', $hikmet))->assertOk();
        $this->get(route('programs.show', $akla))->assertOk();

        Livewire::actingAs($this->admin)
            ->test(ListPrograms::class)
            ->assertTableActionExists('preview');
    }

    public function test_searching_programs_preserves_valid_edit_record_url(): void
    {
        $akla = Program::create([
            'name' => 'Akla Kapı',
            'slug' => 'akla-kapi',
            'status' => 'active',
            'is_active' => true,
            'show_on_public' => true,
        ]);

        $hikmet = Program::create([
            'name' => 'Hikmet Arayışları',
            'slug' => 'hikmet-arayislari',
            'status' => 'active',
            'is_active' => true,
            'show_on_public' => true,
        ]);

        Livewire::actingAs($this->admin)
            ->test(ListPrograms::class)
            ->set('tableSearch', 'akla')
            ->assertCanSeeTableRecords([$akla])
            ->assertCanNotSeeTableRecords([$hikmet])
            ->assertTableActionExists('edit', record: $akla);

        Livewire::actingAs($this->admin)
            ->test(ListPrograms::class)
            ->set('tableSearch', 'hikmet')
            ->assertCanSeeTableRecords([$hikmet])
            ->assertCanNotSeeTableRecords([$akla])
            ->assertTableActionExists('edit', record: $hikmet);


        // Verify both ID and slug access to edit page succeed
        $this->actingAs($this->admin)
            ->get(ProgramResource::getUrl('edit', ['record' => $akla->id]))
            ->assertOk();

        $this->actingAs($this->admin)
            ->get(ProgramResource::getUrl('edit', ['record' => $akla->slug]))
            ->assertOk();

        $this->actingAs($this->admin)
            ->get("/admin/programs/{$hikmet->id}/edit")
            ->assertOk();

        $this->actingAs($this->admin)
            ->get("/admin/programs/{$hikmet->slug}/edit")
            ->assertOk();
    }
}



