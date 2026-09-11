<?php

namespace Tests\Feature;

use App\Filament\Resources\ProgramCollections\Pages\EditProgramCollection;
use App\Filament\Resources\ProgramCollections\RelationManagers\AutoResolvedProgramsRelationManager;
use App\Models\Category;
use App\Models\HomepageLayout;
use App\Models\Program;
use App\Models\ProgramCollection;
use App\Models\ScheduleTemplate;
use App\Models\ScheduleTemplateItem;
use App\Models\User;
use App\Services\Home\HomepageDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProgramCollectionTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'email' => 'admin@dosttv.com',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_access_and_create_program_collection()
    {
        $this->actingAs($this->admin);

        $response = $this->get('/admin/program-collections');
        $response->assertStatus(200);

        $collection = ProgramCollection::create([
            'name' => 'Aile ve Çocuk',
            'slug' => 'aile-ve-cocuk',
            'description' => 'Aile kategorisindeki programlar',
            'source_type' => 'manual',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('program_collections', [
            'id' => $collection->id,
            'name' => 'Aile ve Çocuk',
            'source_type' => 'manual',
        ]);
    }

    public function test_manual_collection_program_attach_detach_and_sort_order()
    {
        $prog1 = Program::factory()->create(['name' => 'Program A', 'show_on_public' => true, 'is_active' => true]);
        $prog2 = Program::factory()->create(['name' => 'Program B', 'show_on_public' => true, 'is_active' => true]);

        $collection = ProgramCollection::create([
            'name' => 'Editörün Seçtikleri',
            'source_type' => 'manual',
            'is_active' => true,
        ]);

        $collection->programs()->attach($prog1->id, ['sort_order' => 2]);
        $collection->programs()->attach($prog2->id, ['sort_order' => 1]);

        $resolved = $collection->resolvePrograms();

        $this->assertCount(2, $resolved);
        // Order by pivot sort_order: Prog B (sort_order=1) then Prog A (sort_order=2)
        $this->assertEquals($prog2->id, $resolved->first()->id);
        $this->assertEquals($prog1->id, $resolved->last()->id);

        // Detach prog2
        $collection->programs()->detach($prog2->id);

        $resolvedAfterDetach = $collection->resolvePrograms();
        $this->assertCount(1, $resolvedAfterDetach);
        $this->assertEquals($prog1->id, $resolvedAfterDetach->first()->id);
    }

    public function test_attached_programs_are_filtered_out_from_attach_action_options()
    {
        $progAttached = Program::factory()->create(['name' => 'Zaten Ekli Program', 'show_on_public' => true, 'is_active' => true]);
        $progAvailable = Program::factory()->create(['name' => 'Müsait Program', 'show_on_public' => true, 'is_active' => true]);

        $collection = ProgramCollection::create([
            'name' => 'Manuel Koleksiyon',
            'source_type' => 'manual',
            'is_active' => true,
        ]);

        $collection->programs()->attach($progAttached->id, ['sort_order' => 1]);

        $relationManager = Livewire::test(
            \App\Filament\Resources\ProgramCollections\RelationManagers\ProgramsRelationManager::class,
            ['ownerRecord' => $collection, 'pageClass' => EditProgramCollection::class]
        );

        $relationManager->assertStatus(200);

        // Verify options do not contain already attached program
        $attachedIds = $collection->programs()->pluck('programs.id')->all();
        $availableOptions = Program::query()
            ->where('show_on_public', true)
            ->where('is_active', true)
            ->whereNotIn('id', $attachedIds)
            ->pluck('name', 'id')
            ->toArray();

        $this->assertArrayHasKey($progAvailable->id, $availableOptions);
        $this->assertArrayNotHasKey($progAttached->id, $availableOptions);
    }

    public function test_category_collection_live_automation()
    {
        $category = Category::create(['name' => 'İslam ve Hayat', 'slug' => 'islam-ve-hayat', 'is_active' => true]);

        $prog1 = Program::factory()->create(['name' => 'Mektubat', 'show_on_public' => true, 'is_active' => true]);
        $prog2 = Program::factory()->create(['name' => 'Kavram Atölyesi', 'show_on_public' => true, 'is_active' => true]);

        $prog1->categories()->attach($category->id);

        $collection = ProgramCollection::create([
            'name' => 'Din ve Hayat Koleksiyonu',
            'source_type' => 'category',
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        // 1. Initial resolution includes prog1
        $resolved1 = $collection->resolvePrograms();
        $this->assertCount(1, $resolved1);
        $this->assertEquals($prog1->id, $resolved1->first()->id);

        // 2. Add category to prog2 -> automatically included in collection!
        $prog2->categories()->attach($category->id);
        $resolved2 = $collection->resolvePrograms();
        $this->assertCount(2, $resolved2);

        // 3. Remove category from prog1 -> automatically removed from collection!
        $prog1->categories()->detach($category->id);
        $resolved3 = $collection->resolvePrograms();
        $this->assertCount(1, $resolved3);
        $this->assertEquals($prog2->id, $resolved3->first()->id);
    }

    public function test_active_period_schedule_collection_resolution()
    {
        $progMon = Program::factory()->create(['name' => 'Hatm-i Şerif', 'show_on_public' => true, 'is_active' => true]);
        $progTue = Program::factory()->create(['name' => 'Seyyah', 'show_on_public' => true, 'is_active' => true]);

        $template = ScheduleTemplate::create([
            'name' => 'Ağustos Akışı',
            'status' => 'published',
            'is_active' => true,
            'priority' => 10,
        ]);
        ScheduleTemplateItem::create(['schedule_template_id' => $template->id, 'day_of_week' => 0, 'start_time' => '08:00:00', 'end_time' => '09:00:00', 'program_id' => $progMon->id, 'is_active' => true]);
        ScheduleTemplateItem::create(['schedule_template_id' => $template->id, 'day_of_week' => 1, 'start_time' => '10:00:00', 'end_time' => '11:00:00', 'program_id' => $progTue->id, 'is_active' => true]);

        $collection = ProgramCollection::create([
            'name' => 'Güncel Programlar',
            'source_type' => 'active_period_schedule',
            'is_active' => true,
        ]);

        $resolved = $collection->resolvePrograms();
        $this->assertCount(2, $resolved);
        $this->assertEquals($progMon->id, $resolved->first()->id);
    }

    public function test_featured_collection_resolution()
    {
        $progFeatured = Program::factory()->create(['name' => 'Öne Çıkan', 'is_featured' => true, 'show_on_public' => true, 'is_active' => true]);
        $progRegular = Program::factory()->create(['name' => 'Normal Program', 'is_featured' => false, 'show_on_public' => true, 'is_active' => true]);

        $collection = ProgramCollection::create([
            'name' => 'Öne Çıkanlar',
            'source_type' => 'featured',
            'is_active' => true,
        ]);

        $resolved = $collection->resolvePrograms();
        $this->assertCount(1, $resolved);
        $this->assertEquals($progFeatured->id, $resolved->first()->id);
    }

    public function test_hybrid_collection_resolution_with_pinned_and_auto_fill()
    {
        $category = Category::create(['name' => 'Kültür', 'slug' => 'kultur', 'is_active' => true]);

        $progPinned = Program::factory()->create(['name' => 'Sabit Program', 'show_on_public' => true, 'is_active' => true]);
        $progAuto = Program::factory()->create(['name' => 'Otomatik Kategori Programı', 'show_on_public' => true, 'is_active' => true]);
        $progAuto->categories()->attach($category->id);

        $collection = ProgramCollection::create([
            'name' => 'Hibrit Seçki',
            'source_type' => 'hybrid',
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        $collection->programs()->attach($progPinned->id, ['sort_order' => 1, 'is_pinned' => true]);

        $resolved = $collection->resolvePrograms();

        $this->assertCount(2, $resolved);
        // Pinned program comes first
        $this->assertEquals($progPinned->id, $resolved->first()->id);
        $this->assertEquals($progAuto->id, $resolved->last()->id);
    }

    public function test_homepage_builder_renders_program_collection_with_independent_title()
    {
        $prog = Program::factory()->create(['name' => 'Ayetler ve Dualar', 'show_on_public' => true, 'is_active' => true]);

        $collection = ProgramCollection::create([
            'name' => 'Dini Programlar',
            'source_type' => 'manual',
            'is_active' => true,
        ]);
        $collection->programs()->attach($prog->id, ['sort_order' => 1]);

        HomepageLayout::create([
            'name' => 'Test Düzeni',
            'is_active' => true,
            'published_sections' => [
                [
                    'uuid' => 'prog_showcase_col',
                    'block_type' => 'program_showcase',
                    'visible' => true,
                    'title' => 'Bu Ay Yayındaki Seçkiler',
                    'program_collection_id' => $collection->id,
                    'display_variant' => 'horizontal_carousel',
                ],
            ],
        ]);

        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Bu Ay Yayındaki Seçkiler');
        $response->assertSee('Ayetler ve Dualar');
    }

    public function test_backward_compatibility_for_legacy_source_mode_blocks()
    {
        $prog = Program::factory()->create(['name' => 'Eski Sistem Programı', 'is_featured' => true, 'show_on_public' => true, 'is_active' => true]);

        // Legacy block without program_collection_id
        $legacyBlock = [
            'uuid' => 'legacy_block_1',
            'block_type' => 'program_showcase',
            'visible' => true,
            'source_mode' => 'featured_programs',
        ];

        $service = app(HomepageDataService::class);
        $resolved = $service->resolveProgramBlockData($legacyBlock);

        $this->assertCount(1, $resolved);
        $this->assertEquals($prog->id, $resolved->first()->id);
    }

    public function test_all_programs_source_type_resolves_all_active_programs_and_auto_updates_when_new_program_added()
    {
        $initialCount = Program::query()->where('show_on_public', true)->where('is_active', true)->count();

        $prog1 = Program::factory()->create(['name' => 'Test Prog 1', 'show_on_public' => true, 'is_active' => true]);
        $prog2 = Program::factory()->create(['name' => 'Test Prog 2', 'show_on_public' => true, 'is_active' => true]);

        $collection = ProgramCollection::create([
            'name' => 'Tüm Programlar',
            'slug' => 'tum-programlar',
            'source_type' => 'all_programs',
            'is_active' => true,
        ]);

        $resolved = $collection->resolvePrograms();
        $this->assertCount($initialCount + 2, $resolved);

        // Add a 3rd program -> automatically included!
        $prog3 = Program::factory()->create(['name' => 'Test Prog 3', 'show_on_public' => true, 'is_active' => true]);

        $resolvedAfterAdd = $collection->resolvePrograms();
        $this->assertCount($initialCount + 3, $resolvedAfterAdd);

        // Deactivate prog1 via show_on_public = false -> automatically excluded!
        $prog1->update(['show_on_public' => false]);
        $resolvedAfterDeactivate = $collection->resolvePrograms();
        $this->assertCount($initialCount + 2, $resolvedAfterDeactivate);
    }

    public function test_active_period_live_programs_source_type_resolves_only_is_live_true_schedule_programs()
    {
        $progLive = Program::factory()->create(['name' => 'Canlı Sohbet', 'show_on_public' => true, 'is_active' => true]);
        $progTape = Program::factory()->create(['name' => 'Bant Yayın Programı', 'show_on_public' => true, 'is_active' => true]);

        $template = ScheduleTemplate::create([
            'name' => 'Yaz Dönemi Akışı',
            'status' => 'published',
            'is_active' => true,
            'priority' => 10,
        ]);

        // Live schedule item
        ScheduleTemplateItem::create([
            'schedule_template_id' => $template->id,
            'day_of_week' => 0,
            'start_time' => '21:00:00',
            'end_time' => '22:30:00',
            'program_id' => $progLive->id,
            'is_live' => true,
            'is_active' => true,
        ]);

        // Non-live schedule item
        ScheduleTemplateItem::create([
            'schedule_template_id' => $template->id,
            'day_of_week' => 1,
            'start_time' => '14:00:00',
            'end_time' => '15:00:00',
            'program_id' => $progTape->id,
            'is_live' => false,
            'is_active' => true,
        ]);

        $collection = ProgramCollection::create([
            'name' => 'Öne Çıkan Canlılar',
            'slug' => 'one-cikan-canlilar',
            'source_type' => 'active_period_live_programs',
            'is_active' => true,
        ]);

        $resolved = $collection->resolvePrograms();

        $this->assertCount(1, $resolved);
        $this->assertEquals($progLive->id, $resolved->first()->id);
    }

    public function test_form_source_type_options_excludes_featured_and_uses_exact_turkish_labels()
    {
        $options = ProgramCollection::getFormSourceTypeOptions();

        $this->assertArrayNotHasKey('featured', $options);
        $this->assertEquals('Güncel Programlar — Aktif Yayın Dönemindeki Tüm Programlar', $options['active_period_schedule']);
        $this->assertEquals('Kategoriye Bağlı (Otomatik)', $options['category']);
        $this->assertEquals('Tüm Programlar — Tüm Aktif Programlar', $options['all_programs']);
        $this->assertEquals('Manuel Seçim', $options['manual']);
        $this->assertEquals('Öne Çıkanlar — Aktif Yayın Dönemindeki Canlı Programlar', $options['active_period_live_programs']);
        $this->assertEquals('Arşiv Programları — Arşiv Kategorisine Bağlı (Otomatik)', $options['archive_programs']);
        $this->assertEquals('Hibrit', $options['hybrid']);

        $keys = array_keys($options);
        $this->assertEquals([
            'active_period_schedule',
            'category',
            'all_programs',
            'manual',
            'active_period_live_programs',
            'archive_programs',
            'hybrid',
        ], $keys);
    }

    public function test_archive_programs_source_type_resolves_programs_attached_to_arsiv_category()
    {
        $archiveCategory = Category::firstOrCreate(
            ['slug' => 'arsiv'],
            ['name' => 'Arşiv', 'is_active' => true]
        );

        $archiveProg = Program::factory()->create(['name' => 'Eski Sohbetler 1995', 'show_on_public' => true, 'is_active' => true]);
        $archiveProg->categories()->attach($archiveCategory->id);

        $regularProg = Program::factory()->create(['name' => 'Yeni Güncel Program', 'show_on_public' => true, 'is_active' => true]);

        $collection = ProgramCollection::create([
            'name' => 'Arşiv Programları',
            'slug' => 'arsiv-programlari',
            'source_type' => 'archive_programs',
            'is_active' => true,
        ]);

        $resolved = $collection->resolvePrograms();

        $this->assertCount(1, $resolved);
        $this->assertEquals($archiveProg->id, $resolved->first()->id);

        // Attach category to regularProg -> automatically included!
        $regularProg->categories()->attach($archiveCategory->id);
        $this->assertCount(2, $collection->resolvePrograms());

        // Detach category from archiveProg -> automatically removed!
        $archiveProg->categories()->detach($archiveCategory->id);
        $this->assertCount(1, $collection->resolvePrograms());

        // Verify no pivot rows written to program_collection_program!
        $this->assertDatabaseMissing('program_collection_program', [
            'program_collection_id' => $collection->id,
        ]);
    }

    public function test_archive_programs_returns_empty_collection_gracefully_when_arsiv_category_missing()
    {
        Category::where('slug', 'arsiv')->orWhere('slug', 'archive')->orWhere('name', 'like', '%Arşiv%')->delete();

        $collection = ProgramCollection::create([
            'name' => 'Arşiv Programları',
            'slug' => 'arsiv-programlari-bos',
            'source_type' => 'archive_programs',
            'is_active' => true,
        ]);

        $resolved = $collection->resolvePrograms();

        $this->assertEmpty($resolved);
    }

    public function test_collection_sort_order_is_independent_from_program_resolution_order(): void
    {
        $prog1 = Program::factory()->create(['name' => 'Program X', 'show_on_public' => true, 'is_active' => true]);
        $prog2 = Program::factory()->create(['name' => 'Program Y', 'show_on_public' => true, 'is_active' => true]);

        $colA = ProgramCollection::create([
            'name' => 'Koleksiyon A',
            'slug' => 'koleksiyon-a',
            'source_type' => 'manual',
            'is_active' => true,
            'sort_order' => 20,
        ]);
        $colA->programs()->attach($prog1->id, ['sort_order' => 1]);
        $colA->programs()->attach($prog2->id, ['sort_order' => 2]);

        $colB = ProgramCollection::create([
            'name' => 'Koleksiyon B',
            'slug' => 'koleksiyon-b',
            'source_type' => 'manual',
            'is_active' => true,
            'sort_order' => 10,
        ]);
        $colB->programs()->attach($prog2->id, ['sort_order' => 1]);
        $colB->programs()->attach($prog1->id, ['sort_order' => 2]);

        // 1. Collections sorted by sort_order: colB (10) then colA (20)
        $collectionsOrder = ProgramCollection::query()->orderBy('sort_order')->pluck('slug')->all();
        $this->assertEquals(['koleksiyon-b', 'koleksiyon-a'], $collectionsOrder);

        // 2. Internal program order inside colA is unaffected by colA.sort_order
        $this->assertEquals(['Program X', 'Program Y'], $colA->resolvePrograms()->pluck('name')->all());
        $this->assertEquals(['Program Y', 'Program X'], $colB->resolvePrograms()->pluck('name')->all());

        // 3. Update colA sort_order to 5 -> collection order changes, internal program order stays identical
        $colA->update(['sort_order' => 5]);
        $reorderedCollections = ProgramCollection::query()->orderBy('sort_order')->pluck('slug')->all();
        $this->assertEquals(['koleksiyon-a', 'koleksiyon-b'], $reorderedCollections);

        $this->assertEquals(['Program X', 'Program Y'], $colA->fresh()->resolvePrograms()->pluck('name')->all());
    }

    public function test_auto_resolved_programs_drag_and_drop_reordering_and_automation_integrity(): void
    {
        $category = Category::create(['name' => 'Geçmiş Programlar', 'slug' => 'gecmis-programlar', 'is_active' => true]);

        $progA = Program::factory()->create(['name' => 'Program A', 'show_on_public' => true, 'is_active' => true]);
        $progB = Program::factory()->create(['name' => 'Program B', 'show_on_public' => true, 'is_active' => true]);
        $progC = Program::factory()->create(['name' => 'Program C', 'show_on_public' => true, 'is_active' => true]);
        $progD = Program::factory()->create(['name' => 'Program D', 'show_on_public' => true, 'is_active' => true]);

        $progA->categories()->attach($category->id);
        $progB->categories()->attach($category->id);
        $progC->categories()->attach($category->id);
        $progD->categories()->attach($category->id);

        $collection = ProgramCollection::create([
            'name' => 'Geçmiş Programlar Koleksiyonu',
            'slug' => 'gecmis-programlar-koleksiyonu',
            'source_type' => 'category',
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        // 1. Initial resolution (default order)
        $this->assertCount(4, $collection->resolvePrograms());

        // 2. Reorder via RelationManager: C (3), A (1), D (4), B (2)
        $relationManager = Livewire::test(
            AutoResolvedProgramsRelationManager::class,
            ['ownerRecord' => $collection, 'pageClass' => EditProgramCollection::class]
        );

        $relationManager->call('reorderTable', [
            $progC->id,
            $progA->id,
            $progD->id,
            $progB->id,
        ]);

        // 3. Verify public settings stored correctly & page refresh preserves C, A, D, B
        $collection->refresh();
        $this->assertEquals([$progC->id, $progA->id, $progD->id, $progB->id], $collection->public_settings['custom_program_order']);

        $resolvedNames = $collection->resolvePrograms()->pluck('name')->all();
        $this->assertEquals(['Program C', 'Program A', 'Program D', 'Program B'], $resolvedNames);

        // 4. Add Program E to category -> should appear at the END: C, A, D, B, E
        $progE = Program::factory()->create(['name' => 'Program E', 'show_on_public' => true, 'is_active' => true]);
        $progE->categories()->attach($category->id);

        $resolvedAfterAdd = $collection->fresh()->resolvePrograms()->pluck('name')->all();
        $this->assertEquals(['Program C', 'Program A', 'Program D', 'Program B', 'Program E'], $resolvedAfterAdd);

        // 5. Remove Program B from category -> should be C, A, D, E
        $progB->categories()->detach($category->id);

        $resolvedAfterRemove = $collection->fresh()->resolvePrograms()->pluck('name')->all();
        $this->assertEquals(['Program C', 'Program A', 'Program D', 'Program E'], $resolvedAfterRemove);

        // 6. Verify program flags (categories, show_on_public, is_active) were NOT mutated
        $this->assertTrue($progA->fresh()->is_active);
        $this->assertTrue($progA->fresh()->show_on_public);
    }
}
