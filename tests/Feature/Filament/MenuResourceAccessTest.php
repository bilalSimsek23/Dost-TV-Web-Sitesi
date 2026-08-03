<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\ThemeSettings;
use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\FontFamilies\FontFamilyResource;
use App\Filament\Resources\MenuItems\MenuItemResource;
use App\Filament\Resources\Menus\MenuResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuResourceAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_access_menu_resource(): void
    {
        $user = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($user)
            ->get(MenuResource::getUrl('index'))
            ->assertOk();
    }

    public function test_administrator_can_access_menu_resource(): void
    {
        $user = User::factory()->create(['role' => 'administrator']);

        $this->actingAs($user)
            ->get(MenuResource::getUrl('index'))
            ->assertOk();
    }

    public function test_editor_cannot_access_menu_resource(): void
    {
        $user = User::factory()->create(['role' => 'editor']);

        $this->actingAs($user)
            ->get(MenuResource::getUrl('index'))
            ->assertForbidden();
    }

    public function test_designer_cannot_access_menu_item_resource(): void
    {
        $user = User::factory()->create(['role' => 'designer']);

        $this->actingAs($user)
            ->get(MenuItemResource::getUrl('index'))
            ->assertForbidden();
    }

    public function test_editor_can_view_category_resource_but_not_create(): void
    {
        $user = User::factory()->create(['role' => 'editor']);

        $this->actingAs($user)
            ->get(CategoryResource::getUrl('index'))
            ->assertOk();

        $this->actingAs($user)
            ->get(CategoryResource::getUrl('create'))
            ->assertForbidden();
    }

    public function test_designer_can_access_theme_settings_and_font_resource(): void
    {
        $user = User::factory()->create(['role' => 'designer']);

        $this->actingAs($user)
            ->get(ThemeSettings::getUrl())
            ->assertOk();

        $this->actingAs($user)
            ->get(FontFamilyResource::getUrl('index'))
            ->assertOk();
    }

    public function test_editor_cannot_access_theme_settings(): void
    {
        $user = User::factory()->create(['role' => 'editor']);

        $this->actingAs($user)
            ->get(ThemeSettings::getUrl())
            ->assertForbidden();
    }
}
