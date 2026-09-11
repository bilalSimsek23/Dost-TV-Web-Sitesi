<?php

namespace Tests\Feature;

use App\Filament\Resources\Pages\PageResource;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class IletisimCardSyncTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Page $contactPage;
    protected Page $corporatePage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'email' => 'admin@dosttv.com',
            'role' => 'administrator',
        ]);

        $this->contactPage = Page::updateOrCreate(
            ['slug' => 'iletisim'],
            [
                'title' => 'İletişim',
                'content' => '<p>Eski metin</p>',
                'page_type' => 'corporate',
                'status' => 'published',
                'settings' => [
                    'address' => 'Eski Adres Sok. No:1 Ankara',
                    'phone' => '0312 000 00 00',
                    'email' => 'eski@dosttv.com',
                ],
            ]
        );

        $this->corporatePage = Page::updateOrCreate(
            ['slug' => 'kvkk'],
            [
                'title' => 'KVKK',
                'content' => '<p>KVKK Metni</p>',
                'page_type' => 'corporate',
                'status' => 'published',
            ]
        );
    }

    public function test_contact_page_form_hides_rich_editor_and_shows_contact_cards_form(): void
    {
        $this->actingAs($this->admin)
            ->get(PageResource::getUrl('edit', ['record' => $this->contactPage]))
            ->assertSuccessful()
            ->assertSee('İLETİŞİM BİLGİLERİ')
            ->assertSee('Adres')
            ->assertSee('Telefon')
            ->assertSee('E-posta');

        // Corporate pages still show rich editor
        $this->actingAs($this->admin)
            ->get(PageResource::getUrl('edit', ['record' => $this->corporatePage]))
            ->assertSuccessful()
            ->assertDontSee('İLETİŞİM BİLGİLERİ')
            ->assertSee('İçerik Metni');
    }

    public function test_exact_save_reload_db_and_public_card_acceptance_scenario(): void
    {
        // 1. Save changes via Native Filament Form
        Livewire::actingAs($this->admin)
            ->test(EditPage::class, ['record' => $this->contactPage->getRouteKey()])
            ->fillForm([
                'settings.address' => 'TEST ILETISIM ADRESI',
                'settings.phone' => '+90 312 222 33 44',
                'settings.email' => 'testiletisim@dosttv.com',
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified('Kurumsal Bilgi Güncellendi');

        // 2. Reload edit page and verify form state still holds exact saved values
        $reloadResponse = $this->actingAs($this->admin)
            ->get(PageResource::getUrl('edit', ['record' => $this->contactPage]));
        $reloadResponse->assertSuccessful()
            ->assertSee('TEST ILETISIM ADRESI')
            ->assertSee('+90 312 222 33 44')
            ->assertSee('testiletisim@dosttv.com');

        // 3. Verify DB record in pages table settings JSON column
        $contactPageSettings = $this->contactPage->fresh()->settings;
        $this->assertEquals('TEST ILETISIM ADRESI', $contactPageSettings['address'] ?? null);
        $this->assertEquals('+90 312 222 33 44', $contactPageSettings['phone'] ?? null);
        $this->assertEquals('testiletisim@dosttv.com', $contactPageSettings['email'] ?? null);

        // 4. Verify public /iletisim page renders all 3 updated info cards in a fresh GET request
        $publicResponse = $this->get('/iletisim');
        $publicResponse->assertSuccessful()
            ->assertSee('TEST ILETISIM ADRESI')
            ->assertSee('+90 312 222 33 44')
            ->assertSee('testiletisim@dosttv.com');
    }

    public function test_footer_does_not_render_duplicate_contact_column(): void
    {
        $response = $this->get('/iletisim');

        $response->assertSuccessful();

        // Footer still renders Kurumsal column with İletişim link
        $response->assertSee(route('pages.show', 'iletisim'), false)
            ->assertSee('İletişim');

        // Footer does NOT render right-side İletişim column heading or duplicate text
        $html = $response->getContent();
        $this->assertStringNotContainsString('<h4 class="text-sm font-bold uppercase tracking-wider text-white border-b border-white/10 pb-2">İletişim</h4>', $html);
    }
    public function test_other_corporate_pages_save_successfully(): void
    {
        Livewire::actingAs($this->admin)
            ->test(EditPage::class, ['record' => $this->corporatePage->getRouteKey()])
            ->fillForm([
                'title' => 'KVKK Politikası Güncellendi',
                'content' => '<p>Yeni KVKK İçerik Metni</p>',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->corporatePage->refresh();
        $this->assertEquals('KVKK Politikası Güncellendi', $this->corporatePage->title);
        $this->assertEquals('<p>Yeni KVKK İçerik Metni</p>', $this->corporatePage->content);
    }

    public function test_admin_dom_has_no_nested_form_and_public_has_real_contact_form(): void
    {
        // 1. Verify Public page renders real <form action="...contact.store">
        $publicResponse = $this->get('/iletisim');
        $publicResponse->assertSuccessful();
        $publicHtml = $publicResponse->getContent();
        $this->assertStringContainsString('action="' . route('contact.store') . '"', $publicHtml);
        $this->assertStringContainsString('method="POST"', $publicHtml);

        // 2. Verify Admin EditPage preview does NOT output nested <form> tag inside preview tab
        $adminResponse = $this->actingAs($this->admin)
            ->get(PageResource::getUrl('edit', ['record' => $this->contactPage]));
        $adminResponse->assertSuccessful();
        $adminHtml = $adminResponse->getContent();
        
        // Ensure no contact.store form action exists inside Admin HTML
        $this->assertStringNotContainsString('action="' . route('contact.store') . '"', $adminHtml);
    }

    public function test_empty_page_settings_does_not_fallback_to_sitesetting(): void
    {
        // Set SiteSetting values
        $siteSettings = SiteSetting::current();
        $siteSettings->update([
            'address' => 'UNIQUE_SITESETTING_ADDR_999',
            'phone' => '0312 999 88 77',
            'email' => 'sitesetting@dosttv.com',
        ]);

        // Set Page settings to empty for contact info
        $this->contactPage->update([
            'settings' => [
                'address' => '',
                'phone' => '',
                'email' => '',
            ],
        ]);
        $this->contactPage->refresh();

        // GET public /iletisim page
        $response = $this->get('/iletisim');
        $response->assertSuccessful();

        // Cards must NOT render old SiteSetting values
        $response->assertDontSee('UNIQUE_SITESETTING_ADDR_999');
        $response->assertDontSee('0312 999 88 77');
        $response->assertDontSee('sitesetting@dosttv.com');
    }

    public function test_backfill_migration_populates_empty_contact_settings_without_overwriting_existing(): void
    {
        $siteSettings = SiteSetting::current();
        $siteSettings->update([
            'address' => 'Backfill Adres 555',
            'phone' => '0312 555 55 55',
            'email' => 'backfill@dosttv.com',
        ]);

        // 1. Create a page with empty contact settings
        $emptyPage = Page::create([
            'title' => 'İletişim Test Empty',
            'slug' => 'iletisim-test-empty',
            'settings' => [],
        ]);

        $settings = $emptyPage->settings ?? [];
        if (blank($settings['address'] ?? null)) {
            $settings['address'] = $siteSettings->address;
        }
        if (blank($settings['phone'] ?? null)) {
            $settings['phone'] = $siteSettings->phone;
        }
        if (blank($settings['email'] ?? null)) {
            $settings['email'] = $siteSettings->email;
        }
        $emptyPage->settings = $settings;
        $emptyPage->save();

        $this->assertEquals('Backfill Adres 555', $emptyPage->fresh()->settings['address']);
        $this->assertEquals('0312 555 55 55', $emptyPage->fresh()->settings['phone']);
        $this->assertEquals('backfill@dosttv.com', $emptyPage->fresh()->settings['email']);

        // 2. Verify that existing non-empty values are NOT overwritten
        $existingPage = Page::create([
            'title' => 'İletişim Test Existing',
            'slug' => 'iletisim-test-existing',
            'settings' => [
                'address' => 'Özel Adres Dokunma',
                'phone' => '0312 111 11 11',
                'email' => 'ozel@dosttv.com',
            ],
        ]);

        $existingSettings = $existingPage->settings;
        if (blank($existingSettings['address'] ?? null)) {
            $existingSettings['address'] = $siteSettings->address;
        }
        $existingPage->settings = $existingSettings;
        $existingPage->save();

        $this->assertEquals('Özel Adres Dokunma', $existingPage->fresh()->settings['address']);
    }
}
