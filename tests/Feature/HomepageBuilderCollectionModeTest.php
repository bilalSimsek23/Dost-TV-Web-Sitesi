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

class HomepageBuilderCollectionModeTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected HomepageLayout $layout;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'role' => 'administrator',
            'is_active' => true,
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
                    'program_collection_id' => null,
                    'content_limit' => 7,
                    'display_variant' => 'horizontal_carousel',
                ],
                [
                    'uuid' => 'block-vid-1',
                    'block_type' => 'video_collection',
                    'title' => 'Özel Videolar',
                    'collection_id' => null,
                    'content_limit' => 6,
                    'display_variant' => 'grid',
                ],
            ],
        ]);
    }

    public function test_default_editor_mode_is_homepage_and_preview_url_points_to_homepage_frame(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(EditHomepageLayout::class, ['record' => $this->layout->id])
            ->assertSet('editTargetMode', 'homepage')
            ->assertSet('previewFrameUrl', route('admin.site-layout.preview-frame', $this->layout->id));
    }

    public function test_cannot_switch_to_collection_mode_without_selecting_collection(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(EditHomepageLayout::class, ['record' => $this->layout->id])
            ->call('selectBlock', 'block-prog-1')
            ->call('setEditTargetMode', 'collection')
            ->assertSet('editTargetMode', 'homepage')
            ->assertSet('previewFrameUrl', route('admin.site-layout.preview-frame', $this->layout->id));
    }

    public function test_switching_to_collection_mode_updates_preview_frame_url_to_collection_public_route(): void
    {
        $programCollection = ProgramCollection::create([
            'name' => 'Gündem Programları',
            'slug' => 'gundem-programlari',
            'source_type' => 'manual',
            'is_active' => true,
            'public_settings' => [
                'display_variant' => 'grid',
                'desktop_columns' => 4,
            ],
        ]);

        $draftSections = $this->layout->draft_sections;
        $draftSections[0]['program_collection_id'] = $programCollection->id;
        $this->layout->update(['draft_sections' => $draftSections]);

        Livewire::actingAs($this->adminUser)
            ->test(EditHomepageLayout::class, ['record' => $this->layout->id])
            ->call('selectBlock', 'block-prog-1')
            ->call('setEditTargetMode', 'collection')
            ->assertSet('editTargetMode', 'collection')
            ->assertSet('previewFrameUrl', route('program.collections.show', 'gundem-programlari'));
    }

    public function test_updating_collection_public_settings_in_builder_saves_to_collection_and_does_not_mutate_draft_sections(): void
    {
        $programCollection = ProgramCollection::create([
            'name' => 'Özel Koleksiyon',
            'slug' => 'ozel-koleksiyon',
            'source_type' => 'manual',
            'is_active' => true,
            'public_settings' => [
                'display_variant' => 'grid',
                'desktop_columns' => 4,
                'gap_size' => 'medium',
            ],
        ]);

        $draftSections = $this->layout->draft_sections;
        $draftSections[0]['program_collection_id'] = $programCollection->id;
        $draftSections[0]['content_limit'] = 7;
        $this->layout->update(['draft_sections' => $draftSections]);

        Livewire::actingAs($this->adminUser)
            ->test(EditHomepageLayout::class, ['record' => $this->layout->id])
            ->call('selectBlock', 'block-prog-1')
            ->call('setEditTargetMode', 'collection')
            ->call('updateCollectionPublicSetting', 'desktop_columns', 5)
            ->call('updateCollectionPublicSetting', 'gap_size', 'large');

        // Verify ProgramCollection record public_settings was updated
        $programCollection->refresh();
        $this->assertEquals(5, $programCollection->public_settings['desktop_columns']);
        $this->assertEquals('large', $programCollection->public_settings['gap_size']);

        // Verify HomepageLayout draft_sections was NOT mutated
        $this->layout->refresh();
        $this->assertEquals(7, $this->layout->draft_sections[0]['content_limit']);
    }

    public function test_video_collection_mode_updates_preview_frame_url_to_video_collection_route(): void
    {
        $videoCollection = VideoCollection::create([
            'name' => 'Söyleşi Videoları',
            'slug' => 'soylesi-videolari',
            'source_type' => 'manual',
            'is_active' => true,
            'public_settings' => [
                'display_variant' => 'grid',
                'desktop_columns' => 3,
            ],
        ]);

        $draftSections = $this->layout->draft_sections;
        $draftSections[1]['collection_id'] = $videoCollection->id;
        $this->layout->update(['draft_sections' => $draftSections]);

        Livewire::actingAs($this->adminUser)
            ->test(EditHomepageLayout::class, ['record' => $this->layout->id])
            ->call('selectBlock', 'block-vid-1')
            ->call('setEditTargetMode', 'collection')
            ->assertSet('editTargetMode', 'collection')
            ->assertSet('previewFrameUrl', route('collections.show', 'soylesi-videolari'));
    }

    public function test_switching_back_to_homepage_mode_restores_homepage_preview_url(): void
    {
        $programCollection = ProgramCollection::create([
            'name' => 'Kültür Sanat',
            'slug' => 'kultur-sanat',
            'source_type' => 'manual',
            'is_active' => true,
        ]);

        $draftSections = $this->layout->draft_sections;
        $draftSections[0]['program_collection_id'] = $programCollection->id;
        $this->layout->update(['draft_sections' => $draftSections]);

        Livewire::actingAs($this->adminUser)
            ->test(EditHomepageLayout::class, ['record' => $this->layout->id])
            ->call('selectBlock', 'block-prog-1')
            ->call('setEditTargetMode', 'collection')
            ->assertSet('editTargetMode', 'collection')
            ->call('setEditTargetMode', 'homepage')
            ->assertSet('editTargetMode', 'homepage')
            ->assertSet('previewFrameUrl', route('admin.site-layout.preview-frame', $this->layout->id));
    }
}
