<?php

namespace Tests\Feature;

use App\Filament\Resources\SiteLayout\HomepageLayoutResource\Pages\EditHomepageLayout;
use App\Models\HomepageLayout;
use App\Models\ProgramCollection;
use App\Models\User;
use App\Models\VideoCollection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VisualBuilderCollectionTabIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected HomepageLayout $layout;

    protected ProgramCollection $programCollection;

    protected VideoCollection $videoCollection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'role' => 'administrator',
            'is_active' => true,
        ]);

        $this->programCollection = ProgramCollection::create([
            'name' => 'Tüm Programlarımız',
            'slug' => 'tum-programlarimiz',
            'is_active' => true,
            'source_type' => 'manual',
            'public_settings' => [
                'desktop_columns' => 3,
                'tablet_columns' => 3,
                'mobile_columns' => 2,
                'gap_size' => 'medium',
                'display_variant' => 'grid',
            ],
        ]);

        $this->videoCollection = VideoCollection::create([
            'name' => 'Akıştan Videolar',
            'slug' => 'akistan-videolar',
            'is_active' => true,
            'source_type' => 'manual',
            'public_settings' => [
                'desktop_columns' => 4,
                'tablet_columns' => 3,
                'mobile_columns' => 1,
                'gap_size' => 'medium',
                'display_variant' => 'grid',
            ],
        ]);

        $this->layout = HomepageLayout::create([
            'name' => 'Test Ana Sayfa Düzeni',
            'is_active' => true,
            'published_sections' => [],
            'draft_sections' => [
                [
                    'uuid' => 'block-prog-1',
                    'block_type' => 'program_showcase',
                    'title' => 'Güncel Programlar',
                    'program_collection_id' => $this->programCollection->id,
                    'desktop_columns' => 5,
                    'content_limit' => 7,
                    'display_variant' => 'horizontal_carousel',
                ],
                [
                    'uuid' => 'block-vid-1',
                    'block_type' => 'video_collection',
                    'title' => 'Özel Videolar',
                    'collection_id' => $this->videoCollection->id,
                    'desktop_columns' => 4,
                    'content_limit' => 6,
                    'display_variant' => 'grid',
                ],
            ],
        ]);
    }

    public function test_program_collection_tab_isolation_and_separate_save_flows(): void
    {
        // Initial DB verification
        $this->assertEquals(5, $this->layout->fresh()->draft_sections[0]['desktop_columns']);
        $this->assertEquals(3, $this->programCollection->fresh()->public_settings['desktop_columns']);

        $testComponent = Livewire::actingAs($this->adminUser)
            ->test(EditHomepageLayout::class, ['record' => $this->layout->id])
            ->call('selectBlock', 'block-prog-1')
            ->assertSet('editTargetMode', 'homepage')
            ->assertSet('previewFrameUrl', route('admin.site-layout.preview-frame', $this->layout->id));

        // Switch to "Tümünü Gör Sayfası" tab
        $testComponent->call('setEditTargetMode', 'collection')
            ->assertSet('editTargetMode', 'collection')
            ->assertSet('previewFrameUrl', route('program.collections.show', $this->programCollection->slug));

        // Update See All Page desktop columns to 6
        $testComponent->call('updateCollectionPublicSetting', 'desktop_columns', 6);

        // 1. ProgramCollection record DB MUST BE 6
        $this->assertEquals(6, $this->programCollection->fresh()->public_settings['desktop_columns']);

        // 2. HomepageLayout draft_sections MUST REMAIN 5 (isolated!)
        $this->assertEquals(5, $this->layout->fresh()->draft_sections[0]['desktop_columns']);

        // Switch back to "Ana Sayfa Vitrini" tab
        $testComponent->call('setEditTargetMode', 'homepage')
            ->assertSet('editTargetMode', 'homepage')
            ->assertSet('previewFrameUrl', route('admin.site-layout.preview-frame', $this->layout->id));

        // Update Homepage Showcase desktop_columns to 4 and save draft
        $testComponent->set('draftSections.0.desktop_columns', 4)
            ->call('saveDraft', false);

        // 3. HomepageLayout draft_sections MUST BE 4
        $this->assertEquals(4, $this->layout->fresh()->draft_sections[0]['desktop_columns']);

        // 4. ProgramCollection record DB MUST REMAIN 6 (isolated!)
        $this->assertEquals(6, $this->programCollection->fresh()->public_settings['desktop_columns']);
    }

    public function test_video_collection_tab_isolation_and_separate_save_flows(): void
    {
        // Initial DB verification
        $this->assertEquals(4, $this->layout->fresh()->draft_sections[1]['desktop_columns']);
        $this->assertEquals(4, $this->videoCollection->fresh()->public_settings['desktop_columns']);

        $testComponent = Livewire::actingAs($this->adminUser)
            ->test(EditHomepageLayout::class, ['record' => $this->layout->id])
            ->call('selectBlock', 'block-vid-1')
            ->assertSet('editTargetMode', 'homepage');

        // Switch to "Tümünü Gör Sayfası" tab
        $testComponent->call('setEditTargetMode', 'collection')
            ->assertSet('editTargetMode', 'collection')
            ->assertSet('previewFrameUrl', route('collections.show', $this->videoCollection->slug));

        // Update See All Page desktop columns to 8
        $testComponent->call('updateCollectionPublicSetting', 'desktop_columns', 8);

        // VideoCollection record DB MUST BE 8
        $this->assertEquals(8, $this->videoCollection->fresh()->public_settings['desktop_columns']);

        // HomepageLayout draft_sections MUST REMAIN 4 (isolated!)
        $this->assertEquals(4, $this->layout->fresh()->draft_sections[1]['desktop_columns']);
    }
}
