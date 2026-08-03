<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\User;
use App\Services\Menu\MenuService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TopHeaderHierarchicalNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_for_admin_location_returns_all_items_including_inactive(): void
    {
        $menu = Menu::create([
            'name' => 'Ana Üst Menü',
            'location' => 'header_primary',
            'is_active' => true,
        ]);

        MenuItem::create([
            'menu_id' => $menu->id,
            'title' => 'Aktif Öğe',
            'item_type' => 'url',
            'url' => '#',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        MenuItem::create([
            'menu_id' => $menu->id,
            'title' => 'Pasif Öğe',
            'item_type' => 'url',
            'url' => '#',
            'is_active' => false,
            'sort_order' => 2,
        ]);

        $menuService = app(MenuService::class);
        $adminItems = $menuService->forAdminLocation('header_primary');
        $publicItems = $menuService->forLocation('header_primary');

        $this->assertCount(2, $adminItems);
        $this->assertCount(1, $publicItems);
        $this->assertTrue($adminItems->contains('title', 'Pasif Öğe'));
        $this->assertFalse($publicItems->contains('title', 'Pasif Öğe'));
    }

    public function test_reorder_top_header_menu_items(): void
    {
        $user = User::factory()->create(['role' => 'administrator']);

        $menu = Menu::create([
            'name' => 'Ana Üst Menü',
            'location' => 'header_primary',
            'is_active' => true,
        ]);

        $item1 = MenuItem::create(['menu_id' => $menu->id, 'title' => 'Öğe 1', 'item_type' => 'url', 'sort_order' => 1]);
        $item2 = MenuItem::create(['menu_id' => $menu->id, 'title' => 'Öğe 2', 'item_type' => 'url', 'sort_order' => 2]);

        $payload = [
            'items' => [
                ['id' => $item2->id, 'position' => 0],
                ['id' => $item1->id, 'position' => 1],
            ],
        ];

        $response = $this->actingAs($user)
            ->postJson(route('admin.menu-items.reorder'), $payload);

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertSame(0, $item2->fresh()->sort_order);
        $this->assertSame(1, $item1->fresh()->sort_order);
    }
}
