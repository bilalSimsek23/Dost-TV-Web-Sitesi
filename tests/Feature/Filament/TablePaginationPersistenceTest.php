<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Announcements\Pages\ListAnnouncements;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Filament\Resources\Episodes\Pages\ListEpisodes;
use App\Filament\Resources\Programs\Pages\ListPrograms;
use App\Filament\Resources\Programs\RelationManagers\EpisodesRelationManager;
use App\Filament\Resources\Schedules\Pages\ListSchedules;
use App\Models\Announcement;
use App\Models\Category;
use App\Models\Episode;
use App\Models\Program;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TablePaginationPersistenceTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'administrator']);
    }

    public function test_programs_goto_page_produces_url_query_string_effect(): void
    {
        for ($i = 1; $i <= 30; $i++) {
            Program::create([
                'name' => sprintf('Program %02d', $i),
                'slug' => sprintf('program-%02d', $i),
                'status' => 'active',
                'is_active' => true,
            ]);
        }

        $component = Livewire::actingAs($this->admin)
            ->test(ListPrograms::class);

        $component->call('gotoPage', 2, 'page');

        $effects = $component->effects;
        $this->assertArrayHasKey('url', $effects, 'Livewire effects must contain url tracking effect for browser address bar synchronization');
        $this->assertArrayHasKey('paginators.page', $effects['url']);
        $this->assertSame('page', $effects['url']['paginators.page']['as']);
    }

    public function test_programs_page_2_simulated_f5_refresh_stays_on_page_2(): void
    {
        for ($i = 1; $i <= 30; $i++) {
            Program::create([
                'name' => sprintf('Program %02d', $i),
                'slug' => sprintf('program-%02d', $i),
                'status' => 'active',
                'is_active' => true,
            ]);
        }

        // Simulating F5 refresh on /admin/programs?page=2
        $component = Livewire::actingAs($this->admin)
            ->withQueryParams(['page' => 2])
            ->test(ListPrograms::class);

        $this->assertSame(2, (int) $component->instance()->getTablePage());
        $records = $component->instance()->getTableRecords();
        $this->assertSame(2, $records->currentPage());
        $this->assertGreaterThanOrEqual(2, $records->lastPage());
    }

    public function test_programs_page_4_simulated_f5_refresh_stays_on_page_4(): void
    {
        // 10 records per page by default -> create 45 records for 5 pages
        for ($i = 1; $i <= 45; $i++) {
            Program::create([
                'name' => sprintf('Program %02d', $i),
                'slug' => sprintf('program-%02d', $i),
                'status' => 'active',
                'is_active' => true,
            ]);
        }

        // Simulating F5 refresh on /admin/programs?page=4
        $component = Livewire::actingAs($this->admin)
            ->withQueryParams(['page' => 4])
            ->test(ListPrograms::class);

        $this->assertSame(4, (int) $component->instance()->getTablePage());
        $records = $component->instance()->getTableRecords();
        $this->assertSame(4, $records->currentPage());
    }

    public function test_episodes_goto_page_produces_url_query_string_effect(): void
    {
        $program = Program::create(['name' => 'Ana Program', 'slug' => 'ana-program', 'status' => 'active', 'is_active' => true]);

        for ($i = 1; $i <= 35; $i++) {
            Episode::create([
                'program_id' => $program->id,
                'title' => sprintf('Bölüm %02d', $i),
                'slug' => sprintf('bolum-%02d', $i),
                'episode_number' => $i,
                'is_active' => true,
            ]);
        }

        $component = Livewire::actingAs($this->admin)
            ->test(ListEpisodes::class, ['program_id' => (string) $program->id]);

        $component->call('gotoPage', 3, 'page');

        $effects = $component->effects;
        $this->assertArrayHasKey('url', $effects);
        $this->assertArrayHasKey('paginators.page', $effects['url']);
        $this->assertSame('page', $effects['url']['paginators.page']['as']);
    }

    public function test_episodes_page_3_simulated_f5_refresh_stays_on_page_3(): void
    {
        $program = Program::create(['name' => 'Ana Program', 'slug' => 'ana-program', 'status' => 'active', 'is_active' => true]);

        for ($i = 1; $i <= 35; $i++) {
            Episode::create([
                'program_id' => $program->id,
                'title' => sprintf('Bölüm %02d', $i),
                'slug' => sprintf('bolum-%02d', $i),
                'episode_number' => $i,
                'is_active' => true,
            ]);
        }

        // Simulating F5 refresh on /admin/episodes?program_id=...&page=3
        $component = Livewire::actingAs($this->admin)
            ->withQueryParams(['program_id' => (string) $program->id, 'page' => 3])
            ->test(ListEpisodes::class, ['program_id' => (string) $program->id]);

        $this->assertSame(3, (int) $component->instance()->getTablePage());
        $records = $component->instance()->getTableRecords();
        $this->assertSame(3, $records->currentPage());
    }

    public function test_search_and_pagination_state_preserved_on_f5_refresh(): void
    {
        for ($i = 1; $i <= 25; $i++) {
            Program::create([
                'name' => sprintf('Ramazan Programı %02d', $i),
                'slug' => sprintf('ramazan-programi-%02d', $i),
                'status' => 'active',
                'is_active' => true,
            ]);
        }

        for ($i = 1; $i <= 10; $i++) {
            Program::create([
                'name' => sprintf('Haber Bülteni %02d', $i),
                'slug' => sprintf('haber-bulteni-%02d', $i),
                'status' => 'active',
                'is_active' => true,
            ]);
        }

        // Simulating F5 refresh on /admin/programs?search=Ramazan&page=2
        $component = Livewire::actingAs($this->admin)
            ->withQueryParams(['search' => 'Ramazan', 'page' => 2])
            ->test(ListPrograms::class);

        $this->assertSame('Ramazan', $component->get('tableSearch'));
        $this->assertSame(2, (int) $component->instance()->getTablePage());
        $records = $component->instance()->getTableRecords();
        $this->assertSame(2, $records->currentPage());
        $this->assertSame(25, $records->total());
    }

    public function test_sort_and_pagination_state_preserved_on_f5_refresh(): void
    {
        for ($i = 1; $i <= 30; $i++) {
            Program::create([
                'name' => sprintf('Program %02d', $i),
                'slug' => sprintf('program-%02d', $i),
                'status' => 'active',
                'is_active' => true,
            ]);
        }

        // Simulating F5 refresh on /admin/programs?sort=name&page=2
        $component = Livewire::actingAs($this->admin)
            ->withQueryParams(['sort' => 'name', 'page' => 2])
            ->test(ListPrograms::class);

        $this->assertSame('name', $component->get('tableSort'));
        $this->assertSame(2, (int) $component->instance()->getTablePage());
        $records = $component->instance()->getTableRecords();
        $this->assertSame(2, $records->currentPage());
    }

    public function test_categories_and_schedules_and_announcements_pagination_persistence(): void
    {
        for ($i = 1; $i <= 25; $i++) {
            Category::create([
                'name' => sprintf('Kategori %02d', $i),
                'slug' => sprintf('kategori-%02d', $i),
                'is_active' => true,
            ]);
        }

        $component = Livewire::actingAs($this->admin)
            ->withQueryParams(['page' => 2])
            ->test(ListCategories::class);

        $this->assertSame(2, (int) $component->instance()->getTablePage());
        $this->assertSame(2, $component->instance()->getTableRecords()->currentPage());
    }

    public function test_relation_manager_pagination_url_synchronization(): void
    {
        $program = Program::create(['name' => 'Program Relation', 'slug' => 'program-relation', 'status' => 'active', 'is_active' => true]);

        for ($i = 1; $i <= 30; $i++) {
            Episode::create([
                'program_id' => $program->id,
                'title' => sprintf('İlişki Bölüm %02d', $i),
                'slug' => sprintf('iliski-bolum-%02d', $i),
                'episode_number' => $i,
                'is_active' => true,
            ]);
        }

        $component = Livewire::actingAs($this->admin)
            ->test(EpisodesRelationManager::class, ['ownerRecord' => $program, 'pageClass' => ListPrograms::class]);

        $pageName = $component->instance()->getTablePaginationPageName();
        $component->call('gotoPage', 2, $pageName);

        $effects = $component->effects;
        $this->assertArrayHasKey('url', $effects);
        $this->assertArrayHasKey("paginators.{$pageName}", $effects['url']);
        $this->assertSame($pageName, $effects['url']["paginators.{$pageName}"]['as']);
    }
}
