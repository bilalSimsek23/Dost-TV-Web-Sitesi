<?php

namespace Tests\Feature;

use App\Filament\Pages\SiteLayout\AppearanceLayoutPage;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CentralThemeTokensTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'administrator']);
        \App\Models\FontFamily::create([
            'name' => 'Instrument Sans',
            'slug' => 'instrument-sans',
            'source_type' => 'google_fonts',
            'is_default' => true,
            'is_active' => true,
        ]);
    }

    public function test_default_theme_settings_render_valid_css_tokens(): void
    {
        $settings = SiteSetting::current();
        $css = $settings->renderThemeCss();

        $this->assertStringContainsString('--color-bg:', $css);
        $this->assertStringContainsString('--color-surface:', $css);
        $this->assertStringContainsString('--color-accent:', $css);
        $this->assertStringContainsString('--color-text:', $css);
        $this->assertStringContainsString('--color-border:', $css);
        $this->assertStringContainsString('--color-card:', $css);
    }

    public function test_custom_dark_and_light_theme_colors_can_be_saved_and_rendered_on_public_site(): void
    {
        $customTheme = [
            'mode' => 'light',
            'dark' => [
                'background' => '#080d1a',
                'surface' => '#131c33',
                'accent' => '#e11d48',
            ],
            'light' => [
                'background' => '#f1f5f9',
                'surface' => '#ffffff',
                'accent' => '#d97706',
            ],
        ];

        Livewire::actingAs($this->admin)
            ->test(AppearanceLayoutPage::class)
            ->fillForm([
                'theme_settings' => $customTheme,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $freshSettings = SiteSetting::current()->fresh();
        $this->assertEquals('light', $freshSettings->normalized_theme_settings['mode']);
        $this->assertEquals('#080d1a', $freshSettings->normalized_theme_settings['dark']['background']);
        $this->assertEquals('#d97706', $freshSettings->normalized_theme_settings['light']['accent']);

        // Verify public site renders custom CSS tokens and data-theme="light" attribute
        $response = $this->get(route('home'));
        $response->assertSuccessful();
        $response->assertSee('data-theme="light"', false);
        $response->assertSee('#080d1a', false);
        $response->assertSee('#d97706', false);
    }

    public function test_system_theme_mode_renders_prefers_color_scheme_media_queries(): void
    {
        SiteSetting::current()->update([
            'theme_settings' => [
                'mode' => 'system',
                'dark' => [
                    'background' => '#020617',
                    'surface' => '#0f172a',
                    'accent' => '#f43f5e',
                ],
                'light' => [
                    'background' => '#ffffff',
                    'surface' => '#f8fafc',
                    'accent' => '#e11d48',
                ],
            ],
        ]);

        $response = $this->get(route('home'));
        $response->assertSuccessful();
        $response->assertSee('data-theme="system"', false);
        $response->assertSee('@media (prefers-color-scheme: dark)', false);
        $response->assertSee('@media (prefers-color-scheme: light)', false);
    }

    public function test_acceptance_flow_with_custom_test_colors(): void
    {
        $testTheme = [
            'mode' => 'dark',
            'dark' => [
                'background' => '#7C3AED',
                'surface' => '#14532D',
                'accent' => '#F97316',
            ],
            'light' => [
                'background' => '#f8fafc',
                'surface' => '#ffffff',
                'accent' => '#e11d48',
            ],
        ];

        // 1. Save via Livewire Form & Assert Success Notification
        Livewire::actingAs($this->admin)
            ->test(AppearanceLayoutPage::class)
            ->set('data.theme_settings', $testTheme)
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified('Görünüm Ayarları Güncellendi');

        // 2. DB verification
        $freshSetting = SiteSetting::current()->fresh();
        $this->assertEquals('#7C3AED', $freshSetting->normalized_theme_settings['dark']['background']);
        $this->assertEquals('#14532D', $freshSetting->normalized_theme_settings['dark']['surface']);
        $this->assertEquals('#F97316', $freshSetting->normalized_theme_settings['dark']['accent']);

        // 3. Admin reload form fill verification
        Livewire::actingAs($this->admin)
            ->test(AppearanceLayoutPage::class)
            ->assertSet('data.theme_settings.dark.background', '#7C3AED')
            ->assertSet('data.theme_settings.dark.surface', '#14532D')
            ->assertSet('data.theme_settings.dark.accent', '#F97316');

        // 4. Public GET request verification
        $res = $this->get(route('home'));
        $res->assertSuccessful();
        $res->assertSee('data-theme="dark"', false);

        // 5. HTML/CSS token verification
        $res->assertSee('--color-bg: #7C3AED;', false);
        $res->assertSee('--color-surface: #14532D;', false);
        $res->assertSee('--color-accent: #F97316;', false);
    }

    public function test_validation_error_prevents_success_notification(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AppearanceLayoutPage::class)
            ->set('data.theme_settings.dark.background', '')
            ->call('save')
            ->assertHasFormErrors(['theme_settings.dark.background'])
            ->assertNotNotified('Görünüm Ayarları Güncellendi');
    }
}
