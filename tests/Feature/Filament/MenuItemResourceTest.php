<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\MenuItems\Pages\CreateMenuItem;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\User;
use App\Support\SafeUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class MenuItemResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'super_admin']);
    }

    public function test_menu_item_can_be_created_through_the_admin_form(): void
    {
        $menu = Menu::factory()->create();

        Livewire::actingAs($this->admin)
            ->test(CreateMenuItem::class)
            ->fillForm([
                'menu_id' => $menu->id,
                'title' => 'Programlar',
                'item_type' => 'route',
                'route_name' => 'programs.index',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('menu_items', [
            'menu_id' => $menu->id,
            'title' => 'Programlar',
            'route_name' => 'programs.index',
        ]);
    }

    public function test_menu_item_cannot_pick_a_parent_from_a_different_menu(): void
    {
        $menuA = Menu::factory()->create(['location' => 'header_primary']);
        $menuB = Menu::factory()->create(['location' => 'footer_primary']);

        $parentInMenuB = MenuItem::create([
            'menu_id' => $menuB->id,
            'title' => 'Diğer Menü Öğesi',
            'item_type' => 'dropdown',
            'sort_order' => 0,
        ]);

        $this->expectException(ValidationException::class);

        MenuItem::create([
            'menu_id' => $menuA->id,
            'parent_id' => $parentInMenuB->id,
            'title' => 'Yanlış Menüden Üst Öğe',
            'item_type' => 'url',
            'url' => '/deneme',
            'sort_order' => 0,
        ]);
    }

    public function test_menu_item_cannot_become_its_own_parent(): void
    {
        $menu = Menu::factory()->create();

        $item = MenuItem::create([
            'menu_id' => $menu->id,
            'title' => 'Kendim',
            'item_type' => 'dropdown',
            'sort_order' => 0,
        ]);

        $this->expectException(ValidationException::class);

        $item->update(['parent_id' => $item->id]);
    }

    public function test_menu_item_cannot_pick_a_descendant_as_its_parent(): void
    {
        $menu = Menu::factory()->create();

        $grandparent = MenuItem::create(['menu_id' => $menu->id, 'title' => 'Büyükanne', 'item_type' => 'dropdown', 'sort_order' => 0]);
        $parent = MenuItem::create(['menu_id' => $menu->id, 'parent_id' => $grandparent->id, 'title' => 'Anne', 'item_type' => 'dropdown', 'sort_order' => 0]);

        $this->expectException(ValidationException::class);

        $grandparent->update(['parent_id' => $parent->id]);
    }

    public function test_maximum_menu_depth_of_three_is_enforced(): void
    {
        $menu = Menu::factory()->create();

        $level1 = MenuItem::create(['menu_id' => $menu->id, 'title' => 'Seviye 1', 'item_type' => 'dropdown', 'sort_order' => 0]);
        $level2 = MenuItem::create(['menu_id' => $menu->id, 'parent_id' => $level1->id, 'title' => 'Seviye 2', 'item_type' => 'dropdown', 'sort_order' => 0]);
        $level3 = MenuItem::create(['menu_id' => $menu->id, 'parent_id' => $level2->id, 'title' => 'Seviye 3', 'item_type' => 'url', 'url' => '/x', 'sort_order' => 0]);

        $this->expectException(ValidationException::class);

        MenuItem::create(['menu_id' => $menu->id, 'parent_id' => $level3->id, 'title' => 'Seviye 4', 'item_type' => 'url', 'url' => '/y', 'sort_order' => 0]);
    }

    public function test_dangerous_url_schemes_are_rejected_by_safe_url_helper(): void
    {
        $this->assertFalse(SafeUrl::isSafe('javascript:alert(1)'));
        $this->assertFalse(SafeUrl::isSafe('data:text/html,<script>alert(1)</script>'));
        $this->assertFalse(SafeUrl::isSafe('vbscript:msgbox(1)'));

        $this->assertTrue(SafeUrl::isSafe('https://dosttv.com'));
        $this->assertTrue(SafeUrl::isSafe('/programlar'));
        $this->assertTrue(SafeUrl::isSafe('#'));
        $this->assertTrue(SafeUrl::isSafe(null));
    }

    public function test_menu_item_form_rejects_a_dangerous_url(): void
    {
        $menu = Menu::factory()->create();

        Livewire::actingAs($this->admin)
            ->test(CreateMenuItem::class)
            ->fillForm([
                'menu_id' => $menu->id,
                'title' => 'Kötü Niyetli Bağlantı',
                'item_type' => 'url',
                'url' => 'javascript:alert(1)',
            ])
            ->call('create')
            ->assertHasFormErrors(['url']);

        $this->assertDatabaseMissing('menu_items', ['title' => 'Kötü Niyetli Bağlantı']);
    }
}
