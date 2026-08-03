<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\TopHeader;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\User;
use App\Services\Menu\MenuService;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

class TopHeaderPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_access_top_header_page(): void
    {
        $user = User::factory()->create(['role' => 'administrator']);

        $this->actingAs($user)
            ->get(TopHeader::getUrl())
            ->assertOk();
    }

    public function test_unauthorized_user_cannot_access_top_header_page(): void
    {
        $user = User::factory()->create(['role' => 'viewer']);

        $this->actingAs($user)
            ->get(TopHeader::getUrl())
            ->assertForbidden();
    }

    public function test_header_primary_menu_is_automatically_loaded_or_created(): void
    {
        $user = User::factory()->create(['role' => 'administrator']);

        $this->actingAs($user);

        Livewire::test(TopHeader::class)
            ->assertOk();

        $this->assertDatabaseHas('menus', [
            'location' => 'header_primary',
            'is_active' => true,
        ]);
    }

    public function test_initial_menu_hierarchy_seeded_idempotently(): void
    {
        $user = User::factory()->create(['role' => 'administrator']);

        $this->actingAs($user);

        Livewire::test(TopHeader::class);

        $menu = Menu::where('location', 'header_primary')->first();
        $this->assertNotNull($menu);

        $this->assertDatabaseHas('menu_items', [
            'menu_id' => $menu->id,
            'title' => 'Programlar',
            'item_type' => 'program_mega_menu',
            'route_name' => 'programs.index',
        ]);

        $this->assertDatabaseHas('menu_items', [
            'menu_id' => $menu->id,
            'title' => 'Yayın Akışı',
            'item_type' => 'route',
            'route_name' => 'schedule.index',
        ]);

        $this->assertDatabaseHas('menu_items', [
            'menu_id' => $menu->id,
            'title' => 'Hatim / Cüz Al',
            'item_type' => 'url',
        ]);

        $canli = MenuItem::where('menu_id', $menu->id)->where('title', 'Canlı')->first();
        $this->assertNotNull($canli);

        $this->assertDatabaseHas('menu_items', [
            'menu_id' => $menu->id,
            'parent_id' => $canli->id,
            'title' => 'Dost TV Canlı',
            'item_type' => 'live_tv',
        ]);

        $this->assertDatabaseHas('menu_items', [
            'menu_id' => $menu->id,
            'parent_id' => $canli->id,
            'title' => 'Dost FM Canlı',
            'item_type' => 'live_radio',
        ]);
    }

    public function test_can_add_edit_and_delete_menu_items(): void
    {
        $user = User::factory()->create(['role' => 'administrator']);

        $this->actingAs($user);

        $menu = Menu::firstOrCreate(['location' => 'header_primary'], ['name' => 'Ana Üst Menü']);

        $item = MenuItem::create([
            'menu_id' => $menu->id,
            'title' => 'Test Başlık',
            'item_type' => 'route',
            'route_name' => 'home',
            'sort_order' => 5,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('menu_items', ['id' => $item->id, 'title' => 'Test Başlık']);

        $item->update(['title' => 'Güncellenmiş Başlık']);
        $this->assertDatabaseHas('menu_items', ['id' => $item->id, 'title' => 'Güncellenmiş Başlık']);

        $item->delete();
        $this->assertDatabaseMissing('menu_items', ['id' => $item->id]);
    }

    public function test_public_header_and_cache_behavior(): void
    {
        $menu = Menu::firstOrCreate(['location' => 'header_primary'], ['name' => 'Ana Üst Menü', 'is_active' => true]);

        MenuItem::create([
            'menu_id' => $menu->id,
            'title' => 'Test Aktif Öğe',
            'item_type' => 'route',
            'route_name' => 'programs.index',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        MenuItem::create([
            'menu_id' => $menu->id,
            'title' => 'Test Pasif Öğe',
            'item_type' => 'route',
            'route_name' => 'schedule.index',
            'is_active' => false,
            'sort_order' => 2,
        ]);

        Cache::flush();

        $menuService = app(MenuService::class);
        $items = $menuService->forLocation('header_primary');

        $this->assertTrue($items->contains('title', 'Test Aktif Öğe'));
        $this->assertFalse($items->contains('title', 'Test Pasif Öğe'));
    }

    public function test_public_routes_return_status_200(): void
    {
        $this->get(route('home'))->assertOk();
        $this->get(route('programs.index'))->assertOk();
        $this->get(route('schedule.index'))->assertOk();
        $this->get(route('live.tv'))->assertOk();
        $this->get(route('live.radio'))->assertOk();
    }
}
