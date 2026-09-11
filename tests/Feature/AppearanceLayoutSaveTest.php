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

class AppearanceLayoutSaveTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        FontFamily::create([
            'name' => 'Instrument Sans',
            'slug' => 'instrument-sans',
            'source_type' => 'google_fonts',
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->admin = User::factory()->create([
            'email' => 'admin@dosttv.com',
            'role' => 'administrator',
        ]);
    }

    public function test_appearance_layout_page_save_updates_db_and_renders_public_css(): void
    {
        $siteSettings = SiteSetting::current();
        $themeSettings = $siteSettings->normalized_theme_settings;

        // 1. Set background to #123456
        $themeSettings['dark']['background'] = '#123456';

        Livewire::actingAs($this->admin)
            ->test(AppearanceLayoutPage::class)
            ->set('data.theme_settings', $themeSettings)
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified('Görünüm Ayarları Güncellendi');

        SiteCache::forgetSiteSetting();
        SiteCache::forgetTheme();

        // 2. DB verification
        $updatedSettings = SiteSetting::current()->fresh()->normalized_theme_settings;
        $this->assertEquals('#123456', $updatedSettings['dark']['background']);

        // 3. Public GET verification
        $publicResponse = $this->get('/');
        $publicResponse->assertSuccessful()
            ->assertSee('--color-bg: #123456', false);

        // 4. Revert background to #030712
        $themeSettings['dark']['background'] = '#030712';

        Livewire::actingAs($this->admin)
            ->test(AppearanceLayoutPage::class)
            ->set('data.theme_settings', $themeSettings)
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified('Görünüm Ayarları Güncellendi');

        SiteCache::forgetSiteSetting();
        SiteCache::forgetTheme();

        $revertedSettings = SiteSetting::current()->fresh()->normalized_theme_settings;
        $this->assertEquals('#030712', $revertedSettings['dark']['background']);

        $revertedPublicResponse = $this->get('/');
        $revertedPublicResponse->assertSuccessful()
            ->assertSee('--color-bg: #030712', false);
    }
}
