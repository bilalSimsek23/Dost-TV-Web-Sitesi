<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Program;
use App\Services\Menu\MenuResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuResolverTest extends TestCase
{
    use RefreshDatabase;

    private MenuResolver $resolver;

    private Menu $menu;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new MenuResolver;
        $this->menu = Menu::factory()->create();
    }

    public function test_resolves_page_item_to_pages_show_route(): void
    {
        $page = Page::create(['title' => 'İletişim', 'content' => 'Merhaba']);

        $item = MenuItem::create([
            'menu_id' => $this->menu->id,
            'title' => $page->title,
            'item_type' => 'page',
            'linked_model_type' => 'page',
            'linked_model_id' => $page->id,
            'sort_order' => 0,
        ]);

        $this->assertSame(route('pages.show', $page), $this->resolver->resolve($item));
    }

    public function test_resolves_program_item_to_programs_show_route(): void
    {
        $program = Program::factory()->create(['name' => 'Cuma Sohbetleri']);

        $item = MenuItem::create([
            'menu_id' => $this->menu->id,
            'title' => $program->name,
            'item_type' => 'program',
            'linked_model_type' => 'program',
            'linked_model_id' => $program->id,
            'sort_order' => 0,
        ]);

        $this->assertSame(route('programs.show', $program), $this->resolver->resolve($item));
    }

    public function test_resolves_category_item_to_filtered_programs_index(): void
    {
        $category = Category::create(['name' => 'Dini Sohbetler']);

        $item = MenuItem::create([
            'menu_id' => $this->menu->id,
            'title' => $category->name,
            'item_type' => 'category',
            'linked_model_type' => 'category',
            'linked_model_id' => $category->id,
            'sort_order' => 0,
        ]);

        $this->assertSame(
            route('programs.index', ['kategori' => $category->slug]),
            $this->resolver->resolve($item)
        );
    }

    public function test_resolves_named_route_item(): void
    {
        $item = MenuItem::create([
            'menu_id' => $this->menu->id,
            'title' => 'Programlar',
            'item_type' => 'route',
            'route_name' => 'programs.index',
            'sort_order' => 0,
        ]);

        $this->assertSame(route('programs.index'), $this->resolver->resolve($item));
    }

    public function test_resolves_url_item_as_is(): void
    {
        $item = MenuItem::create([
            'menu_id' => $this->menu->id,
            'title' => 'Hatim Cüz Al',
            'item_type' => 'url',
            'url' => '#',
            'sort_order' => 0,
        ]);

        $this->assertSame('#', $this->resolver->resolve($item));
    }

    public function test_resolves_live_tv_live_radio_and_schedule_shortcuts(): void
    {
        $tv = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Canlı TV', 'item_type' => 'live_tv', 'sort_order' => 0]);
        $radio = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Canlı Radyo', 'item_type' => 'live_radio', 'sort_order' => 1]);
        $schedule = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Yayın Akışı', 'item_type' => 'schedule', 'sort_order' => 2]);

        $this->assertSame(route('live.tv'), $this->resolver->resolve($tv));
        $this->assertSame(route('live.radio'), $this->resolver->resolve($radio));
        $this->assertSame(route('schedule.index'), $this->resolver->resolve($schedule));
    }

    public function test_dropdown_item_resolves_to_null(): void
    {
        $item = MenuItem::create([
            'menu_id' => $this->menu->id,
            'title' => 'Kurumsal',
            'item_type' => 'dropdown',
            'sort_order' => 0,
        ]);

        $this->assertNull($this->resolver->resolve($item));
    }

    public function test_page_item_with_missing_linked_model_resolves_to_null_without_throwing(): void
    {
        $item = MenuItem::create([
            'menu_id' => $this->menu->id,
            'title' => 'Silinmiş Sayfa',
            'item_type' => 'page',
            'linked_model_type' => 'page',
            'linked_model_id' => 9999,
            'sort_order' => 0,
        ]);

        $this->assertNull($this->resolver->resolve($item));
    }
}
