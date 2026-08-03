<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Menus\Pages\CreateMenu;
use App\Models\Menu;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class MenuResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'super_admin']);
    }

    public function test_menu_can_be_created_through_the_admin_form(): void
    {
        Livewire::actingAs($this->admin)
            ->test(CreateMenu::class)
            ->fillForm([
                'name' => 'Ana Menü',
                'location' => 'header_primary',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('menus', [
            'name' => 'Ana Menü',
            'location' => 'header_primary',
        ]);
    }

    public function test_only_one_active_menu_is_allowed_per_location(): void
    {
        Menu::factory()->create(['location' => 'header_primary', 'is_active' => true]);

        $this->expectException(ValidationException::class);

        Menu::factory()->create(['location' => 'header_primary', 'is_active' => true]);
    }

    public function test_a_second_inactive_menu_for_the_same_location_is_allowed(): void
    {
        Menu::factory()->create(['location' => 'header_primary', 'is_active' => true]);

        $second = Menu::factory()->create(['location' => 'header_primary', 'is_active' => false]);

        $this->assertDatabaseHas('menus', ['id' => $second->id, 'is_active' => false]);
    }
}
