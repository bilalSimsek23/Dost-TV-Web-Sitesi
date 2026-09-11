<?php

namespace Tests\Feature;

use App\Filament\Pages\SiteLayout\AppearanceLayoutPage;
use App\Models\FontFamily;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ThemeSettingsGradientTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'role' => 'administrator',
            'is_active' => true,
        ]);

        FontFamily::create([
            'name' => 'Instrument Sans',
            'slug' => 'instrument-sans',
            'source_type' => 'system',
            'is_default' => true,
            'is_active' => true,
        ]);
    }

    public function test_site_setting_renders_default_disabled_gradient_css_variables(): void
    {
        $siteSettings = SiteSetting::current();
        $css = $siteSettings->renderThemeCss();

        $this->assertStringContainsString('--gradient-enabled: 0;', $css);
        $this->assertStringContainsString('--gradient-background: var(--color-bg, #030712);', $css);
        $this->assertStringContainsString('--gradient-top-dark-hold: 25%;', $css);
        $this->assertStringContainsString('--gradient-light-color: #F5F2E8;', $css);
    }

    public function test_enabling_gradient_renders_linear_gradient_background(): void
    {
        $siteSettings = SiteSetting::current();
        $ts = $siteSettings->normalized_theme_settings;
        $ts['page_gradient']['enabled'] = true;
        $siteSettings->update(['theme_settings' => $ts]);

        $css = $siteSettings->fresh()->renderThemeCss();

        $this->assertStringContainsString('--gradient-enabled: 1;', $css);
        $this->assertStringContainsString('--gradient-background: linear-gradient(', $css);
    }

    public function test_backward_compatibility_maps_legacy_keys(): void
    {
        $siteSettings = SiteSetting::current();
        $ts = $siteSettings->normalized_theme_settings;
        $ts['page_gradient'] = [
            'enabled' => false,
            'dark_hold_until' => 12,
            'fade_start' => 18,
            'light_end' => 88,
            'light_color' => '#EEEEEE',
        ];
        $siteSettings->update(['theme_settings' => $ts]);

        $gradient = $siteSettings->fresh()->normalized_theme_settings['page_gradient'];

        $this->assertFalse($gradient['enabled']);
        $this->assertEquals(12, $gradient['top_dark_hold']);
        $this->assertEquals(18, $gradient['top_fade_start']);
        $this->assertEquals(88, $gradient['bottom_dark_at']);
        $this->assertEquals('#EEEEEE', $gradient['light_color']);
        $this->assertEquals('#030712', $gradient['bottom_color']);
    }

    public function test_updating_bidirectional_page_gradient_settings_reflects_in_rendered_css(): void
    {
        $siteSettings = SiteSetting::current();
        $ts = $siteSettings->normalized_theme_settings;
        $ts['page_gradient'] = [
            'enabled' => true,
            'top_dark_hold' => 10,
            'top_fade_start' => 15,
            'light_zone_start' => 40,
            'light_zone_end' => 60,
            'bottom_fade_start' => 65,
            'bottom_dark_at' => 90,
            'light_color' => '#F5F2E8',
            'bottom_color' => '#030712',
        ];
        $siteSettings->update(['theme_settings' => $ts]);

        $css = $siteSettings->fresh()->renderThemeCss();

        $this->assertStringContainsString('--gradient-enabled: 1;', $css);
        $this->assertStringContainsString('--gradient-top-dark-hold: 10%;', $css);
        $this->assertStringContainsString('--gradient-top-fade-start: 15%;', $css);
        $this->assertStringContainsString('--gradient-light-zone-start: 40%;', $css);
        $this->assertStringContainsString('--gradient-light-zone-end: 60%;', $css);
        $this->assertStringContainsString('--gradient-bottom-fade-start: 65%;', $css);
        $this->assertStringContainsString('--gradient-bottom-dark-at: 90%;', $css);
        $this->assertStringContainsString('--gradient-light-color: #F5F2E8;', $css);
        $this->assertStringContainsString('--gradient-bottom-color: #030712;', $css);
    }

    public function test_appearance_layout_page_live_preview_generates_preview_token_with_temporary_bidirectional_gradient(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(AppearanceLayoutPage::class)
            ->set('data.theme_settings.page_gradient.enabled', true)
            ->set('data.theme_settings.page_gradient.top_dark_hold', 10)
            ->set('data.theme_settings.page_gradient.top_fade_start', 15)
            ->set('data.theme_settings.page_gradient.light_zone_start', 40)
            ->set('data.theme_settings.page_gradient.light_zone_end', 60)
            ->set('data.theme_settings.page_gradient.bottom_fade_start', 65)
            ->set('data.theme_settings.page_gradient.bottom_dark_at', 90)
            ->set('data.theme_settings.page_gradient.light_color', '#F5F2E8')
            ->set('data.theme_settings.page_gradient.bottom_color', '#030712')
            ->call('livePreview')
            ->assertNotified('Canlı Tema Test Modu Başlatıldı');

        $token = session('theme_preview_token');
        $this->assertNotEmpty($token);

        $previewData = session('theme_preview_data');
        $gradient = $previewData['theme_settings']['page_gradient'];

        $this->assertTrue($gradient['enabled']);
        $this->assertEquals(10, $gradient['top_dark_hold']);
        $this->assertEquals(15, $gradient['top_fade_start']);
        $this->assertEquals(40, $gradient['light_zone_start']);
        $this->assertEquals(60, $gradient['light_zone_end']);

        $response = $this->actingAs($this->adminUser)->get('/?theme_preview_token=' . $token);
        $response->assertSuccessful();
        $response->assertSee('--gradient-enabled: 1;', false);
        $response->assertSee('--gradient-top-dark-hold: 10%;', false);
    }

    public function test_appearance_layout_page_saves_bidirectional_gradient_settings_permanently(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(AppearanceLayoutPage::class)
            ->set('data.theme_settings.page_gradient.enabled', false)
            ->set('data.theme_settings.page_gradient.top_dark_hold', 10)
            ->set('data.theme_settings.page_gradient.top_fade_start', 15)
            ->set('data.theme_settings.page_gradient.light_zone_start', 40)
            ->set('data.theme_settings.page_gradient.light_zone_end', 60)
            ->set('data.theme_settings.page_gradient.bottom_fade_start', 65)
            ->set('data.theme_settings.page_gradient.bottom_dark_at', 90)
            ->set('data.theme_settings.page_gradient.light_color', '#F5F2E8')
            ->set('data.theme_settings.page_gradient.bottom_color', '#030712')
            ->call('save')
            ->assertNotified('Görünüm Ayarları Güncellendi');

        $siteSettings = SiteSetting::current()->fresh();
        $gradient = $siteSettings->normalized_theme_settings['page_gradient'];

        $this->assertFalse($gradient['enabled']);
        $this->assertEquals(10, $gradient['top_dark_hold']);
        $this->assertEquals(15, $gradient['top_fade_start']);
        $this->assertEquals(40, $gradient['light_zone_start']);
        $this->assertEquals(60, $gradient['light_zone_end']);
    }
}
