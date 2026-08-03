<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\MenuItem;
use App\Support\SiteCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class MenuCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_saving_a_menu_item_clears_the_menu_cache(): void
    {
        $menu = Menu::factory()->create(['location' => 'header_primary']);
        $item = MenuItem::create(['menu_id' => $menu->id, 'title' => 'Programlar', 'item_type' => 'route', 'route_name' => 'programs.index', 'sort_order' => 0]);

        SiteCache::rememberMenu('header_primary', fn () => 'stale-cached-value');
        $this->assertTrue(Cache::has(SiteCache::menuKey('header_primary')));

        $item->update(['title' => 'Programlarımız']);

        $this->assertFalse(Cache::has(SiteCache::menuKey('header_primary')));
    }

    public function test_deleting_a_menu_item_clears_the_menu_cache(): void
    {
        $menu = Menu::factory()->create(['location' => 'header_primary']);
        $item = MenuItem::create(['menu_id' => $menu->id, 'title' => 'Programlar', 'item_type' => 'route', 'route_name' => 'programs.index', 'sort_order' => 0]);

        SiteCache::rememberMenu('header_primary', fn () => 'stale-cached-value');
        $this->assertTrue(Cache::has(SiteCache::menuKey('header_primary')));

        $item->delete();

        $this->assertFalse(Cache::has(SiteCache::menuKey('header_primary')));
    }
}
