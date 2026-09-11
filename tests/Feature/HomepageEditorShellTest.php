<?php

namespace Tests\Feature;

use App\Filament\Resources\SiteLayout\HomepageLayoutResource\Pages\EditHomepageLayout;
use App\Models\HomepageLayout;
use App\Models\Program;
use App\Models\User;
use App\Services\Home\HomepageBlockRegistry;
use App\Support\SiteCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HomepageEditorShellTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $regularUser;

    protected HomepageLayout $activeLayout;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $this->regularUser = User::factory()->create(['role' => 'editor', 'is_active' => true]);

        Program::factory()->create(['name' => 'Test Programı', 'is_active' => true, 'is_featured' => true]);

        $this->activeLayout = HomepageLayout::create([
            'name' => 'Canlı Düzen Test',
            'is_active' => true,
            'draft_sections' => [
                ['uuid' => 'test-1', 'block_type' => 'today_schedule', 'visible' => true, 'title' => 'Taslak Bölümü', 'show_now_badge' => true, 'show_next_badge' => true],
                ['uuid' => 'test-2', 'block_type' => 'program_showcase', 'visible' => true, 'title' => 'Vitrin', 'display_variant' => 'horizontal_carousel', 'row_count' => 2, 'desktop_columns' => 1, 'tablet_columns' => 2, 'mobile_columns' => 1, 'padding_y' => 'md', 'gap_size' => 'md', 'autoplay' => true, 'loop' => true, 'show_arrows' => true, 'show_dots' => false],
            ],
            'published_sections' => [
                ['uuid' => 'test-1', 'block_type' => 'today_schedule', 'visible' => true, 'title' => 'Yayınlanmış Bölüm'],
            ],
            'published_at' => now(),
        ]);
    }

    public function test_unauthenticated_user_cannot_access_preview_frame(): void
    {
        $response = $this->get(route('admin.site-layout.preview-frame', $this->activeLayout));
        $response->assertRedirect('/admin/login');
    }

    public function test_authenticated_admin_can_access_preview_frame(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.site-layout.preview-frame', $this->activeLayout));

        $response->assertStatus(200);
        $response->assertSee('Ana Sayfa Canlı Önizleme');
    }

    public function test_draft_changes_do_not_leak_to_public_home(): void
    {
        $this->activeLayout->draft_sections = [
            ['uuid' => 'test-2', 'block_type' => 'today_schedule', 'visible' => false, 'title' => 'Yalnızca Taslakta Var'],
        ];
        $this->activeLayout->save();

        SiteCache::forgetHomepage();

        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertDontSee('Yalnızca Taslakta Var');
    }

    public function test_publishing_draft_updates_public_home(): void
    {
        $this->activeLayout->draft_sections = [];
        $this->activeLayout->save();

        $this->activeLayout->publish(true);

        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Dost TV');
    }

    public function test_fixed_areas_not_in_builder_block_registry(): void
    {
        $types = HomepageBlockRegistry::getBlockTypes();
        $this->assertArrayNotHasKey('hero', $types);
        $this->assertArrayNotHasKey('header', $types);
        $this->assertArrayNotHasKey('footer', $types);
    }

    public function test_editor_shell_renders_full_viewport_100dvh_layout(): void
    {
        Livewire::actingAs($this->superAdmin)
            ->test(EditHomepageLayout::class, ['record' => $this->activeLayout->id])
            ->assertSee('id="dost-homepage-editor"', false)
            ->assertSee('class="dost-editor-toolbar"', false)
            ->assertSee('class="dost-editor-sidebar"', false)
            ->assertSee('class="dost-editor-preview-frame"', false);
    }

    public function test_column_count_supports_one_to_eight(): void
    {
        $columns = HomepageBlockRegistry::getDesktopColumns();
        $this->assertArrayHasKey(1, $columns);
        $this->assertEquals(1, HomepageBlockRegistry::resolveDesktopColumns(['desktop_columns' => 1]));
        $this->assertEquals(8, HomepageBlockRegistry::resolveDesktopColumns(['desktop_columns' => 'custom', 'desktop_columns_custom' => 8]));
    }

    public function test_elementor_widget_editor_helpers(): void
    {
        $this->assertArrayHasKey('boxed', HomepageBlockRegistry::getSectionWidthOptions());
        $this->assertArrayHasKey('16:9', HomepageBlockRegistry::getCardRatioOptions());
        $this->assertArrayHasKey('md', HomepageBlockRegistry::getCardRadiusOptions());
        $this->assertArrayHasKey('lg', HomepageBlockRegistry::getTitleSizeOptions());
    }

    public function test_add_block_handles_null_or_empty_safely(): void
    {
        Livewire::actingAs($this->superAdmin)
            ->test(EditHomepageLayout::class, ['record' => $this->activeLayout->id])
            ->call('addBlock', null)
            ->assertHasNoErrors();
    }

    public function test_select_fixed_area_and_editor_context_methods(): void
    {
        Livewire::actingAs($this->superAdmin)
            ->test(EditHomepageLayout::class, ['record' => $this->activeLayout->id])
            ->call('selectFixedArea', 'hero')
            ->assertSet('contextMode', 'fixed')
            ->assertSet('fixedAreaName', 'hero')
            ->call('selectListContext')
            ->assertSet('contextMode', 'list')
            ->call('deleteBlock', 'test-1')
            ->assertHasNoErrors();
    }

    public function test_reorder_blocks_swaps_draft_sections_order(): void
    {
        $comp = Livewire::actingAs($this->superAdmin)
            ->test(EditHomepageLayout::class, ['record' => $this->activeLayout->id]);

        $drafts = array_values($comp->get('draftSections'));
        $initialFirstUuid = $drafts[0]['uuid'];
        $initialSecondUuid = $drafts[1]['uuid'];

        $comp->call('reorderBlocks', 0, 1);

        $updated = array_values($comp->get('draftSections'));
        $this->assertEquals($initialSecondUuid, $updated[0]['uuid']);
        $this->assertEquals($initialFirstUuid, $updated[1]['uuid']);
    }

    public function test_each_block_type_renders_its_specific_settings_panel_and_not_only_fallback(): void
    {
        $layout = HomepageLayout::create([
            'name' => 'Tüm Blok Panelleri Testi',
            'is_active' => false,
            'draft_sections' => [
                ['uuid' => 'ts-1', 'block_type' => 'today_schedule', 'visible' => true, 'title' => 'Akış'],
                ['uuid' => 'ps-1', 'block_type' => 'program_showcase', 'visible' => true, 'title' => 'Vitrin', 'source_mode' => 'featured_programs'],
                ['uuid' => 'cs-1', 'block_type' => 'content_shelf', 'visible' => true, 'title' => 'Raf', 'shelf_type' => 'program', 'source_mode' => 'manual'],
                ['uuid' => 'cat-1', 'block_type' => 'category_shelf', 'visible' => true, 'title' => 'Kategori Rafı'],
                ['uuid' => 'vc-1', 'block_type' => 'video_collection', 'visible' => true, 'title' => 'Koleksiyon'],
            ],
            'published_sections' => [],
        ]);

        $comp = Livewire::actingAs($this->superAdmin)
            ->test(EditHomepageLayout::class, ['record' => $layout->id]);

        // 1. today_schedule
        $comp->call('selectBlock', 'ts-1')
            ->assertSee('İÇERİK')
            ->assertSee('Tüm Akış', false);

        // 2. program_showcase
        $comp->call('selectBlock', 'ps-1')
            ->assertSee('İÇERİK')
            ->assertSee('Program Koleksiyonu');

        // 3. content_shelf
        $comp->call('selectBlock', 'cs-1')
            ->assertSee('Raf Türü')
            ->assertSee('Program Rafı');

        // 4. category_shelf
        $comp->call('selectBlock', 'cat-1')
            ->assertSee('Kategori Seçin');

        // 5. video_collection
        $comp->call('selectBlock', 'vc-1')
            ->assertSee('Video Koleksiyonu');

        // 6. Return to list context
        $comp->call('selectListContext')
            ->assertSet('contextMode', 'list')
            ->assertSee('Ana Sayfa Blokları');
    }

    public function test_fixed_sections_are_structurally_locked_and_cannot_be_deleted(): void
    {
        $initialCount = count($this->activeLayout->draft_sections);

        Livewire::actingAs($this->superAdmin)
            ->test(EditHomepageLayout::class, ['record' => $this->activeLayout->id])
            ->call('deleteBlock', 'footer');

        $this->activeLayout->refresh();
        $this->assertCount($initialCount, $this->activeLayout->draft_sections);
    }

    public function test_fixed_sections_presentation_settings_can_be_edited_and_saved(): void
    {
        $comp = Livewire::actingAs($this->superAdmin)
            ->test(EditHomepageLayout::class, ['record' => $this->activeLayout->id])
            ->call('selectFixedArea', 'footer')
            ->assertSet('contextMode', 'fixed')
            ->assertSet('fixedAreaName', 'footer')
            ->assertSee('İÇERİK')
            ->assertSee('YERLEŞİM')
            ->assertSee('ÖLÇÜLER')
            ->assertSee('GELİŞMİŞ')
            ->call('updateFixedSetting', 'footer', 'form_field_gap', 28)
            ->call('updateFixedSetting', 'footer', 'map_form_gap', 72);

        $this->activeLayout->refresh();
        $this->assertEquals(28, $this->activeLayout->draft_sections['_fixed_settings']['footer']['form_field_gap']);
        $this->assertEquals(72, $this->activeLayout->draft_sections['_fixed_settings']['footer']['map_form_gap']);
    }

    public function test_contact_page_presentation_controls_and_live_preview(): void
    {
        Livewire::actingAs($this->superAdmin)
            ->test(EditHomepageLayout::class, ['record' => $this->activeLayout->id])
            ->call('selectFixedArea', 'footer')
            ->call('updateFixedSetting', 'footer', 'map_form_gap', 100)
            ->call('updateFixedSetting', 'footer', 'contact_cards_gap', 40)
            ->call('updateFixedSetting', 'footer', 'map_height', 450)
            ->call('updateFixedSetting', 'footer', 'card_border', 'none')
            ->call('updateFixedSetting', 'footer', 'card_surface', 'transparent');

        \Illuminate\Support\Facades\View::share('errors', new \Illuminate\Support\ViewErrorBag());

        $contactPage = \App\Models\Page::where('slug', 'iletisim')->first();

        // Render page-card Blade component with updated fixedSettings
        $view = $this->blade('<x-site.page-card :page="$page" :fixed-settings="$fixedSettings" />', [
            'page' => $contactPage,
            'fixedSettings' => $this->activeLayout->fresh()->draft_sections['_fixed_settings'],
        ]);

        $view->assertSee('margin-top: 100px;', false)
            ->assertSee('gap: 40px;', false)
            ->assertSee('height: 450px;', false)
            ->assertSee('border-color: transparent', false)
            ->assertSee('background-color: transparent', false);
    }

    public function test_draft_changes_do_not_leak_to_public_site_until_published(): void
    {
        // 1. Initial public page shows default published settings (map_form_gap: 56px)
        $publicBefore = $this->get('/iletisim');
        $publicBefore->assertSuccessful()
            ->assertSee('margin-top: 56px;', false);

        // 2. Admin edits draft layout setting
        $comp = Livewire::actingAs($this->superAdmin)
            ->test(EditHomepageLayout::class, ['record' => $this->activeLayout->id])
            ->call('selectFixedArea', 'footer')
            ->call('updateFixedSetting', 'footer', 'map_form_gap', 120);

        // 3. Public site MUST NOT change on draft save
        $publicDuringDraft = $this->get('/iletisim');
        $publicDuringDraft->assertSuccessful()
            ->assertDontSee('margin-top: 120px;', false)
            ->assertSee('margin-top: 56px;', false);

        // 4. Admin publishes the draft
        $comp->call('publish');

        // 5. Public site now reflects the newly published settings
        $publicAfterPublish = $this->get('/iletisim');
        $publicAfterPublish->assertSuccessful()
            ->assertSee('margin-top: 120px;', false);
    }
}
