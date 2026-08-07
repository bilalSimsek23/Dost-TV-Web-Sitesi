<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        Category::where('slug', Category::ALL_CATEGORIES_SLUG)->delete();

        $categories = [
            ['name' => 'Tefekkür / Dua', 'slug' => 'tefekkur-dua', 'sort_order' => 1],
            ['name' => 'Kur’an-ı Kerim', 'slug' => 'kuran-i-kerim', 'sort_order' => 2],
            ['name' => 'Tefsir', 'slug' => 'tefsir', 'sort_order' => 3],
            ['name' => 'Hadis / Siyer', 'slug' => 'hadis-siyer', 'sort_order' => 4],
            ['name' => 'Aile', 'slug' => 'aile', 'sort_order' => 5],
            ['name' => 'Sağlık', 'slug' => 'saglik', 'sort_order' => 6],
            ['name' => 'Yarışma', 'slug' => 'yarisma', 'sort_order' => 7],
            ['name' => 'Risale-i Nur', 'slug' => 'risale-i-nur', 'sort_order' => 8],
            ['name' => 'Soru / Cevap', 'slug' => 'soru-cevap', 'sort_order' => 9],
            ['name' => 'Arşiv', 'slug' => 'arsiv', 'sort_order' => 10],
        ];

        foreach ($categories as $cat) {
            Category::updateOrCreate(
                ['slug' => $cat['slug']],
                [
                    'name' => $cat['name'],
                    'sort_order' => $cat['sort_order'],
                    'is_active' => true,
                    'show_in_menu' => true,
                    'show_in_mega_menu' => true,
                ]
            );
        }
    }
}
