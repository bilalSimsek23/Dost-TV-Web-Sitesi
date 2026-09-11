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

class PageLivePreviewWithoutSavingAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Page $yayinIlkeleri;

    protected Page $iletisim;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'email' => 'admin@dosttv.com',
            'role' => 'administrator',
        ]);

        $this->yayinIlkeleri = Page::firstOrCreate(
            ['slug' => 'dost-tv-yayin-ilkeleri'],
            [
                'title' => 'Dost TV Yayın İlkeleri',
                'content' => '<p>Yayın ilkelerimiz burada yer alır.</p>',
                'page_type' => 'corporate',
                'template' => 'corporate',
                'status' => 'published',
                'show_in_footer' => true,
                'settings' => [
                    'design' => [
                        'use_custom_design' => true,
                        'background_color' => '#030712',
                        'padding_top' => 60,
                    ],
                ],
            ]
        );

        $this->iletisim = Page::firstOrCreate(
            ['slug' => 'iletisim'],
            [
                'title' => 'İletişim',
                'content' => '<p>İletişim detayları.</p>',
                'page_type' => 'corporate',
                'template' => 'contact',
                'status' => 'published',
                'show_in_footer' => true,
            ]
        );
    }

    public function test_page_live_preview_without_saving_acceptance_scenario(): void
    {
        // 1. Initial DB state: padding_top = 60, background_color = #030712
        $initialDbSettings = $this->yayinIlkeleri->fresh()->settings['design'];
        $this->assertEquals(60, $initialDbSettings['padding_top']);
        $this->assertEquals('#030712', $initialDbSettings['background_color']);

        // 2. Admin edits "Dost TV Yayın İlkeleri" and fills unsaved form state
        $unsavedDesignSettings = [
            'use_custom_design' => true,
            'background_color' => '#5B2131',
            'surface_color' => '#1E293B',
            'accent_color' => '#F59E0B',
            'content_max_width' => 1000,
            'card_radius' => 40,
            'padding_top' => 140,
            'padding_bottom' => 80,
            'show_surface' => true,
        ];

        // Call livePreview action without saving
        Livewire::actingAs($this->admin)
            ->test(EditPage::class, ['record' => $this->yayinIlkeleri->slug])
            ->set('data.settings.design', $unsavedDesignSettings)
            ->mountAction('livePreview')
            ->assertHasNoFormErrors()
            ->assertNotified('Canlı Sayfa Test Modu Başlatıldı');

        // 3. Assert DB STILL HAS original values (NOT updated)
        $freshDbSettings = $this->yayinIlkeleri->fresh()->settings['design'];
        $this->assertEquals(60, $freshDbSettings['padding_top']);
        $this->assertEquals('#030712', $freshDbSettings['background_color']);

        $token = session('page_preview_token');
        $this->assertNotEmpty($token);

        // 4. Assert GET /dost-tv-yayin-ilkeleri?page_preview_token=XYZ as admin sees temporary preview design
        $previewResponse = $this->actingAs($this->admin)->get('/dost-tv-yayin-ilkeleri?page_preview_token=' . $token);
        $previewResponse->assertSuccessful()
            ->assertSee('SAYFA TEST MODU')
            ->assertSee('background-color: #5B2131', false)
            ->assertSee('padding-top: 140px', false)
            ->assertSee('border-radius: 40px', false);

        // 5. Assert normal visitor (unauthenticated / no preview session) GET /dost-tv-yayin-ilkeleri sees original DB design
        auth()->logout();
        session()->flush();

        $normalVisitorResponse = $this->get('/dost-tv-yayin-ilkeleri');
        $normalVisitorResponse->assertSuccessful()
            ->assertDontSee('SAYFA TEST MODU')
            ->assertDontSee('background-color: #5B2131', false)
            ->assertDontSee('padding-top: 140px', false)
            ->assertSee('padding-top: 60px', false);

        // 6. Assert GET /iletisim as admin is NOT affected by Yayın İlkeleri preview
        $iletisimResponse = $this->actingAs($this->admin)->get('/iletisim');
        $iletisimResponse->assertSuccessful()
            ->assertDontSee('background-color: #5B2131', false)
            ->assertDontSee('padding-top: 140px', false);

        // 7. Close preview mode
        $closeResponse = $this->actingAs($this->admin)->get(route('page.preview.close', ['slug' => 'dost-tv-yayin-ilkeleri']));
        $closeResponse->assertRedirect('/dost-tv-yayin-ilkeleri');

        $this->assertNull(session('page_preview_token'));
        $this->assertNull(session('page_preview_data'));

        // 8. Now execute normal Save in admin
        Livewire::actingAs($this->admin)
            ->test(EditPage::class, ['record' => $this->yayinIlkeleri->slug])
            ->set('data.settings.design', $unsavedDesignSettings)
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified('Kurumsal Bilgi Güncellendi');

        // Assert DB is now updated and public site sees new values
        $updatedDbSettings = $this->yayinIlkeleri->fresh()->settings['design'];
        $this->assertEquals(140, $updatedDbSettings['padding_top']);
        $this->assertEquals('#5B2131', $updatedDbSettings['background_color']);

        $savedPublicResponse = $this->get('/dost-tv-yayin-ilkeleri');
        $savedPublicResponse->assertSuccessful()
            ->assertSee('background-color: #5B2131', false)
            ->assertSee('padding-top: 140px', false);
    }

    public function test_edit_page_has_exactly_two_panel_forms_without_extra_preview_forms(): void
    {
        $response = $this->actingAs($this->admin)->get(PageResource::getUrl('edit', ['record' => $this->yayinIlkeleri]));
        $response->assertSuccessful();

        // Filament panel layout renders 1 global header form + 1 page form = 2 forms total.
        $formCount = substr_count($response->getContent(), '<form');
        $this->assertEquals(2, $formCount, 'PageResource EditPage MUST contain exactly 2 panel forms (global + page form) with zero extra preview forms.');
    }
}
