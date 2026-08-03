<?php

namespace Tests\Unit;

use App\Models\ThemeSetting;
use App\Services\Theme\ThemeSettingsService;
use App\Support\SiteCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ThemeSettingsServiceTest extends TestCase
{
    use RefreshDatabase;

    private ThemeSettingsService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ThemeSettingsService;
    }

    public function test_get_returns_stored_value_for_existing_key(): void
    {
        ThemeSetting::create([
            'key' => 'color.primary',
            'group' => 'colors',
            'value' => '#be123c',
            'value_type' => 'color',
            'label' => 'Ana Renk',
        ]);

        $this->assertSame('#be123c', $this->service->get('color.primary'));
    }

    public function test_get_returns_default_for_missing_key(): void
    {
        $this->assertSame('#000000', $this->service->get('color.unknown', '#000000'));
        $this->assertNull($this->service->get('color.unknown'));
    }

    public function test_all_settings_are_served_from_cache(): void
    {
        ThemeSetting::create([
            'key' => 'color.primary',
            'group' => 'colors',
            'value' => '#be123c',
            'value_type' => 'color',
            'label' => 'Ana Renk',
        ]);

        $this->service->all();
        $this->assertTrue(Cache::has('site:theme:active'));

        // Changing the DB row directly (bypassing the model event) must not
        // affect the cached value until the cache is explicitly busted.
        ThemeSetting::withoutEvents(function () {
            ThemeSetting::where('key', 'color.primary')->update(['value' => '#000000']);
        });

        $this->assertSame('#be123c', $this->service->get('color.primary'));

        SiteCache::forgetTheme();

        $this->assertSame('#000000', $this->service->get('color.primary'));
    }

    public function test_set_updates_value_and_busts_cache(): void
    {
        ThemeSetting::create([
            'key' => 'color.primary',
            'group' => 'colors',
            'value' => '#be123c',
            'value_type' => 'color',
            'label' => 'Ana Renk',
        ]);

        $this->service->all();

        $this->service->set('color.primary', '#111111');

        $this->assertSame('#111111', $this->service->get('color.primary'));
    }
}
