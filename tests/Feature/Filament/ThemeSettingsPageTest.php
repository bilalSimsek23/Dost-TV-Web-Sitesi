<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\ThemeSettings;
use App\Models\ThemeSetting;
use App\Models\User;
use App\Services\Theme\ThemeSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
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

        ThemeSetting::create([
            'key' => 'color.primary',
            'group' => 'colors',
            'value' => '#be123c',
            'value_type' => 'color',
            'label' => 'Ana Renk',
        ]);
        ThemeSetting::create([
            'key' => 'brand.site_name',
            'group' => 'brand',
            'value' => 'Dost TV',
            'value_type' => 'text',
            'label' => 'Site Adı',
        ]);
    }

    public function test_theme_settings_can_be_saved(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ThemeSettings::class)
            ->set('data.color.primary', '#111111')
            ->set('data.brand.site_name', 'Dost TV Güncel')
            ->call('save')
            ->assertHasNoFormErrors();

        $service = app(ThemeSettingsService::class);

        $this->assertSame('#111111', $service->get('color.primary'));
        $this->assertSame('Dost TV Güncel', $service->get('brand.site_name'));
    }

    public function test_invalid_color_value_is_rejected(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ThemeSettings::class)
            ->set('data.color.primary', 'not-a-color')
            ->call('save')
            ->assertHasErrors(['data.color.primary']);

        $service = app(ThemeSettingsService::class);
        $this->assertSame('#be123c', $service->get('color.primary'));
    }

    public function test_saving_clears_the_theme_cache(): void
    {
        app(ThemeSettingsService::class)->all();
        $this->assertTrue(Cache::has('site:theme:active'));

        Livewire::actingAs($this->admin)
            ->test(ThemeSettings::class)
            ->set('data.color.primary', '#222222')
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('#222222', app(ThemeSettingsService::class)->get('color.primary'));
    }
}
