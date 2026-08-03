<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Seeds the initial header_primary menu: Kurumsal, Hatim Cüz Al, Programlar,
 * Yayın Akışı, Canlı Yayın. None of this is hardcoded into Blade/routes —
 * it exists purely as editable data an admin can rearrange, hide, or delete.
 */
class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $menu = Menu::updateOrCreate(
            ['location' => 'header_primary'],
            ['name' => 'Ana Menü', 'description' => null, 'is_active' => true]
        );

        // Idempotent: wipe previously seeded items before recreating them.
        $menu->items()->delete();

        $order = 0;

        $kurumsal = MenuItem::create([
            'menu_id' => $menu->id,
            'title' => 'Kurumsal',
            'item_type' => 'dropdown',
            'sort_order' => $order++,
        ]);

        $corporatePageSlugs = [
            'dost-tv-yayin-ilkeleri',
            'neden-dost-tv',
            'yayinci-kunye-bilgisi',
            'dost-vakfi-hesap-numaralari',
            'iletisim',
        ];

        $childOrder = 0;

        foreach ($corporatePageSlugs as $slug) {
            $page = Page::where('slug', $slug)->first();

            if (! $page) {
                $this->command?->warn("MenuSeeder: '{$slug}' sayfası bulunamadı, Kurumsal alt menüsüne eklenmedi.");

                continue;
            }

            MenuItem::create([
                'menu_id' => $menu->id,
                'parent_id' => $kurumsal->id,
                'title' => $page->title,
                'item_type' => 'page',
                'linked_model_type' => 'page',
                'linked_model_id' => $page->id,
                'sort_order' => $childOrder++,
            ]);
        }

        MenuItem::create([
            'menu_id' => $menu->id,
            'title' => 'Hatim Cüz Al',
            'item_type' => 'url',
            'url' => '#',
            'sort_order' => $order++,
        ]);

        MenuItem::create([
            'menu_id' => $menu->id,
            'title' => 'Programlar',
            'item_type' => 'route',
            'route_name' => 'programs.index',
            'sort_order' => $order++,
        ]);

        MenuItem::create([
            'menu_id' => $menu->id,
            'title' => 'Yayın Akışı',
            'item_type' => 'route',
            'route_name' => 'schedule.index',
            'sort_order' => $order++,
        ]);

        $canliYayin = MenuItem::create([
            'menu_id' => $menu->id,
            'title' => 'Canlı Yayın',
            'item_type' => 'dropdown',
            'sort_order' => $order++,
        ]);

        MenuItem::create([
            'menu_id' => $menu->id,
            'parent_id' => $canliYayin->id,
            'title' => 'Canlı TV',
            'item_type' => 'live_tv',
            'sort_order' => 0,
        ]);

        MenuItem::create([
            'menu_id' => $menu->id,
            'parent_id' => $canliYayin->id,
            'title' => 'Canlı Radyo',
            'item_type' => 'live_radio',
            'sort_order' => 1,
        ]);
    }
}
