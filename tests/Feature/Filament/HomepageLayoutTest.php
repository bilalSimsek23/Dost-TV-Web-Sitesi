<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\SiteLayout\HomepageLayoutPage;
use App\Models\Banner;
use App\Models\Program;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\SiteCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageLayoutTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);
    }

    public function test_authorized_user_can_access_homepage_layout_page(): void
    {
        $response = $this->actingAs($this->admin)->get(HomepageLayoutPage::getUrl());

        $response->assertStatus(200);
    }

    public function test_unauthorized_editor_cannot_access_homepage_layout_page(): void
    {
        $editor = User::factory()->create([
            'role' => 'editor',
            'is_active' => true,
        ]);

        $response = $this->actingAs($editor)->get(HomepageLayoutPage::getUrl());

        $response->assertStatus(403);
    }

    public function test_default_homepage_sections_order(): void
    {
        $settings = SiteSetting::current();

        $normalized = $settings->normalized_homepage_sections;

        $this->assertCount(4, $normalized);
        $this->assertEquals('hero', $normalized[0]['key']);
        $this->assertEquals('live_intro', $normalized[1]['key']);
        $this->assertEquals('today_schedule', $normalized[2]['key']);
        $this->assertEquals('featured_programs', $normalized[3]['key']);
        $this->assertTrue($normalized[0]['visible']);
    }

    public function test_malformed_json_fallback_to_defaults(): void
    {
        $settings = SiteSetting::current();
        $settings->homepage_sections = 'invalid_json_string_or_number';
        $settings->save();

        $normalized = $settings->normalized_homepage_sections;

        $this->assertCount(4, $normalized);
        $this->assertEquals('hero', $normalized[0]['key']);
    }

    public function test_missing_section_key_automatically_appended(): void
    {
        $settings = SiteSetting::current();
        $settings->homepage_sections = [
            ['key' => 'featured_programs', 'visible' => false],
        ];
        $settings->save();

        $normalized = $settings->normalized_homepage_sections;

        $this->assertCount(4, $normalized);
        $this->assertEquals('featured_programs', $normalized[0]['key']);
        $this->assertFalse($normalized[0]['visible']);
        $this->assertEquals('hero', $normalized[1]['key']);
        $this->assertTrue($normalized[1]['visible']);
    }

    public function test_unknown_key_ignored(): void
    {
        $settings = SiteSetting::current();
        $settings->homepage_sections = [
            ['key' => 'unknown_section_123', 'visible' => true],
            ['key' => 'hero', 'visible' => true],
        ];
        $settings->save();

        $normalized = $settings->normalized_homepage_sections;

        $keys = array_column($normalized, 'key');
        $this->assertNotContains('unknown_section_123', $keys);
        $this->assertCount(4, $normalized);
    }

    public function test_duplicate_key_normalized(): void
    {
        $settings = SiteSetting::current();
        $settings->homepage_sections = [
            ['key' => 'hero', 'visible' => false],
            ['key' => 'hero', 'visible' => true],
        ];
        $settings->save();

        $normalized = $settings->normalized_homepage_sections;

        $heroOccurrences = array_filter($normalized, fn ($i) => $i['key'] === 'hero');
        $this->assertCount(1, $heroOccurrences);
        $firstHero = reset($heroOccurrences);
        $this->assertFalse($firstHero['visible']);
    }

    public function test_hidden_section_does_not_render_on_public_homepage(): void
    {
        $settings = SiteSetting::current();
        $settings->homepage_sections = [
            ['key' => 'hero', 'visible' => false],
            ['key' => 'live_intro', 'visible' => true],
            ['key' => 'today_schedule', 'visible' => true],
            ['key' => 'featured_programs', 'visible' => false],
        ];
        $settings->save();

        Banner::factory()->create([
            'title' => 'Gizli Manşet Test Bannerı',
            'image' => 'banners/hero.jpg',
            'is_active' => true,
            'content_type' => 'hero',
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertDontSee('Gizli Manşet Test Bannerı');
    }

    public function test_hidden_section_data_is_not_deleted(): void
    {
        $program = Program::factory()->create([
            'name' => 'Silinmeyen Test Programı',
            'is_active' => true,
        ]);

        $settings = SiteSetting::current();
        $settings->homepage_sections = [
            ['key' => 'featured_programs', 'visible' => false],
        ];
        $settings->save();

        $this->assertDatabaseHas('programs', ['id' => $program->id]);
    }

    public function test_homepage_returns_200_when_all_sections_hidden(): void
    {
        $settings = SiteSetting::current();
        $settings->homepage_sections = [
            ['key' => 'hero', 'visible' => false],
            ['key' => 'live_intro', 'visible' => false],
            ['key' => 'today_schedule', 'visible' => false],
            ['key' => 'featured_programs', 'visible' => false],
        ];
        $settings->save();

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Ana sayfa içerikleri güncellenmektedir.');
    }

    public function test_cache_cleared_on_save(): void
    {
        SiteCache::rememberHomeBanners(fn () => collect(['cached_banner']));

        $settings = SiteSetting::current();
        $settings->homepage_sections = [
            ['key' => 'hero', 'visible' => true],
        ];
        $settings->save();

        SiteCache::forgetHomepage();

        $this->get('/');

        $this->assertNotEquals(collect(['cached_banner']), SiteCache::rememberHomeBanners(fn () => collect(['fresh'])));
    }

    public function test_homepage_cache_isolation(): void
    {
        \Illuminate\Support\Facades\Cache::put('site:setting:active', 'general_setting_cache', 3600);
        SiteCache::rememberHomeBanners(fn () => collect(['home_banner_cache']));

        SiteCache::forgetHomepage();

        $this->assertTrue(\Illuminate\Support\Facades\Cache::has('site:setting:active'));
        $this->assertEquals('general_setting_cache', \Illuminate\Support\Facades\Cache::get('site:setting:active'));
    }

    public function test_ttl_comes_from_config(): void
    {
        config(['site.homepage_cache_ttl' => 3600]);

        $this->assertEquals(3600, SiteCache::getHomepageTtl());
    }
}
