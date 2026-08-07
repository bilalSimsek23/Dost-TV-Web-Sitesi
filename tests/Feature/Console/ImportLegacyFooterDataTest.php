<?php

namespace Tests\Feature\Console;

use App\Models\Menu;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportLegacyFooterDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        User::factory()->create(['role' => 'administrator']);
    }

    public function test_dry_run_does_not_modify_database(): void
    {
        $setting = SiteSetting::current();
        $setting->update([
            'phone' => null,
            'email' => null,
        ]);

        $this->artisan('footer:import-legacy-data', ['--dry-run' => true])
            ->assertExitCode(0);

        $freshSetting = SiteSetting::current();
        $this->assertNull($freshSetting->phone);
        $this->assertNull($freshSetting->email);
    }

    public function test_empty_fields_are_filled_with_legacy_data(): void
    {
        $setting = SiteSetting::current();
        $setting->update([
            'phone' => null,
            'email' => null,
            'address' => null,
            'facebook_url' => null,
        ]);

        $this->artisan('footer:import-legacy-data')
            ->assertExitCode(0);

        $freshSetting = SiteSetting::current();
        $this->assertEquals('+90 (312) 341 21 21', $freshSetting->phone);
        $this->assertEquals('iletisim@dosttv.com', $freshSetting->email);
        $this->assertNotNull($freshSetting->address);
        $this->assertEquals('https://facebook.com/dosttv', $freshSetting->facebook_url);
    }

    public function test_filled_fields_are_preserved_and_not_overwritten(): void
    {
        $setting = SiteSetting::current();
        $setting->update([
            'phone' => '+90 (212) 999 00 00',
            'email' => 'ozel@dosttv.com',
        ]);

        $this->artisan('footer:import-legacy-data')
            ->assertExitCode(0);

        $freshSetting = SiteSetting::current();
        $this->assertEquals('+90 (212) 999 00 00', $freshSetting->phone);
        $this->assertEquals('ozel@dosttv.com', $freshSetting->email);
    }

    public function test_footer_menus_are_seeded_idempotently(): void
    {
        $this->artisan('footer:import-legacy-data')->assertExitCode(0);

        $primaryMenu = Menu::where('location', 'footer_primary')->first();
        $legalMenu = Menu::where('location', 'footer_legal')->first();

        $this->assertNotNull($primaryMenu);
        $this->assertNotNull($legalMenu);

        $primaryItemsCount = $primaryMenu->items()->count();
        $legalItemsCount = $legalMenu->items()->count();

        $this->assertGreaterThan(0, $primaryItemsCount);
        $this->assertGreaterThan(0, $legalItemsCount);

        // Re-run command
        $this->artisan('footer:import-legacy-data')->assertExitCode(0);

        $this->assertEquals($primaryItemsCount, $primaryMenu->items()->count());
        $this->assertEquals($legalItemsCount, $legalMenu->items()->count());
    }

    public function test_public_footer_renders_imported_legacy_data(): void
    {
        $this->artisan('footer:import-legacy-data')->assertExitCode(0);

        $this->get(route('home'))
            ->assertSuccessful()
            ->assertSee('iletisim@dosttv.com')
            ->assertSee('Tüm hakları saklıdır.');
    }
}
