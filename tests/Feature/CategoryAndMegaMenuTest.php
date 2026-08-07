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

        $this->assertDatabaseMissing('categories', ['slug' => 'tum-kategoriler']);
        $this->assertDatabaseHas('categories', ['slug' => 'tefekkur-dua']);
        $this->assertDatabaseHas('categories', ['slug' => 'kuran-i-kerim']);

        // Seeder tekrar çalıştığında duplicate oluşmamalı
        $this->seed(CategorySeeder::class);
        $this->assertSame($countFirst, Category::count());
    }

    public function test_all_categories_is_always_first_virtual_entry_in_mega_menu(): void
    {
        $this->seed(CategorySeeder::class);

        $service = app(ProgramMegaMenuService::class);
        ProgramMegaMenuService::forgetCache();

        $data = $service->getMenuData();

        $first = $data['categories']->first();
        $this->assertNotNull($first);
        $this->assertSame('tum-kategoriler', $first->slug);
        $this->assertSame('Tüm Programlar', $first->name);
        $this->assertDatabaseMissing('categories', ['slug' => 'tum-kategoriler']);
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

    public function test_program_without_categories_automatically_appears_in_all_programs(): void
    {
        $prog = Program::create([
            'name' => 'Kategorisiz Program',
            'slug' => 'kategorisiz-program',
            'status' => 'active',
            'show_on_public' => true,
        ]);

        ProgramMegaMenuService::forgetCache();
        $service = app(ProgramMegaMenuService::class);
        $data = $service->getMenuData();

        $allDetails = $data['category_details']['cat-0'] ?? null;
        $this->assertNotNull($allDetails);

        $titles = [];
        foreach ($allDetails['columns'] as $col) {
            foreach ($col as $item) {
                $titles[] = $item['slug'];
            }
        }

        $this->assertContains('kategorisiz-program', $titles);
    }

    public function test_program_appears_under_its_attached_category_and_in_all_programs(): void
    {
        $cat = Category::create(['name' => 'Test Kat', 'slug' => 'test-kat', 'is_active' => true, 'show_in_menu' => true, 'show_in_mega_menu' => true]);
        $prog = Program::create([
            'name' => 'Kategorili Program',
            'slug' => 'kategorili-program',
            'status' => 'active',
            'show_on_public' => true,
        ]);

        $cat->programs()->attach($prog);

        ProgramMegaMenuService::forgetCache();
        $service = app(ProgramMegaMenuService::class);
        $data = $service->getMenuData();

        $catDetails = $data['category_details']['cat-' . $cat->id] ?? null;
        $this->assertNotNull($catDetails);

        $titles = [];
        foreach ($catDetails['columns'] as $col) {
            foreach ($col as $item) {
                $titles[] = $item['slug'];
            }
        }

        $this->assertContains('kategorili-program', $titles);
    }

    public function test_passive_program_excluded_from_all_mega_menu_lists(): void
    {
        $cat = Category::create(['name' => 'Test Kat', 'slug' => 'test-kat', 'is_active' => true, 'show_in_menu' => true, 'show_in_mega_menu' => true]);
        $prog = Program::create([
            'name' => 'Pasif Program',
            'slug' => 'pasif-program',
            'status' => 'archived',
            'show_on_public' => false,
        ]);
        $cat->programs()->attach($prog);

        ProgramMegaMenuService::forgetCache();
        $service = app(ProgramMegaMenuService::class);
        $data = $service->getMenuData();

        $allDetails = $data['category_details']['cat-0'] ?? null;
        $titlesAll = [];
        foreach ($allDetails['columns'] as $col) {
            foreach ($col as $item) {
                $titlesAll[] = $item['slug'];
            }
        }
        $this->assertNotContains('pasif-program', $titlesAll);

        $catDetails = $data['category_details']['cat-' . $cat->id] ?? null;
        $titlesCat = [];
        foreach ($catDetails['columns'] as $col) {
            foreach ($col as $item) {
                $titlesCat[] = $item['slug'];
            }
        }
        $this->assertNotContains('pasif-program', $titlesCat);
    }

    public function test_no_pivot_or_database_record_created_for_tum_kategoriler(): void
    {
        $this->assertDatabaseMissing('categories', ['slug' => 'tum-kategoriler']);

        $countPivot = \Illuminate\Support\Facades\DB::table('category_program')
            ->whereIn('category_id', function ($q) {
                $q->select('id')->from('categories')->where('slug', 'tum-kategoriler');
            })->count();

        $this->assertSame(0, $countPivot);
    }

    public function test_programs_are_sorted_in_turkish_alphabetical_order(): void
    {
        $names = ['Şehir ve Medeniyet', 'Çocuk Kuşağı', 'İslam Tarihi', 'Hadis Dersleri', 'Özel Yayın', 'Dini Sohbetler'];
        foreach ($names as $name) {
            Program::create([
                'name' => $name,
                'slug' => \Illuminate\Support\Str::slug($name),
                'status' => 'active',
                'show_on_public' => true,
            ]);
        }

        ProgramMegaMenuService::forgetCache();
        $service = app(ProgramMegaMenuService::class);
        $data = $service->getMenuData();

        $allDetails = $data['category_details']['cat-0'];
        $titles = [];
        foreach ($allDetails['columns'] as $col) {
            foreach ($col as $item) {
                $titles[] = $item['title'];
            }
        }

        $expectedOrder = ['Çocuk Kuşağı', 'Dini Sohbetler', 'Hadis Dersleri', 'İslam Tarihi', 'Özel Yayın', 'Şehir ve Medeniyet'];
        $this->assertSame($expectedOrder, array_values(array_intersect($expectedOrder, $titles)));
    }

    public function test_column_counts_and_balanced_distributions(): void
    {
        // 8 programs -> 1 column
        $this->assertSame(1, ProgramMegaMenuService::determineColumnCount(8));

        // 20 programs -> 2 columns
        $this->assertSame(2, ProgramMegaMenuService::determineColumnCount(20));

        // 40 programs -> 3 columns
        $this->assertSame(3, ProgramMegaMenuService::determineColumnCount(40));

        // 50 programs -> 4 columns (13, 13, 12, 12)
        $this->assertSame(4, ProgramMegaMenuService::determineColumnCount(50));

        $dummyItems = range(1, 50);
        $distribution = ProgramMegaMenuService::distributeIntoBalancedColumns($dummyItems);

        $this->assertSame(4, $distribution['column_count']);
        $this->assertSame(13, count($distribution['columns'][0]));
        $this->assertSame(13, count($distribution['columns'][1]));
        $this->assertSame(12, count($distribution['columns'][2]));
        $this->assertSame(12, count($distribution['columns'][3]));
    }

    public function test_no_duplicate_programs_in_any_list(): void
    {
        $prog = Program::create([
            'name' => 'Tekil Program',
            'slug' => 'tekil-program',
            'status' => 'active',
            'show_on_public' => true,
        ]);

        $cat1 = Category::create(['name' => 'Kat 1', 'slug' => 'kat-1', 'is_active' => true, 'show_in_menu' => true, 'show_in_mega_menu' => true]);
        $cat1->programs()->attach($prog);

        ProgramMegaMenuService::forgetCache();
        $service = app(ProgramMegaMenuService::class);
        $data = $service->getMenuData();

        $allDetails = $data['category_details']['cat-0'];
        $ids = [];
        foreach ($allDetails['columns'] as $col) {
            foreach ($col as $item) {
                $ids[] = $item['id'];
            }
        }

        $this->assertSame(count($ids), count(array_unique($ids)));
    }
}
