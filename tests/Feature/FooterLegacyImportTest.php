<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\Page;
use App\Models\Program;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FooterLegacyImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_dry_run_writes_nothing(): void
    {
        $this->artisan('footer:import-legacy-data', ['--dry-run' => true])
            ->assertSuccessful();

        $this->assertDatabaseMissing('menus', ['location' => 'footer_primary']);
        $this->assertDatabaseMissing('menus', ['location' => 'footer_legal']);

        $settings = SiteSetting::current();
        $this->assertNull($settings->email);
    }

    public function test_real_run_fills_empty_fields(): void
    {
        $this->artisan('footer:import-legacy-data')->assertSuccessful();

        $settings = SiteSetting::current()->fresh();

        $this->assertNotNull($settings->email);
        $this->assertNotNull($settings->youtube_url);
        $this->assertNotNull($settings->address);
    }

    public function test_a_genuinely_custom_value_is_never_overwritten(): void
    {
        SiteSetting::current()->update([
            'email' => 'ozel-eposta@dosttv.com',
            'facebook_url' => 'https://facebook.com/gercek-hesap',
        ]);

        $this->artisan('footer:import-legacy-data')->assertSuccessful();

        $settings = SiteSetting::current()->fresh();

        $this->assertSame('ozel-eposta@dosttv.com', $settings->email);
        $this->assertSame('https://facebook.com/gercek-hesap', $settings->facebook_url);
    }

    public function test_running_twice_creates_no_duplicate_menus_or_items(): void
    {
        Page::create(['title' => 'Yayın İlkeleri', 'slug' => 'dost-tv-yayin-ilkeleri', 'content' => 'x']);
        Page::create(['title' => 'Künye', 'slug' => 'yayinci-kunye-bilgisi', 'content' => 'x']);
        Page::create(['title' => 'KVKK', 'slug' => 'kisisel-verilerin-korunmasi-ve-gizlilik-politikasi', 'content' => 'x']);

        $this->artisan('footer:import-legacy-data')->assertSuccessful();
        $firstPrimaryCount = Menu::where('location', 'footer_primary')->first()->items()->count();
        $firstLegalCount = Menu::where('location', 'footer_legal')->first()->items()->count();

        $this->artisan('footer:import-legacy-data')->assertSuccessful();

        $this->assertSame(1, Menu::where('location', 'footer_primary')->count());
        $this->assertSame(1, Menu::where('location', 'footer_legal')->count());
        $this->assertSame($firstPrimaryCount, Menu::where('location', 'footer_primary')->first()->items()->count());
        $this->assertSame($firstLegalCount, Menu::where('location', 'footer_legal')->first()->items()->count());
    }

    public function test_public_footer_no_longer_lists_every_show_in_menu_page(): void
    {
        // Simulates the exact real-world bug: many unrelated "show_in_menu"
        // pages (including obvious demo/leftover content) must NOT appear
        // in the footer anymore now that it reads from footer_primary /
        // footer_legal menus instead of all Page::show_in_menu records.
        Page::create(['title' => 'Pricing Plan', 'slug' => 'pricing-plan', 'content' => 'x', 'show_in_menu' => true]);
        Page::create(['title' => 'Recover password', 'slug' => 'recover-password', 'content' => 'x', 'show_in_menu' => true]);
        Page::create(['title' => 'Deneme', 'slug' => 'deneme', 'content' => 'x', 'show_in_menu' => true]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertDontSee('Pricing Plan');
        $response->assertDontSee('Recover password');
    }

    public function test_home_page_still_renders_successfully_after_footer_change(): void
    {
        $this->get(route('home'))->assertOk();
    }
}
