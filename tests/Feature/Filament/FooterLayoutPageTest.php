<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\SiteLayout\FooterLayoutPage;
use App\Filament\Resources\Pages\PageResource;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FooterLayoutPageTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Page $corporatePage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'administrator']);

        $this->corporatePage = Page::updateOrCreate(
            ['slug' => 'yayinci-kunye-bilgisi'],
            [
                'title' => 'Yayıncı Künye Bilgisi',
                'content' => 'Künye detayları',
                'page_type' => 'corporate',
                'show_in_footer' => true,
                'status' => 'published',
            ]
        );
    }

    public function test_authorized_user_can_access_footer_layout_page_and_see_corporate_list(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/site-layout/footer')
            ->assertSuccessful()
            ->assertSee('Footer Yönetimi')
            ->assertSee('Sayfa')
            ->assertSee('İçerik Türü')
            ->assertSee('İşlem')
            ->assertSee('Yayıncı Künye Bilgisi')
            ->assertSee('Künye Bilgisi')
            ->assertSee('+ Yeni Kurumsal Bilgi')
            ->assertSee('/admin/pages/' . $this->corporatePage->slug . '/edit');
    }

    public function test_page_resource_navigation_is_hidden_but_create_and_edit_routes_work(): void
    {
        $this->assertFalse(PageResource::shouldRegisterNavigation());

        $this->actingAs($this->admin)
            ->get('/admin/pages/create')
            ->assertSuccessful();

        $this->actingAs($this->admin)
            ->get('/admin/pages/' . $this->corporatePage->slug . '/edit')
            ->assertSuccessful()
            ->assertSee('yayinci-kunye-bilgisi');
    }

    public function test_footer_layout_page_search_filtering(): void
    {
        Page::create([
            'title' => 'Özel Gizlilik Politikası Ek Metni',
            'slug' => 'ozel-gizlilik-politikasi-ek-metni',
            'content' => 'Gizlilik detayları',
            'page_type' => 'corporate',
            'show_in_footer' => false,
            'status' => 'published',
        ]);

        Livewire::actingAs($this->admin)
            ->test(FooterLayoutPage::class)
            ->set('search', 'Künye')
            ->assertSee('Yayıncı Künye Bilgisi')
            ->assertDontSee('Özel Gizlilik Politikası Ek Metni');
    }

    public function test_footer_settings_can_be_saved(): void
    {
        Livewire::actingAs($this->admin)
            ->test(FooterLayoutPage::class)
            ->fillForm([
                'phone' => '+90 (312) 111 22 33',
                'email' => 'iletisim@dosttv.com',
                'facebook_url' => 'https://facebook.com/dosttv',
                'instagram_url' => 'https://instagram.com/dosttv',
                'copyright_text' => '© {year} Dost Medya. Tüm hakları saklıdır.',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $settings = SiteSetting::current();
        $this->assertEquals('+90 (312) 111 22 33', $settings->phone);
        $this->assertEquals('iletisim@dosttv.com', $settings->email);
        $this->assertEquals('https://facebook.com/dosttv', $settings->facebook_url);
        $this->assertEquals('https://instagram.com/dosttv', $settings->instagram_url);
        $this->assertEquals('© {year} Dost Medya. Tüm hakları saklıdır.', $settings->copyright_text);
    }

    public function test_invalid_social_media_url_is_rejected(): void
    {
        Livewire::actingAs($this->admin)
            ->test(FooterLayoutPage::class)
            ->fillForm([
                'facebook_url' => 'invalid-url-string',
            ])
            ->call('save')
            ->assertHasFormErrors(['facebook_url' => 'url']);
    }

    public function test_public_footer_renders_3_columns_and_corporate_links(): void
    {
        SiteSetting::current()->update([
            'phone' => '+90 (312) 341 21 21',
            'email' => 'destek@dosttv.com',
            'copyright_text' => '© {year} Dost Medya A.Ş.',
        ]);

        $this->get(route('home'))
            ->assertSuccessful()
            ->assertSee('Kurumsal')
            ->assertSee('İletişim')
            ->assertSee('Sosyal Medya')
            ->assertSee('Yayıncı Künye Bilgisi')
            ->assertSee('+90 (312) 341 21 21')
            ->assertSee('destek@dosttv.com')
            ->assertSee('Dost Medya A.Ş.');
    }
}
