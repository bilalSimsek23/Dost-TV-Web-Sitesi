<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Program;
use App\Services\Menu\ProgramMegaMenuService;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CategoryAndMegaMenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_initial_categories_seeded_idempotently(): void
    {
        $this->seed(CategorySeeder::class);
        $countFirst = Category::count();

        $this->assertDatabaseHas('categories', ['slug' => 'tum-kategoriler']);
        $this->assertDatabaseHas('categories', ['slug' => 'tefekkur-dua']);
        $this->assertDatabaseHas('categories', ['slug' => 'kuran-i-kerim']);

        // Seeder tekrar çalıştığında duplicate oluşmamalı
        $this->seed(CategorySeeder::class);
        $this->assertSame($countFirst, Category::count());
    }

    public function test_all_categories_is_always_first_in_mega_menu(): void
    {
        $this->seed(CategorySeeder::class);

        $service = app(ProgramMegaMenuService::class);
        $data = $service->getMenuData();

        $first = $data['categories']->first();
        $this->assertNotNull($first);
        $this->assertSame('tum-kategoriler', $first->slug);
    }

    public function test_category_validation_depth_and_cycle_restrictions(): void
    {
        $cat1 = Category::create(['name' => 'Ana Kat 1', 'slug' => 'ana-1']);
        $cat2 = Category::create(['name' => 'Alt Kat 2', 'slug' => 'alt-2', 'parent_id' => $cat1->id]);
        $cat3 = Category::create(['name' => 'Alt Kat 3', 'slug' => 'alt-3', 'parent_id' => $cat2->id]);

        // Max 3 depth level reached; adding level 4 should throw exception
        $this->expectException(ValidationException::class);
        Category::create(['name' => 'Alt Kat 4', 'slug' => 'alt-4', 'parent_id' => $cat3->id]);
    }

    public function test_category_cannot_be_its_own_parent_or_create_cycle(): void
    {
        $cat1 = Category::create(['name' => 'Ana Kat 1', 'slug' => 'ana-1']);
        $cat2 = Category::create(['name' => 'Alt Kat 2', 'slug' => 'alt-2', 'parent_id' => $cat1->id]);

        $this->expectException(ValidationException::class);
        $cat1->update(['parent_id' => $cat2->id]);
    }

    public function test_inactive_or_hidden_categories_excluded_from_mega_menu(): void
    {
        $catActive = Category::create(['name' => 'Aktif Kat', 'slug' => 'aktif-kat', 'is_active' => true, 'show_in_menu' => true, 'show_in_mega_menu' => true]);
        $catInactive = Category::create(['name' => 'Pasif Kat', 'slug' => 'pasif-kat', 'is_active' => false, 'show_in_menu' => true, 'show_in_mega_menu' => true]);

        $service = app(ProgramMegaMenuService::class);
        ProgramMegaMenuService::forgetCache();

        $data = $service->getMenuData();
        $slugs = $data['categories']->pluck('slug')->toArray();

        $this->assertContains('aktif-kat', $slugs);
        $this->assertNotContains('pasif-kat', $slugs);
    }

    public function test_programs_associated_with_category(): void
    {
        $cat = Category::create(['name' => 'Test Kat', 'slug' => 'test-kat', 'is_active' => true, 'show_in_menu' => true, 'show_in_mega_menu' => true]);
        $prog = Program::create(['name' => 'Test Program', 'slug' => 'test-program', 'is_active' => true]);

        $cat->programs()->attach($prog);

        $this->assertTrue($cat->programs->contains('id', $prog->id));
    }
}
