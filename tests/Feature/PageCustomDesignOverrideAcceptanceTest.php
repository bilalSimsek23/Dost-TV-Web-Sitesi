<?php

namespace Tests\Feature;

use App\Filament\Resources\Pages\Pages\EditPage;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\PageDesignResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PageCustomDesignOverrideAcceptanceTest extends TestCase
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

    public function test_page_custom_design_override_acceptance_scenario(): void
    {
        // 1. Initial State: use_custom_design is false
        $initialResolver = PageDesignResolver::resolve($this->yayinIlkeleri);
        $this->assertFalse($initialResolver['use_custom_design']);

        // 2. Admin edits "Dost TV Yayın İlkeleri" and enables custom design settings
        $customDesignSettings = [
            'use_custom_design' => true,
            'background_color' => '#111827',
            'surface_color' => '#172033',
            'accent_color' => '#D4A853',
            'text_color' => '#F8FAFC',
            'muted_text_color' => '#94A3B8',
            'content_max_width' => 900,
            'card_radius' => 24,
            'padding_top' => 48,
            'padding_bottom' => 64,
            'show_surface' => true,
        ];

        Livewire::actingAs($this->admin)
            ->test(EditPage::class, ['record' => $this->yayinIlkeleri->slug])
            ->fillForm([
                'title' => 'Dost TV Yayın İlkeleri',
                'settings.design' => $customDesignSettings,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        // Fresh page instance from DB
        $freshYayinIlkeleri = $this->yayinIlkeleri->fresh();
        $resolved = PageDesignResolver::resolve($freshYayinIlkeleri);

        $this->assertTrue($resolved['use_custom_design']);
        $this->assertEquals('#111827', $resolved['background_color']);
        $this->assertEquals('#172033', $resolved['surface_color']);
        $this->assertEquals('#D4A853', $resolved['accent_color']);
        $this->assertEquals(900, $resolved['content_max_width']);
        $this->assertEquals(24, $resolved['card_radius']);

        // 3. Assert GET /dost-tv-yayin-ilkeleri reflects the custom design
        $responseYayinIlkeleri = $this->get('/dost-tv-yayin-ilkeleri');
        $responseYayinIlkeleri->assertSuccessful()
            ->assertSee('background-color: #111827', false)
            ->assertSee('background-color: #172033', false)
            ->assertSee('max-width: 900px', false)
            ->assertSee('border-radius: 24px', false);

        // 4. Assert GET /iletisim is NOT affected (does not render #111827 or max-width: 900px)
        $responseIletisim = $this->get('/iletisim');
        $responseIletisim->assertSuccessful()
            ->assertDontSee('background-color: #111827', false)
            ->assertDontSee('max-width: 900px', false);

        // 5. Assert Homepage GET / is NOT affected
        $responseHome = $this->get('/');
        $responseHome->assertSuccessful()
            ->assertDontSee('background-color: #111827', false)
            ->assertDontSee('max-width: 900px', false);

        // 6. Assert Global SiteSetting theme is NOT mutated
        $globalThemeSettings = SiteSetting::current()->fresh()->normalized_theme_settings;
        $this->assertEquals('#030712', $globalThemeSettings['dark']['background']);

        // 7. Turn use_custom_design off and save
        $customDesignSettings['use_custom_design'] = false;

        Livewire::actingAs($this->admin)
            ->test(EditPage::class, ['record' => $this->yayinIlkeleri->slug])
            ->fillForm([
                'settings.design' => $customDesignSettings,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        // Assert /dost-tv-yayin-ilkeleri reverts back to global theme
        $responseReverted = $this->get('/dost-tv-yayin-ilkeleri');
        $responseReverted->assertSuccessful()
            ->assertDontSee('background-color: #111827', false)
            ->assertDontSee('max-width: 900px', false);
    }
}
