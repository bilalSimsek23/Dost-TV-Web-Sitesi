<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\MenuItem;
use App\Services\Menu\MenuService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MenuServiceTest extends TestCase
{
    use RefreshDatabase;

    private MenuService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new MenuService;
    }

    public function test_returns_empty_collection_when_menu_does_not_exist(): void
    {
        $items = $this->service->forLocation('header_primary');

        $this->assertTrue($items->isEmpty());
    }

    public function test_returns_empty_collection_when_menu_is_inactive(): void
    {
        $menu = Menu::factory()->create(['location' => 'header_primary', 'is_active' => false]);
        MenuItem::create(['menu_id' => $menu->id, 'title' => 'Programlar', 'item_type' => 'route', 'route_name' => 'programs.index', 'sort_order' => 0]);

        $items = $this->service->forLocation('header_primary');

        $this->assertTrue($items->isEmpty());
    }

    public function test_items_are_returned_in_sort_order(): void
    {
        $menu = Menu::factory()->create(['location' => 'header_primary']);

        MenuItem::create(['menu_id' => $menu->id, 'title' => 'İkinci', 'item_type' => 'url', 'url' => '#', 'sort_order' => 2]);
        MenuItem::create(['menu_id' => $menu->id, 'title' => 'Birinci', 'item_type' => 'url', 'url' => '#', 'sort_order' => 1]);
        MenuItem::create(['menu_id' => $menu->id, 'title' => 'Üçüncü', 'item_type' => 'url', 'url' => '#', 'sort_order' => 3]);

        $titles = $this->service->forLocation('header_primary')->pluck('title')->all();

        $this->assertSame(['Birinci', 'İkinci', 'Üçüncü'], $titles);
    }

    public function test_inactive_item_is_excluded(): void
    {
        $menu = Menu::factory()->create(['location' => 'header_primary']);

        MenuItem::create(['menu_id' => $menu->id, 'title' => 'Aktif', 'item_type' => 'url', 'url' => '#', 'is_active' => true, 'sort_order' => 0]);
        MenuItem::create(['menu_id' => $menu->id, 'title' => 'Pasif', 'item_type' => 'url', 'url' => '#', 'is_active' => false, 'sort_order' => 1]);

        $titles = $this->service->forLocation('header_primary')->pluck('title')->all();

        $this->assertSame(['Aktif'], $titles);
    }

    public function test_nested_menu_is_built_correctly(): void
    {
        $menu = Menu::factory()->create(['location' => 'header_primary']);

        $parent = MenuItem::create(['menu_id' => $menu->id, 'title' => 'Canlı Yayın', 'item_type' => 'dropdown', 'sort_order' => 0]);
        MenuItem::create(['menu_id' => $menu->id, 'parent_id' => $parent->id, 'title' => 'Canlı TV', 'item_type' => 'live_tv', 'sort_order' => 0]);
        MenuItem::create(['menu_id' => $menu->id, 'parent_id' => $parent->id, 'title' => 'Canlı Radyo', 'item_type' => 'live_radio', 'sort_order' => 1]);

        $items = $this->service->forLocation('header_primary');

        $this->assertCount(1, $items);
        $this->assertCount(2, $items->first()->children);
        $this->assertSame(['Canlı TV', 'Canlı Radyo'], $items->first()->children->pluck('title')->all());
    }

    public function test_exceeding_max_depth_is_rejected(): void
    {
        $menu = Menu::factory()->create(['location' => 'header_primary']);

        $level1 = MenuItem::create(['menu_id' => $menu->id, 'title' => 'Seviye 1', 'item_type' => 'dropdown', 'sort_order' => 0]);
        $level2 = MenuItem::create(['menu_id' => $menu->id, 'parent_id' => $level1->id, 'title' => 'Seviye 2', 'item_type' => 'dropdown', 'sort_order' => 0]);
        $level3 = MenuItem::create(['menu_id' => $menu->id, 'parent_id' => $level2->id, 'title' => 'Seviye 3', 'item_type' => 'url', 'url' => '#', 'sort_order' => 0]);

        $this->expectException(ValidationException::class);

        MenuItem::create(['menu_id' => $menu->id, 'parent_id' => $level3->id, 'title' => 'Seviye 4', 'item_type' => 'url', 'url' => '#', 'sort_order' => 0]);
    }
}
