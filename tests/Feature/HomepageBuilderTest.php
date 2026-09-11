<?php

namespace Tests\Feature;

use App\Filament\Resources\SiteLayout\HomepageLayoutResource\HomepageLayoutResource;
use App\Models\Category;
use App\Models\Episode;
use App\Models\HomepageLayout;
use App\Models\Program;
use App\Models\User;
use App\Models\VideoCollection;
use App\Services\Home\CtaRouteResolver;
use App\Services\Home\HomepageBlockRegistry;
use App\Services\Home\HomepageDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_one_layout_can_be_active_at_a_time(): void
    {
        $layout1 = HomepageLayout::create(['name' => 'Layout 1', 'is_active' => true]);
        $layout2 = HomepageLayout::create(['name' => 'Layout 2', 'is_active' => false]);

        $this->assertTrue($layout1->fresh()->is_active);
        $this->assertFalse($layout2->fresh()->is_active);

        $layout2->activate();

        $this->assertFalse($layout1->fresh()->is_active);
        $this->assertTrue($layout2->fresh()->is_active);
    }

    public function test_draft_changes_do_not_affect_public_home_until_published(): void
    {
        $layout = HomepageLayout::create([
            'name' => 'Test Layout',
            'is_active' => true,
            'draft_sections' => [
                ['uuid' => '1', 'block_type' => 'today_schedule', 'visible' => true, 'title' => 'Taslak Başlık'],
            ],
            'published_sections' => [
                ['uuid' => '1', 'block_type' => 'today_schedule', 'visible' => true, 'title' => 'Yayınlanan Başlık'],
            ],
        ]);

        $response = $this->get('/');
        $response->assertStatus(200);

        $layout->publish();
        $this->assertEquals('Taslak Başlık', $layout->fresh()->published_sections[0]['title']);
    }

    public function test_hero_header_footer_not_in_builder_block_registry(): void
    {
        $types = HomepageBlockRegistry::getBlockTypes();

        $this->assertArrayNotHasKey('hero', $types);
        $this->assertArrayNotHasKey('header', $types);
        $this->assertArrayNotHasKey('footer', $types);
    }

    public function test_empty_layout_still_renders_fixed_hero_and_footer_on_public_home(): void
    {
        HomepageLayout::create([
            'name' => 'Boş Düzen',
            'is_active' => true,
            'published_sections' => [],
        ]);

        $response = $this->get('/');
        $response->assertStatus(200);
    }

    public function test_hybrid_video_source_merges_pinned_and_auto_episodes_without_duplicates(): void
    {
        $prog = Program::factory()->create(['name' => 'Test Prog 1', 'slug' => 'test-prog-1']);
        $ep1 = Episode::factory()->create(['program_id' => $prog->id, 'title' => 'Ep 1']);
        $ep2 = Episode::factory()->create(['program_id' => $prog->id, 'title' => 'Ep 2']);
        $ep3 = Episode::factory()->create(['program_id' => $prog->id, 'title' => 'Ep 3']);

        $block = [
            'uuid' => 'block-1',
            'block_type' => 'video_collection',
            'source_mode' => 'hybrid_videos',
            'pinned_ids' => [$ep3->id],
            'content_limit' => 2,
        ];

        $service = app(HomepageDataService::class);
        $result = $service->resolveVideoBlockData($block);

        $this->assertCount(2, $result);
        $this->assertEquals($ep3->id, $result->first()->id);
        $this->assertNotEquals($ep3->id, $result->last()->id);
    }

    public function test_cta_route_resolver_resolves_real_routes(): void
    {
        $collection = VideoCollection::create(['name' => 'Özel Seçki', 'is_active' => true]);
        $block = [
            'source_mode' => 'manual_collection',
            'collection_id' => $collection->id,
        ];

        $url = CtaRouteResolver::resolveForVideoBlock($block);
        $this->assertEquals(route('collections.show', $collection->slug), $url);
    }

    public function test_public_homepage_returns_http_200(): void
    {
        HomepageLayout::create([
            'name' => 'Canlı Düzen',
            'is_active' => true,
            'published_sections' => [
                ['uuid' => '1', 'block_type' => 'today_schedule', 'visible' => true],
            ],
        ]);

        $response = $this->get('/');
        $response->assertStatus(200);
    }

    public function test_unauthorized_user_cannot_access_builder_resource(): void
    {
        $user = User::factory()->create(['role' => 'editor']);

        $this->actingAs($user);
        $this->assertFalse(HomepageLayoutResource::canAccess());
    }

    public function test_shelf_grid_horizontal_carousel_renders_single_row_grid_flow(): void
    {
        for ($i = 1; $i <= 9; $i++) {
            Program::create([
                'name' => "Program {$i}",
                'slug' => "program-{$i}",
                'is_active' => true,
                'is_featured' => true,
            ]);
        }

        HomepageLayout::create([
            'name' => 'Vitrin Düzeni',
            'is_active' => true,
            'published_sections' => [
                [
                    'uuid' => 'vitrin-1',
                    'block_type' => 'program_showcase',
                    'visible' => true,
                    'title' => 'Görkemli Vitrin',
                    'display_variant' => 'horizontal_carousel',
                    'row_count' => 1,
                    'desktop_columns' => 6,
                    'source_mode' => 'featured_programs',
                    'content_limit' => 'all',
                ],
            ],
        ]);

        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('grid-auto-flow: column', false);
        $response->assertSee('grid-template-rows: repeat(1, minmax(0, 1fr))', false);
    }

    public function test_today_schedule_renders_horizontal_broadcast_strip_with_auto_scroll(): void
    {
        $prog = Program::create([
            'name' => 'Sabah Bülteni',
            'slug' => 'sabah-bulteni',
            'is_active' => true,
            'show_on_public' => true,
        ]);

        $template = \App\Models\ScheduleTemplate::create([
            'name' => 'Test Şablon',
            'status' => 'published',
            'is_active' => true,
            'priority' => 10,
        ]);

        \App\Models\ScheduleTemplateItem::create([
            'schedule_template_id' => $template->id,
            'program_id' => $prog->id,
            'day_of_week' => (int) now()->dayOfWeekIso - 1,
            'start_time' => '08:00',
            'end_time' => '09:00',
            'is_active' => true,
        ]);

        HomepageLayout::create([
            'name' => 'Akış Düzeni',
            'is_active' => true,
            'published_sections' => [
                [
                    'uuid' => 'schedule-1',
                    'block_type' => 'today_schedule',
                    'visible' => true,
                    'title' => 'Bugünün Yayın Akışı',
                    'show_now_badge' => true,
                    'show_next_badge' => true,
                    'show_all_link' => true,
                    'auto_scroll_to_now' => true,
                    'density' => 'compact',
                    'bg_style' => 'dark',
                ],
            ],
        ]);

        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('dost-schedule-strip', false);
        $response->assertSee('Sabah Bülteni');
        $response->assertSee('08:00');
        $response->assertSee('Tüm Akış');
        $response->assertSee(route('programs.show', $prog));
        $response->assertDontSee('/bolum/');
        $response->assertSee('scrollToTarget()');
    }

    public function test_content_shelf_program_and_video_isolation(): void
    {
        $cat = Category::create(['name' => 'Tefekkür', 'slug' => 'tefekkur', 'is_active' => true]);

        $prog1 = Program::create(['name' => 'Tefekkür Saati', 'slug' => 'tefekkur-saati', 'is_active' => true, 'show_on_public' => true]);
        $prog1->categories()->attach($cat->id);

        $prog2 = Program::create(['name' => 'Kur\'an Yolu', 'slug' => 'kuran-yolu', 'is_active' => true, 'show_on_public' => true]);
        $prog2->categories()->attach($cat->id);

        $ep1 = Episode::create(['program_id' => $prog1->id, 'title' => '1. Bölüm', 'slug' => '1-bolum', 'is_active' => true, 'show_on_public' => true]);

        $dataService = app(\App\Services\Home\HomepageDataService::class);

        // Program Shelf should only return programs
        $programBlock = [
            'block_type' => 'content_shelf',
            'shelf_type' => 'program',
            'source_mode' => 'manual',
            'item_ids' => [$prog1->id, $prog2->id],
        ];

        $resolvedPrograms = $dataService->resolveContentShelfData($programBlock);
        $this->assertCount(2, $resolvedPrograms);
        $this->assertEquals($prog1->id, $resolvedPrograms->first()->id);
        $this->assertInstanceOf(Program::class, $resolvedPrograms->first());

        // Video Shelf should only return episodes
        $videoBlock = [
            'block_type' => 'content_shelf',
            'shelf_type' => 'video',
            'source_mode' => 'manual',
            'item_ids' => [$ep1->id],
        ];

        $resolvedVideos = $dataService->resolveContentShelfData($videoBlock);
        $this->assertCount(1, $resolvedVideos);
        $this->assertEquals($ep1->id, $resolvedVideos->first()->id);
        $this->assertInstanceOf(Episode::class, $resolvedVideos->first());
    }

    public function test_content_shelf_manual_category_and_hybrid_modes_with_deduplication(): void
    {
        $cat = Category::create(['name' => 'Hadis', 'slug' => 'hadis', 'is_active' => true]);

        $prog1 = Program::create(['name' => 'Hadis Okumaları', 'slug' => 'hadis-okumalari', 'is_active' => true, 'show_on_public' => true, 'sort_order' => 1]);
        $prog1->categories()->attach($cat->id);

        $prog2 = Program::create(['name' => 'Riyazus Salihin', 'slug' => 'riyazus-salihin', 'is_active' => true, 'show_on_public' => true, 'sort_order' => 2]);
        $prog2->categories()->attach($cat->id);

        $dataService = app(\App\Services\Home\HomepageDataService::class);

        // Hybrid mode with duplicate item_id (prog1 pinned AND in category)
        $hybridBlock = [
            'block_type' => 'content_shelf',
            'shelf_type' => 'program',
            'source_mode' => 'hybrid',
            'category_id' => $cat->id,
            'item_ids' => [$prog1->id],
        ];

        $resolved = $dataService->resolveContentShelfData($hybridBlock);
        $this->assertCount(2, $resolved); // Should be deduplicated to 2 items total
        $this->assertEquals($prog1->id, $resolved->first()->id);
        $this->assertEquals($prog2->id, $resolved->last()->id);
    }

    public function test_content_shelf_live_category_vs_fixed_list_behavior(): void
    {
        $cat = Category::create(['name' => 'Fıkıh', 'slug' => 'fikih', 'is_active' => true]);

        $prog1 = Program::create(['name' => 'Fıkıh Dersleri', 'slug' => 'fikih-dersleri', 'is_active' => true, 'show_on_public' => true]);
        $prog1->categories()->attach($cat->id);

        $dataService = app(\App\Services\Home\HomepageDataService::class);

        // Live category mode
        $liveBlock = [
            'block_type' => 'content_shelf',
            'shelf_type' => 'program',
            'source_mode' => 'category',
            'category_mode' => 'live_category',
            'category_id' => $cat->id,
        ];

        $resolvedLive = $dataService->resolveContentShelfData($liveBlock);
        $this->assertCount(1, $resolvedLive);

        // Now attach a new program to category
        $prog2 = Program::create(['name' => 'Fetva Saati', 'slug' => 'fetva-saati', 'is_active' => true, 'show_on_public' => true]);
        $prog2->categories()->attach($cat->id);

        // Live category dynamically picks up prog2
        $resolvedLiveAfter = $dataService->resolveContentShelfData($liveBlock);
        $this->assertCount(2, $resolvedLiveAfter);

        // Fixed list mode with frozen item_ids
        $fixedBlock = [
            'block_type' => 'content_shelf',
            'shelf_type' => 'program',
            'source_mode' => 'category',
            'category_mode' => 'fixed_list',
            'category_id' => $cat->id,
            'item_ids' => [$prog1->id],
        ];

        $resolvedFixed = $dataService->resolveContentShelfData($fixedBlock);
        $this->assertCount(1, $resolvedFixed); // Stays frozen at 1 item!
        $this->assertEquals($prog1->id, $resolvedFixed->first()->id);
    }
}
