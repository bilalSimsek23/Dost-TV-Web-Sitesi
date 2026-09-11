<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\SiteLayout\AppearanceLayoutPage;
use App\Models\FontFamily;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ThemeSettingsPageTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'super_admin']);
        FontFamily::create([
            'name' => 'Instrument Sans',
            'slug' => 'instrument-sans',
            'source_type' => 'google_fonts',
            'is_default' => true,
            'is_active' => true,
        ]);
    }

    public function test_theme_settings_can_be_saved(): void
    {
        $newTheme = [
            'mode' => 'dark',
            'dark' => [
                'background' => '#111111',
                'surface' => '#222222',
                'accent' => '#f43f5e',
            ],
            'light' => [
                'background' => '#ffffff',
                'surface' => '#ffffff',
                'accent' => '#e11d48',
            ],
        ];

        Livewire::actingAs($this->admin)
            ->test(AppearanceLayoutPage::class)
            ->set('data.theme_settings', $newTheme)
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('#111111', SiteSetting::current()->fresh()->normalized_theme_settings['dark']['background']);
    }

    public function test_legacy_theme_settings_page_is_disabled(): void
    {
        $this->assertFalse(\App\Filament\Pages\ThemeSettings::canAccess());
    }
}
