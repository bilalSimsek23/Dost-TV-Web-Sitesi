<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Pages\Pages\ListPages;
use App\Filament\Resources\Pages\PageResource;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CorporateInformationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'administrator']);
    }

    public function test_authorized_user_can_access_corporate_information_page(): void
    {
        $this->actingAs($this->admin)
            ->get(PageResource::getUrl())
            ->assertSuccessful();
    }

    public function test_only_corporate_pages_are_listed_by_default(): void
    {
        $corporatePage = Page::create([
            'title' => 'Özel Kurumsal Metin',
            'slug' => 'ozel-kurumsal-metin',
            'page_type' => 'corporate',
            'status' => 'published',
        ]);

        $otherPage = Page::create([
            'title' => 'Özel Demo Metin',
            'slug' => 'ozel-demo-metin',
            'page_type' => null,
            'status' => 'published',
        ]);

        Livewire::actingAs($this->admin)
            ->test(ListPages::class)
            ->assertCanSeeTableRecords([$corporatePage])
            ->assertCanNotSeeTableRecords([$otherPage]);

        $this->assertDatabaseHas('pages', ['id' => $otherPage->id]);
    }

    public function test_verified_7_corporate_pages_and_linked_menu_items_are_preserved(): void
    {
        $corporatePage = Page::create([
            'title' => 'Yayıncı Künye Bilgisi',
            'slug' => 'yayinci-kunye-bilgisi',
            'page_type' => 'corporate',
            'status' => 'published',
        ]);

        $menu = \App\Models\Menu::create(['name' => 'Header Menu', 'location' => 'header_primary', 'is_active' => true]);

        $menuItem = MenuItem::create([
            'menu_id' => $menu->id,
            'title' => 'Yayıncı Künye Bilgisi',
            'item_type' => 'page',
            'linked_model_type' => 'page',
            'linked_model_id' => $corporatePage->id,
            'sort_order' => 1,
        ]);

        $this->assertDatabaseHas('pages', ['id' => $corporatePage->id, 'page_type' => 'corporate']);
        $this->assertDatabaseHas('menu_items', ['id' => $menuItem->id, 'linked_model_id' => $corporatePage->id]);
    }

    public function test_public_corporate_pages_return_status_200(): void
    {
        $page = Page::create([
            'title' => 'Dost TV Yayın İlkeleri',
            'slug' => 'dost-tv-yayin-ilkeleri',
            'content' => '<p>Yayın ilkeleri metni</p>',
            'page_type' => 'corporate',
            'status' => 'published',
        ]);

        $this->get('/dost-tv-yayin-ilkeleri')
            ->assertStatus(200)
            ->assertSee('Dost TV Yayın İlkeleri');
    }
}
