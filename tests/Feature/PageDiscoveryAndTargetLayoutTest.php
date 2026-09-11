<?php

namespace Tests\Feature;

use App\Models\HomepageLayout;
use App\Models\ProgramCollection;
use App\Models\VideoCollection;
use App\Services\Page\PageDiscoveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageDiscoveryAndTargetLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_discovery_service_returns_system_and_dynamic_collection_pages()
    {
        $pCol = ProgramCollection::create([
            'name' => 'Öne Çıkan Dini Programlar',
            'slug' => 'one-cikan-dini-programlar',
            'is_active' => true,
        ]);

        $vCol = VideoCollection::create([
            'name' => 'Seçme Sohbetler',
            'slug' => 'secme-sohbetler',
            'is_active' => true,
        ]);

        $discovered = PageDiscoveryService::getDiscoverablePages();

        $this->assertTrue($discovered->contains('page_type', 'home'));
        $this->assertTrue($discovered->contains('page_type', 'program_detail'));
        $this->assertTrue($discovered->contains('page_type', 'program_index'));
        $this->assertTrue($discovered->contains('page_type', 'schedule'));
        $this->assertTrue($discovered->contains('page_type', 'live_tv'));

        $pColPage = $discovered->firstWhere('key', "program_collection_{$pCol->id}");
        $this->assertNotNull($pColPage);
        $this->assertEquals('/program-koleksiyonlari/one-cikan-dini-programlar', $pColPage['url']);
        $this->assertEquals('Henüz Düzenlenmedi', $pColPage['status_label']);

        $vColPage = $discovered->firstWhere('key', "video_collection_{$vCol->id}");
        $this->assertNotNull($vColPage);
        $this->assertEquals('/koleksiyonlar/secme-sohbetler', $vColPage['url']);
    }

    public function test_find_or_create_layout_for_target_creates_record_on_demand_without_duplicates()
    {
        $pCol = ProgramCollection::create([
            'name' => 'Ramazan Özel',
            'slug' => 'ramazan-ozel',
            'is_active' => true,
        ]);

        // 1. Create on demand
        $layout1 = PageDiscoveryService::findOrCreateLayoutForTarget('program_collection', $pCol->id);

        $this->assertEquals('program_collection', $layout1->page_type);
        $this->assertEquals($pCol->id, $layout1->target_id);
        $this->assertEquals('Ramazan Özel', $layout1->name);
        $this->assertFalse($layout1->is_active);

        // 2. Calling second time returns exact same instance without creating duplicate
        $layout2 = PageDiscoveryService::findOrCreateLayoutForTarget('program_collection', $pCol->id);

        $this->assertEquals($layout1->id, $layout2->id);
        $this->assertEquals(1, HomepageLayout::query()->where('page_type', 'program_collection')->where('target_id', $pCol->id)->count());
    }

    public function test_collection_detail_page_renders_builder_sections_when_active_or_preview()
    {
        $pCol = ProgramCollection::create([
            'name' => 'Cuma Sohbetleri',
            'slug' => 'cuma-sohbetleri',
            'is_active' => true,
        ]);

        $layout = HomepageLayout::create([
            'name' => 'Cuma Sohbetleri Düzeni',
            'page_type' => 'program_collection',
            'target_id' => $pCol->id,
            'is_active' => true,
            'published_sections' => [
                ['uuid' => 'hdr-1', 'block_type' => 'collection_header', 'visible' => true],
                ['uuid' => 'grid-1', 'block_type' => 'program_collection_grid', 'visible' => true],
            ],
            'published_at' => now(),
        ]);

        $response = $this->get(route('program.collections.show', $pCol->slug));

        $response->assertOk();
        $response->assertSee('Cuma Sohbetleri');
    }

    public function test_collection_detail_falls_back_to_legacy_view_when_no_layout_or_empty_sections()
    {
        $pCol = ProgramCollection::create([
            'name' => 'Belgeseller',
            'slug' => 'belgeseller',
            'is_active' => true,
        ]);

        // Legacy fallback view test
        $response = $this->get(route('program.collections.show', $pCol->slug));

        $response->assertOk();
        $response->assertSee('Belgeseller');
    }
}
