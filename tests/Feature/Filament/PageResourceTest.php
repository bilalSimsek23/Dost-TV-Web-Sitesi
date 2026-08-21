<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Pages\PageResource;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Page $page;

    protected function setUp(): void
    {
        parent::setUp();

        SiteSetting::create([
            'site_name' => 'Dost TV',
            'phone' => '+90 (312) 341 21 21',
            'email' => 'iletisim@dosttv.com',
            'copyright_text' => '© {year} Dost TV. Tüm hakları saklıdır.',
        ]);

        $this->admin = User::factory()->create([
            'email' => 'admin@dosttv.com',
            'role' => 'administrator',
        ]);

        $this->page = Page::create([
            'title' => 'Yayıncı Künye Bilgisi',
            'slug' => 'yayinci-kunye-bilgisi',
            'content' => '<p>Dost TV Künye İçeriği</p>',
            'show_in_footer' => true,
            'sort_order' => 1,
        ]);
    }

    public function test_authorized_user_can_access_pages_list_and_see_simplified_columns(): void
    {
        $response = $this->actingAs($this->admin)
            ->get('/admin/pages')
            ->assertSuccessful()
            ->assertSee('Başlık')
            ->assertDontSee('İçerik Türü')
            ->assertDontSee('Durum')
            ->assertDontSee('Sitedeki Konum')
            ->assertSee('Yayıncı Künye Bilgisi')
            ->assertSee('Yeni Kurumsal Bilgi')
            ->assertSee('Düzenle')
            ->assertSee('Önizle');
    }

    public function test_page_form_contains_only_content_and_seo_tabs_and_no_location_tab(): void
    {
        $createResponse = $this->actingAs($this->admin)
            ->get('/admin/pages/create')
            ->assertSuccessful()
            ->assertSee('Genel Bilgiler')
            ->assertSee('İçerik')
            ->assertSee('SEO')
            ->assertSee('Önizleme')
            ->assertDontSee('Sitedeki Konum')
            ->assertDontSee('Menüde Göster')
            ->assertDontSee('Header\'da Göster')
            ->assertDontSee('Footer\'da Göster')
            ->assertDontSee('Menü Grubu')
            ->assertDontSee('Menü Konumu')
            ->assertDontSee('Üst Sayfa')
            ->assertDontSee('Bağlı Menü Durumu')
            ->assertDontSee('İçerik Türü')
            ->assertDontSee('Yayın Tarihi');

        $newPage = Page::create([
            'title' => 'Neden Dost TV',
            'content' => '<p>Neden Dost TV açıklaması</p>',
        ]);

        $this->assertEquals('corporate', $newPage->page_type);
        $this->assertEquals('published', $newPage->status);
        $this->assertEquals('neden-dost-tv', $newPage->slug);

        $editResponse = $this->actingAs($this->admin)
            ->get('/admin/pages/' . $this->page->slug . '/edit')
            ->assertSuccessful()
            ->assertSee('yayinci-kunye-bilgisi')
            ->assertDontSee('Sitedeki Konum')
            ->assertDontSee('Menüde Göster')
            ->assertDontSee('Header\'da Göster');
    }

    public function test_all_seven_default_corporate_pages_render_without_workflow_dependencies(): void
    {
        $slugs = [
            'yayinci-kunye-bilgisi' => 'Yayıncı Künye Bilgisi',
            'dost-tv-yayin-ilkeleri' => 'Dost TV Yayın İlkeleri',
            'neden-dost-tv' => 'Neden Dost TV',
            'dost-vakfi-hesap-numaralari' => 'Dost Vakfı Hesap Numaraları',
            'iletisim' => 'İletişim',
            'kisisel-verilerin-korunmasi-ve-gizlilik-politikasi' => 'Kişisel Verilerin Korunması ve Gizlilik Politikası',
            'uyelik-kosullari' => 'Üyelik Koşulları',
        ];

        foreach ($slugs as $slug => $title) {
            Page::updateOrCreate(
                ['slug' => $slug],
                [
                    'title' => $title,
                    'content' => "<p>{$title} içeriği</p>",
                    'show_in_footer' => true,
                ]
            );

            $this->get("/{$slug}")
                ->assertSuccessful()
                ->assertSee($title);
        }
    }

    public function test_header_linked_pages_work_correctly(): void
    {
        $menu = Menu::create(['name' => 'Header Primary', 'location' => 'header_primary', 'is_active' => true]);
        $item = MenuItem::create([
            'menu_id' => $menu->id,
            'title' => 'Künye',
            'item_type' => 'page',
            'linked_model_type' => 'page',
            'linked_model_id' => $this->page->id,
            'url' => '/yayinci-kunye-bilgisi',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('menu_items', [
            'id' => $item->id,
            'linked_model_id' => $this->page->id,
        ]);
    }
}
