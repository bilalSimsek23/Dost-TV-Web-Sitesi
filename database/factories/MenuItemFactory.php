<?php

namespace Database\Factories;

use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MenuItem>
 */
class MenuItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'menu_id' => Menu::factory(),
            'parent_id' => null,
            'title' => ucfirst($this->faker->words(2, true)),
            'item_type' => 'url',
            'url' => '/'.$this->faker->slug(),
            'is_active' => true,
            'show_on_desktop' => true,
            'show_on_mobile' => true,
            'sort_order' => 0,
        ];
    }
}
