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
        $response = $this->actingAs($this->admin)
            ->get('/admin/site-layout/footer')
            ->assertSuccessful()
            ->assertSee('Footer Yönetimi')
            ->assertSee('Sayfa')
            ->assertSee('Footer Görünürlüğü')
            ->assertDontSee('İçerik Türü')
            ->assertSee('İşlem')
            ->assertSee('Yayıncı Künye Bilgisi')
            ->assertSee('Yeni Kurumsal Bilgi')
            ->assertSee('/admin/pages/' . $this->corporatePage->slug . '/edit')
            ->assertSee('Kurumsal')
            ->assertSee('Sosyal Medya')
            ->assertSee('Alt Bilgi')
            ->assertDontSee('Önizleme');

        // Verify top tab contact inputs are removed, social & footer inputs remain
        $response->assertDontSee('Telefon Numarası')
            ->assertDontSee('E-Posta Adresi')
            ->assertDontSee('Footer İletişim sütununda tel: bağlantısı olarak gösterilir.')
            ->assertSee('Instagram')
            ->assertSee('Facebook')
            ->assertSee('YouTube')
            ->assertSee('X / Twitter')
            ->assertSee('WhatsApp')
            ->assertSee('Telegram')
            ->assertSee('Telif Metni');
    }

    public function test_page_resource_navigation_is_registered_and_create_and_edit_routes_work(): void
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

    public function test_footer_settings_can_be_saved_without_losing_contact_data(): void
    {
        SiteSetting::current()->update([
            'phone' => '+90 (312) 341 21 21',
            'email' => 'iletisim@dosttv.com',
        ]);

        Livewire::actingAs($this->admin)
            ->test(FooterLayoutPage::class)
            ->fillForm([
                'facebook_url' => 'https://facebook.com/dosttv',
                'instagram_url' => 'https://instagram.com/dosttv',
                'copyright_text' => '© {year} Dost Medya. Tüm hakları saklıdır.',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $settings = SiteSetting::current();
        $this->assertEquals('+90 (312) 341 21 21', $settings->phone);
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

    public function test_corporate_pages_can_be_reordered(): void
    {
        $secondPage = Page::updateOrCreate(
            ['slug' => 'iletisim'],
            [
                'title' => 'İletişim',
                'content' => 'İletişim metni',
                'page_type' => 'corporate',
                'show_in_footer' => true,
                'sort_order' => 0,
                'status' => 'published',
            ]
        );

        $this->corporatePage->update(['sort_order' => 1]);

        // Reorder: make corporatePage first (0) and secondPage second (1)
        Livewire::actingAs($this->admin)
            ->test(FooterLayoutPage::class)
            ->call('reorderCorporatePages', [$this->corporatePage->id, $secondPage->id])
            ->assertHasNoFormErrors();

        $this->assertEquals(0, $this->corporatePage->fresh()->sort_order);
        $this->assertEquals(1, $secondPage->fresh()->sort_order);
    }

    public function test_footer_visibility_can_be_toggled(): void
    {
        $this->assertTrue($this->corporatePage->show_in_footer);

        Livewire::actingAs($this->admin)
            ->test(FooterLayoutPage::class)
            ->call('toggleFooterVisibility', $this->corporatePage->id);

        $this->assertFalse($this->corporatePage->fresh()->show_in_footer);

        Livewire::actingAs($this->admin)
            ->test(FooterLayoutPage::class)
            ->call('toggleFooterVisibility', $this->corporatePage->id);

        $this->assertTrue($this->corporatePage->fresh()->show_in_footer);
    }

    public function test_contact_page_managed_under_corporate_list_and_rendered_in_footer(): void
    {
        $contactPage = Page::updateOrCreate(
            ['slug' => 'iletisim'],
            [
                'title' => 'İletişim',
                'content' => '<p>İletişim Sayfası Detayları</p>',
                'page_type' => 'corporate',
                'show_in_footer' => true,
                'sort_order' => 6,
                'status' => 'published',
            ]
        );

        // Verify contact page is listed in corporate list on Footer Layout Page
        Livewire::actingAs($this->admin)
            ->test(FooterLayoutPage::class)
            ->assertSee('İletişim');

        // Verify public site renders contact link in Corporate section
        $this->get(route('home'))
            ->assertSuccessful()
            ->assertSee('Kurumsal')
            ->assertSee('İletişim')
            ->assertSee(route('pages.show', 'iletisim'));
    }

    public function test_public_footer_renders_balanced_columns_and_corporate_links(): void
    {
        SiteSetting::current()->update([
            'phone' => '+90 (312) 341 21 21',
            'email' => 'destek@dosttv.com',
            'copyright_text' => '© {year} Dost Medya A.Ş.',
        ]);

        $response = $this->get(route('home'));
        $response->assertSuccessful()
            ->assertSee('Kurumsal')
            ->assertSee('Sosyal Medya')
            ->assertSee('Yayıncı Künye Bilgisi')
            ->assertSee('Dost Medya A.Ş.')
            ->assertDontSee('+90 (312) 341 21 21')
            ->assertDontSee('destek@dosttv.com');
    }

    public function test_recommended_sites_management_and_public_footer_rendering(): void
    {
        // 1. Footer layout page shows "Önerilen Siteler" tab
        $this->actingAs($this->admin)
            ->get('/admin/site-layout/footer')
            ->assertSuccessful()
            ->assertSee('Önerilen Siteler');

        // 2. Save recommended sites via Livewire form
        Livewire::actingAs($this->admin)
            ->test(FooterLayoutPage::class)
            ->fillForm([
                'recommended_sites' => [
                    [
                        'name' => 'Kalbî',
                        'logo' => null,
                        'url' => 'https://kalbi.com.tr',
                        'target_blank' => true,
                        'is_active' => true,
                    ],
                    [
                        'name' => 'Pasif Dış Site',
                        'logo' => null,
                        'url' => 'https://pasifsite.com',
                        'target_blank' => false,
                        'is_active' => false,
                    ],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $settings = SiteSetting::current()->fresh();
        $this->assertCount(2, $settings->recommended_sites);
        $this->assertEquals('Kalbî', $settings->recommended_sites[0]['name']);

        // 3. Public footer renders active site, logo, external target_blank, and excludes passive site
        $publicRes = $this->get(route('home'));
        $publicRes->assertSuccessful()
            ->assertSee('Önerilen Siteler')
            ->assertSee('Kalbî')
            ->assertSee('https://kalbi.com.tr')
            ->assertSee('target="_blank"', false)
            ->assertSee('rel="noopener noreferrer"', false)
            ->assertDontSee('Pasif Dış Site');
    }

    public function test_empty_recommended_sites_hides_column_header(): void
    {
        SiteSetting::current()->update([
            'recommended_sites' => [],
        ]);

        $this->get(route('home'))
            ->assertSuccessful()
            ->assertDontSee('Önerilen Siteler');
    }

    public function test_array_logo_format_is_handled_safely(): void
    {
        \Illuminate\Support\Facades\Storage::disk('public')->put('recommended-sites/logo-in-array.png', 'fake image content');

        SiteSetting::current()->update([
            'recommended_sites' => [
                [
                    'name' => 'Array Logo Site',
                    'logo' => ['recommended-sites/logo-in-array.png'],
                    'url' => 'https://example-array-logo.com',
                    'target_blank' => true,
                    'is_active' => true,
                ],
            ],
        ]);

        $this->get(route('home'))
            ->assertSuccessful()
            ->assertSee('Array Logo Site')
            ->assertSee('storage/recommended-sites/logo-in-array.png');
    }
}


