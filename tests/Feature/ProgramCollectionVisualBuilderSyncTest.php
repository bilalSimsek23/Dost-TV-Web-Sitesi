<?php

namespace Tests\Feature;

use App\Filament\Resources\SiteLayout\HomepageLayoutResource\Pages\EditHomepageLayout;
use App\Models\HomepageLayout;
use App\Models\ProgramCollection;
use App\Models\User;
use App\Services\Page\PageDiscoveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProgramCollectionVisualBuilderSyncTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected ProgramCollection $programCollection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'role' => 'administrator',
            'is_active' => true,
        ]);

        $this->programCollection = ProgramCollection::create([
            'name' => 'Geçmiş Programlarımız',
            'slug' => 'gecmis-programlarimiz',
            'is_active' => true,
        ]);
    }

    public function test_opening_empty_program_collection_layout_bootstraps_default_sections()
    {
        $layout = PageDiscoveryService::findOrCreateLayoutForTarget('program_collection', $this->programCollection->id);

        $testComponent = Livewire::actingAs($this->adminUser)
            ->test(EditHomepageLayout::class, ['record' => $layout->id]);

        $draftSections = $testComponent->get('draftSections');

        $this->assertCount(2, $draftSections);
        $this->assertEquals('collection_header', $draftSections[0]['block_type']);
        $this->assertEquals('program_collection_grid', $draftSections[1]['block_type']);
    }

    public function test_adding_new_block_appends_without_overwriting_default_sections()
    {
        $layout = PageDiscoveryService::findOrCreateLayoutForTarget('program_collection', $this->programCollection->id);

        $testComponent = Livewire::actingAs($this->adminUser)
            ->test(EditHomepageLayout::class, ['record' => $layout->id]);

        $testComponent->set('newBlockType', 'content_shelf')
            ->call('addBlock');

        $draftSections = $testComponent->get('draftSections');

        $this->assertCount(3, $draftSections);
        $this->assertEquals('collection_header', $draftSections[0]['block_type']);
        $this->assertEquals('program_collection_grid', $draftSections[1]['block_type']);
        $this->assertEquals('content_shelf', $draftSections[2]['block_type']);
    }

    public function test_preview_mode_renders_builder_view_and_displays_empty_state_card_when_sections_cleared()
    {
        $layout = HomepageLayout::create([
            'name' => 'Geçmiş Programlarımız Düzeni',
            'page_type' => 'program_collection',
            'target_id' => $this->programCollection->id,
            'is_active' => false,
            'draft_sections' => [],
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('program.collections.show', [
                'slug' => $this->programCollection->slug,
                'preview_layout_id' => $layout->id,
            ]));

        $response->assertOk();
        $response->assertSee('Bu Düzende Henüz Bölüm Bulunmuyor');
    }
}
