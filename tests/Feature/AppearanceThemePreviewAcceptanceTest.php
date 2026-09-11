<?php

namespace Tests\Feature;

use App\Filament\Pages\SiteLayout\AppearanceLayoutPage;
use App\Models\FontFamily;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\SiteCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

class AppearanceThemePreviewAcceptanceTest extends TestCase
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

    public function test_live_theme_preview_without_saving_to_db_acceptance(): void
    {
        $siteSettings = SiteSetting::current();

        // 1. Initial DB theme values
        $dbThemeSettings = $siteSettings->normalized_theme_settings;
        $this->assertEquals('#030712', $dbThemeSettings['dark']['background']);

        // 2. Prepare new unsaved colors
        $unsavedThemeSettings = $dbThemeSettings;
        $unsavedThemeSettings['dark']['background'] = '#4C1D95';
        $unsavedThemeSettings['dark']['surface'] = '#166534';
        $unsavedThemeSettings['dark']['accent'] = '#F97316';

        // 3. Call livePreview on AppearanceLayoutPage without calling save
        $component = Livewire::actingAs($this->admin)
            ->test(AppearanceLayoutPage::class)
            ->set('data.theme_settings', $unsavedThemeSettings)
            ->call('livePreview')
            ->assertHasNoFormErrors()
            ->assertNotified('Canlı Tema Test Modu Başlatıldı');

        // 4. Assert DB STILL contains default DOST theme values
        $freshDbSettings = SiteSetting::current()->fresh()->normalized_theme_settings;
        $this->assertEquals('#030712', $freshDbSettings['dark']['background']);

        $token = session('theme_preview_token');
        $this->assertNotEmpty($token);

        // 5. Assert normal visitor (unauthenticated / no preview session / no token) GET / sees default theme
        auth()->logout();
        session()->flush();

        $publicNormalResponse = $this->get('/');
        $publicNormalResponse->assertSuccessful()
            ->assertSee('--color-bg: #030712', false)
            ->assertDontSee('TEMA TEST MODU');

        // 6. Assert preview session/token GET / as admin shows temporary purple/green/orange theme
        $previewResponse = $this->actingAs($this->admin)->get('/?theme_preview_token=' . $token);
        $previewResponse->assertSuccessful()
            ->assertSee('--color-bg: #4C1D95', false)
            ->assertSee('--color-surface: #166534', false)
            ->assertSee('--color-accent: #F97316', false)
            ->assertSee('TEMA TEST MODU');

        // 7. Test mode toggle (Light Mode)
        $toggleResponse = $this->actingAs($this->admin)->get(route('theme.preview.toggle-mode', ['mode' => 'light']));
        $toggleResponse->assertRedirect();
        $this->assertEquals('light', session('theme_preview_mode'));

        // 8. Close preview mode
        $closeResponse = $this->actingAs($this->admin)->get(route('theme.preview.close'));
        $closeResponse->assertRedirect('/');

        $this->assertNull(session('theme_preview_token'));
        $this->assertNull(session('theme_preview_data'));

        // Assert public site reverts to original DB colors
        $afterCloseResponse = $this->actingAs($this->admin)->get('/');
        $afterCloseResponse->assertSuccessful()
            ->assertSee('--color-bg: #030712', false)
            ->assertDontSee('TEMA TEST MODU');

        // 9. Now execute normal Save
        Livewire::actingAs($this->admin)
            ->test(AppearanceLayoutPage::class)
            ->set('data.theme_settings', $unsavedThemeSettings)
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified('Görünüm Ayarları Güncellendi');

        SiteCache::forgetSiteSetting();
        SiteCache::forgetTheme();

        // Assert DB is updated
        $savedDbSettings = SiteSetting::current()->fresh()->normalized_theme_settings;
        $this->assertEquals('#4C1D95', $savedDbSettings['dark']['background']);

        $savedPublicResponse = $this->get('/');
        $savedPublicResponse->assertSuccessful()
            ->assertSee('--color-bg: #4C1D95', false);

        // 10. Restore default DOST theme in DB
        $restoredTheme = $unsavedThemeSettings;
        $restoredTheme['dark']['background'] = '#030712';
        $restoredTheme['dark']['surface'] = '#0f172a';
        $restoredTheme['dark']['accent'] = '#f43f5e';

        Livewire::actingAs($this->admin)
            ->test(AppearanceLayoutPage::class)
            ->set('data.theme_settings', $restoredTheme)
            ->call('save');

        SiteCache::forgetSiteSetting();
        SiteCache::forgetTheme();

        $this->assertEquals('#030712', SiteSetting::current()->fresh()->normalized_theme_settings['dark']['background']);
    }
}
