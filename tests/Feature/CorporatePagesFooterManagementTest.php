<?php

namespace Tests\Feature;

use App\Filament\Pages\SiteLayout\FooterLayoutPage;
use App\Filament\Resources\Pages\PageResource;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CorporatePagesFooterManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'email' => 'admin@dosttv.com',
            'role' => 'administrator',
        ]);

        // Seed system corporate pages if not existing
        $systemPages = [
            ['title' => 'Dost TV Yayın İlkeleri', 'slug' => 'dost-tv-yayin-ilkeleri', 'page_type' => 'corporate', 'template' => 'corporate', 'show_in_footer' => true],
            ['title' => 'Yayıncı Künye Bilgisi', 'slug' => 'yayinci-kunye-bilgisi', 'page_type' => 'corporate', 'template' => 'corporate', 'show_in_footer' => true],
            ['title' => 'Neden Dost TV', 'slug' => 'neden-dost-tv', 'page_type' => 'corporate', 'template' => 'corporate', 'show_in_footer' => true],
            ['title' => 'Dost Vakfı Hesap Numaraları', 'slug' => 'dost-vakfi-hesap-numaralari', 'page_type' => 'corporate', 'template' => 'corporate', 'show_in_footer' => true],
            ['title' => 'Kişisel Verilerin Korunması ve Gizlilik Politikası', 'slug' => 'kisisel-verilerin-korunmasi-ve-gizlilik-politikasi', 'page_type' => 'corporate', 'template' => 'corporate', 'show_in_footer' => true],
            ['title' => 'İletişim', 'slug' => 'iletisim', 'page_type' => 'corporate', 'template' => 'contact', 'show_in_footer' => true],
        ];

        foreach ($systemPages as $idx => $p) {
            Page::firstOrCreate(
                ['slug' => $p['slug']],
                array_merge($p, ['sort_order' => $idx, 'status' => 'published'])
            );
        }
    }

    public function test_pages_resource_navigation_is_hidden_from_sidebar(): void
    {
        $this->assertFalse(
            PageResource::shouldRegisterNavigation(),
            'Sayfalar girişi sol menüde (sidebar) görünmemelidir.'
        );
    }

    public function test_page_resource_edit_and_create_routes_remain_accessible(): void
    {
        $page = Page::where('slug', 'iletisim')->first();
        $this->assertNotNull($page);

        // Edit route
        $editUrl = PageResource::getUrl('edit', ['record' => $page]);
        $this->actingAs($this->admin)
            ->get($editUrl)
            ->assertSuccessful()
            ->assertSee('İletişim');

        // Create route
        $createUrl = PageResource::getUrl('create');
        $this->actingAs($this->admin)
            ->get($createUrl)
            ->assertSuccessful();
    }

    public function test_footer_layout_page_is_main_corporate_management_hub(): void
    {
        // 1. Access Footer Layout Page
        $this->actingAs($this->admin)
            ->get(FooterLayoutPage::getUrl())
            ->assertSuccessful()
            ->assertSee('Footer Yönetimi')
            ->assertSee('Dost TV Yayın İlkeleri')
            ->assertSee('Yayıncı Künye Bilgisi')
            ->assertSee('İletişim');

        // 2. Toggle footer visibility
        $page = Page::where('slug', 'neden-dost-tv')->first();
        $this->assertTrue($page->show_in_footer);

        Livewire::actingAs($this->admin)
            ->test(FooterLayoutPage::class)
            ->call('toggleFooterVisibility', $page->id)
            ->assertNotified();

        $this->assertFalse($page->fresh()->show_in_footer);

        // 3. Reorder corporate pages
        $pageIds = Page::where('page_type', 'corporate')->pluck('id')->toArray();
        $reversedIds = array_reverse($pageIds);

        Livewire::actingAs($this->admin)
            ->test(FooterLayoutPage::class)
            ->call('reorderCorporatePages', $reversedIds)
            ->assertNotified();

        $this->assertEquals(0, Page::find($reversedIds[0])->sort_order);
    }

    public function test_system_corporate_pages_cannot_be_deleted(): void
    {
        $page = Page::where('slug', 'iletisim')->first();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Sistem kurumsal sayfası ('İletişim') silinemez.");

        $page->delete();
    }

    public function test_public_corporate_pages_render_successfully(): void
    {
        $this->get('/iletisim')->assertSuccessful()->assertSee('İletişim');
        $this->get('/neden-dost-tv')->assertSuccessful()->assertSee('Neden Dost TV');
        $this->get('/dost-tv-yayin-ilkeleri')->assertSuccessful()->assertSee('Dost TV Yayın İlkeleri');
    }
}
