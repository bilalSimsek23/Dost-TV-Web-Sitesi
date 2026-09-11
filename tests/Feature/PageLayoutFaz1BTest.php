<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Episode;
use App\Models\HomepageLayout;
use App\Models\Program;
use App\Support\SiteCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageLayoutFaz1BTest extends TestCase
{
    use RefreshDatabase;

    public function test_program_detail_layout_renders_dynamic_sections_when_active()
    {
        $program = Program::factory()->create([
            'name' => 'Hikmet Arayışları',
            'slug' => 'hikmet-arayislari',
            'is_active' => true,
            'show_on_public' => true,
            'status' => 'published',
            'description' => 'Detaylı program açıklaması burada yer alır.',
        ]);

        $layout = HomepageLayout::create([
            'name' => 'Program Detay Sayfası - Varsayılan Düzen',
            'page_type' => 'program_detail',
            'is_active' => true,
            'published_sections' => [
                ['uuid' => 'hero-1', 'block_type' => 'program_hero', 'visible' => true],
                ['uuid' => 'desc-1', 'block_type' => 'program_description', 'visible' => true, 'title' => 'Açıklama'],
            ],
            'published_at' => now(),
        ]);

        $response = $this->get(route('programs.show', $program));

        $response->assertOk();
        $response->assertSee('Hikmet Arayışları');
        $response->assertSee('Detaylı program açıklaması burada yer alır.');
    }

    public function test_fallback_to_legacy_view_when_no_active_layout_or_empty_published_sections()
    {
        $program = Program::factory()->create([
            'name' => 'Akla Kapı',
            'slug' => 'akla-kapi',
            'is_active' => true,
            'show_on_public' => true,
            'status' => 'published',
        ]);

        // Scenario 1: No layout exists
        $response = $this->get(route('programs.show', $program));
        $response->assertOk();
        $response->assertSee('Akla Kapı');

        // Scenario 2: Active layout exists but published_sections is empty
        HomepageLayout::create([
            'name' => 'Boş Şablon',
            'page_type' => 'program_detail',
            'is_active' => true,
            'published_sections' => [],
            'published_at' => now(),
        ]);

        SiteCache::forgetProgramDetailLayout();

        $response2 = $this->get(route('programs.show', $program));
        $response2->assertOk();
        $response2->assertSee('Akla Kapı');
    }

    public function test_two_different_programs_render_distinct_content_with_same_layout()
    {
        $prog1 = Program::factory()->create([
            'name' => 'Program Bir',
            'slug' => 'program-bir',
            'is_active' => true,
            'show_on_public' => true,
            'status' => 'published',
        ]);

        $prog2 = Program::factory()->create([
            'name' => 'Program İki',
            'slug' => 'program-iki',
            'is_active' => true,
            'show_on_public' => true,
            'status' => 'published',
        ]);

        HomepageLayout::create([
            'name' => 'Program Detay Şablonu',
            'page_type' => 'program_detail',
            'is_active' => true,
            'published_sections' => [
                ['uuid' => 'hero-1', 'block_type' => 'program_hero', 'visible' => true],
            ],
            'published_at' => now(),
        ]);

        SiteCache::forgetProgramDetailLayout();

        $res1 = $this->get(route('programs.show', $prog1));
        $res1->assertOk();
        $res1->assertSee('Program Bir');
        $res1->assertDontSee('Program İki');

        $res2 = $this->get(route('programs.show', $prog2));
        $res2->assertOk();
        $res2->assertSee('Program İki');
        $res2->assertDontSee('Program Bir');
    }

    public function test_episodes_shelf_renders_correct_episodes_belonging_only_to_current_program()
    {
        $prog1 = Program::factory()->create(['name' => 'Prog Bir', 'slug' => 'prog-bir', 'is_active' => true, 'show_on_public' => true, 'status' => 'published']);
        $prog2 = Program::factory()->create(['name' => 'Prog İki', 'slug' => 'prog-iki', 'is_active' => true, 'show_on_public' => true, 'status' => 'published']);

        $ep1 = Episode::factory()->create([
            'program_id' => $prog1->id,
            'title' => 'Prog1 Bölüm 1',
            'is_active' => true,
            'show_on_public' => true,
        ]);

        $ep2 = Episode::factory()->create([
            'program_id' => $prog2->id,
            'title' => 'Prog2 Bölüm 1',
            'is_active' => true,
            'show_on_public' => true,
        ]);

        HomepageLayout::create([
            'name' => 'Program Detay Şablonu',
            'page_type' => 'program_detail',
            'is_active' => true,
            'published_sections' => [
                ['uuid' => 'episodes-1', 'block_type' => 'episodes_shelf', 'visible' => true],
            ],
            'published_at' => now(),
        ]);

        SiteCache::forgetProgramDetailLayout();

        $res = $this->get(route('programs.show', $prog1));
        $res->assertOk();
        $res->assertSee('Prog1 Bölüm 1');
        $res->assertDontSee('Prog2 Bölüm 1');
    }

    public function test_archived_or_non_public_program_access_rules_are_strictly_preserved()
    {
        $archived = Program::factory()->create([
            'name' => 'Arşiv Program',
            'slug' => 'arsiv-program',
            'is_active' => true,
            'show_on_public' => true,
            'status' => 'archived',
        ]);

        HomepageLayout::create([
            'name' => 'Program Detay Şablonu',
            'page_type' => 'program_detail',
            'is_active' => true,
            'published_sections' => [
                ['uuid' => 'hero-1', 'block_type' => 'program_hero', 'visible' => true],
            ],
            'published_at' => now(),
        ]);

        SiteCache::forgetProgramDetailLayout();

        // Non-admin request should 404
        $res = $this->get(route('programs.show', $archived));
        $res->assertStatus(404);
    }

    public function test_home_layout_is_completely_isolated_from_program_detail_layout()
    {
        $homeLayout = HomepageLayout::create([
            'name' => 'Ana Sayfa Canlı',
            'page_type' => 'home',
            'is_active' => true,
        ]);

        $programDetailLayout = HomepageLayout::create([
            'name' => 'Program Detay Canlı',
            'page_type' => 'program_detail',
            'is_active' => false,
        ]);

        // Activating program_detail layout must NOT deactivate home layout!
        $programDetailLayout->activate();

        $this->assertTrue($homeLayout->fresh()->is_active);
        $this->assertTrue($programDetailLayout->fresh()->is_active);
    }
}
