<?php

namespace Tests\Feature;

use App\Filament\Resources\SiteLayout\HomepageLayoutResource\Pages\EditHomepageLayout;
use App\Models\Category;
use App\Models\HomepageLayout;
use App\Models\Program;
use App\Models\ProgramCollection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VisualBuilderUnifiedCollectionSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected HomepageLayout $layout;

    protected ProgramCollection $programCollection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'role' => 'administrator',
            'is_active' => true,
        ]);

        $category = Category::create(['name' => 'Din ve Hayat', 'slug' => 'din-ve-hayat']);
        $program = Program::create(['name' => 'Test Program', 'slug' => 'test-program', 'category_id' => $category->id, 'status' => 'active']);

        $this->programCollection = ProgramCollection::create([
            'name' => 'Tüm Programlarımız',
            'slug' => 'tum-programlarimiz',
            'is_active' => true,
            'source_type' => 'manual',
            'public_settings' => [
                'desktop_columns' => 4,
                'tablet_columns' => 3,
                'mobile_columns' => 2,
                'gap_size' => 'medium',
                'display_variant' => 'grid',
            ],
        ]);
        $this->programCollection->programs()->attach($program->id);

        HomepageLayout::query()->update(['is_active' => false]);

        $this->layout = HomepageLayout::create([
            'name' => 'Test Ana Sayfa Düzeni',
            'is_active' => true,
            'published_sections' => [
                [
                    'uuid' => 'block-prog-1',
                    'block_type' => 'program_showcase',
                    'title' => 'Tüm Programlarımız',
                    'program_collection_id' => $this->programCollection->id,
                    'desktop_columns' => 5,
                    'content_limit' => 8,
                    'display_variant' => 'horizontal_carousel',
                ],
            ],
            'draft_sections' => [
                [
                    'uuid' => 'block-prog-1',
                    'block_type' => 'program_showcase',
                    'title' => 'Tüm Programlarımız',
                    'program_collection_id' => $this->programCollection->id,
                    'desktop_columns' => 5,
                    'content_limit' => 8,
                    'display_variant' => 'horizontal_carousel',
                ],
            ],
        ]);
    }

    public function test_unified_see_all_page_settings_in_visual_builder_updates_collection_public_settings_only(): void
    {
        // 1. Initial State assertions
        $this->assertEquals(5, $this->layout->fresh()->draft_sections[0]['desktop_columns']);
        $this->assertEquals('horizontal_carousel', $this->layout->fresh()->draft_sections[0]['display_variant']);

        // 2. Edit Visual Builder: switch to "Tümünü Gör Sayfası" tab and update collection settings
        Livewire::actingAs($this->adminUser)
            ->test(EditHomepageLayout::class, ['record' => $this->layout->id])
            ->call('selectBlock', 'block-prog-1')
            ->call('setEditTargetMode', 'collection')
            ->call('updateCollectionPublicSetting', 'desktop_columns', 4)
            ->call('updateCollectionPublicSetting', 'tablet_columns', 3)
            ->call('updateCollectionPublicSetting', 'mobile_columns', 2);

        // 3. Verify single source of truth (ProgramCollection record) was updated
        $colSettings = $this->programCollection->fresh()->public_settings;
        $this->assertEquals(4, $colSettings['desktop_columns']);
        $this->assertEquals(3, $colSettings['tablet_columns']);
        $this->assertEquals(2, $colSettings['mobile_columns']);

        // 4. Verify Homepage layout draft_sections remains untouched
        $this->assertEquals(5, $this->layout->fresh()->draft_sections[0]['desktop_columns']);
        $this->assertEquals('horizontal_carousel', $this->layout->fresh()->draft_sections[0]['display_variant']);

        // 5. GET Public See All Detail Page: renders 4 desktop columns, 3 tablet columns, 2 mobile columns
        $detailResponse = $this->actingAs($this->adminUser)->get('/program-koleksiyonlari/tum-programlarimiz');
        $detailResponse->assertSuccessful()
            ->assertSee('lg:grid-cols-4', false)
            ->assertSee('sm:grid-cols-3', false)
            ->assertSee('grid-cols-2', false);

        // 6. Verify Homepage layout draft_sections remains untouched with 5 columns and horizontal carousel
        $this->assertEquals(5, $this->layout->fresh()->draft_sections[0]['desktop_columns']);
        $this->assertEquals('horizontal_carousel', $this->layout->fresh()->draft_sections[0]['display_variant']);
    }
}
