<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnifiedLiveHeaderNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        SiteSetting::current()->update([
            'site_name' => 'Dost TV',
            'live_button_text' => 'Canlı',
            'live_button_is_visible' => true,
            'search_is_visible' => true,
        ]);

        $menu = Menu::create([
            'name' => 'Ana Üst Menü',
            'location' => 'header_primary',
            'is_active' => true,
        ]);

        MenuItem::create([
            'menu_id' => $menu->id,
            'title' => 'Programlar',
            'slug' => 'programlar',
            'item_type' => 'program_mega_menu',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        MenuItem::create([
            'menu_id' => $menu->id,
            'title' => 'Yayın Akışı',
            'slug' => 'yayin-akisi',
            'item_type' => 'route',
            'route_name' => 'schedule.index',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        MenuItem::create([
            'menu_id' => $menu->id,
            'title' => 'Hatim / Cüz Al',
            'slug' => 'hatim-cuz-al',
            'item_type' => 'url',
            'url' => '#',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        $canliDropdown = MenuItem::create([
            'menu_id' => $menu->id,
            'title' => 'Canlı',
            'slug' => 'canli',
            'item_type' => 'dropdown',
            'sort_order' => 3,
            'is_active' => true,
        ]);

        MenuItem::create([
            'menu_id' => $menu->id,
            'parent_id' => $canliDropdown->id,
            'title' => 'Dost TV Canlı',
            'slug' => 'dost-tv-canli',
            'item_type' => 'live_tv',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        MenuItem::create([
            'menu_id' => $menu->id,
            'parent_id' => $canliDropdown->id,
            'title' => 'Dost FM Canlı',
            'slug' => 'dost-fm-canli',
            'item_type' => 'live_radio',
            'sort_order' => 1,
            'is_active' => true,
        ]);
    }

    public function test_desktop_header_renders_direct_live_cta_without_dropdown(): void
    {
        $response = $this->get('/');
        $response->assertOk();

        // 1. Direct Live CTA exists in header linking directly to live.tv
        $response->assertSee(route('live.tv'));
        $response->assertSee('Canlı');

        // 2. Header does NOT contain dropdown menu items or dropdown arrows
        $response->assertDontSee('Televizyon Yayını');
        $response->assertDontSee('Radyo Yayını');

        // 3. Main navigation items exist
        $response->assertSee('Programlar');
        $response->assertSee('Yayın Akışı');
        $response->assertSee('Hatim / Cüz Al');

        // 4. Search button exists
        $response->assertSee('aria-label="Arama"', false);
    }

    public function test_canli_tv_page_renders_player_and_dost_fm_button(): void
    {
        $response = $this->get(route('live.tv'));
        $response->assertOk();

        // TV Player and title
        $response->assertSee('Canlı TV');

        // Dost FM CTA button inside Canlı TV page
        $response->assertSee('Dost FM Canlı');
        $response->assertSee('Gönüllerin sesi Dost FM yayınını canlı dinleyin.');
        $response->assertSee(route('live.radio'));
    }

    public function test_live_radio_route_responds_with_200(): void
    {
        $response = $this->get(route('live.radio'));
        $response->assertOk();
        $response->assertSee('Canlı Radyo');
    }
}
