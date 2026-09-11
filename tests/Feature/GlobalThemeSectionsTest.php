<?php

namespace Tests\Feature;

use App\Filament\Pages\SiteLayout\AppearanceLayoutPage;
use App\Models\FontFamily;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\SiteCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GlobalThemeSectionsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'administrator']);
        FontFamily::create([
            'name' => 'Instrument Sans',
            'slug' => 'instrument-sans',
            'source_type' => 'google_fonts',
            'is_default' => true,
            'is_active' => true,
        ]);
    }

    public function test_global_theme_colors_propagate_to_all_sections_and_cards(): void
    {
        $testTheme = [
            'mode' => 'dark',
            'dark' => [
                'background' => '#030712',
                'surface' => '#0F172A',
                'accent' => '#F43F5E',
            ],
            'light' => [
                'background' => '#ffffff',
                'surface' => '#ffffff',
                'accent' => '#e11d48',
            ],
        ];

        // Save via Livewire AppearanceLayoutPage
        Livewire::actingAs($this->admin)
            ->test(AppearanceLayoutPage::class)
            ->set('data.theme_settings', $testTheme)
            ->call('save')
            ->assertHasNoFormErrors();

        SiteCache::forgetSiteSetting();

        // Fetch homepage response
        $response = $this->get('/');
        $response->assertStatus(200);

        // Verify root CSS variables in header use default dark values
        $response->assertSee('--color-bg: #030712', false);
        $response->assertSee('--color-surface: #0F172A', false);
        $response->assertSee('--color-accent: #F43F5E', false);

        // Verify test colors are completely absent
        $response->assertDontSee('#C6C3CC', false);
        $response->assertDontSee('#66FFE0', false);
        $response->assertDontSee('#F97316', false);
    }

    public function test_accent_color_change_saves_and_renders_on_public_site(): void
    {
        $blueAccentTheme = [
            'mode' => 'dark',
            'dark' => [
                'background' => '#030712',
                'surface' => '#0F172A',
                'accent' => '#2563EB',
            ],
            'light' => [
                'background' => '#ffffff',
                'surface' => '#ffffff',
                'accent' => '#e11d48',
            ],
        ];

        // 1. Save accent #2563EB in AppearanceLayoutPage
        Livewire::actingAs($this->admin)
            ->test(AppearanceLayoutPage::class)
            ->set('data.theme_settings', $blueAccentTheme)
            ->call('save')
            ->assertHasNoFormErrors();

        SiteCache::forgetSiteSetting();

        // 2. Verify DB updated
        $this->assertEquals('#2563EB', SiteSetting::current()->fresh()->normalized_theme_settings['dark']['accent']);

        // 3. Verify public HTML renders --color-accent: #2563EB
        $response = $this->get('/');
        $response->assertSee('--color-accent: #2563EB', false);

        // 4. Reset accent back to #F43F5E
        $defaultTheme = $blueAccentTheme;
        $defaultTheme['dark']['accent'] = '#F43F5E';

        Livewire::actingAs($this->admin)
            ->test(AppearanceLayoutPage::class)
            ->set('data.theme_settings', $defaultTheme)
            ->call('save')
            ->assertHasNoFormErrors();

        SiteCache::forgetSiteSetting();
        $this->assertEquals('#F43F5E', SiteSetting::current()->fresh()->normalized_theme_settings['dark']['accent']);
    }
}
