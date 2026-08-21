<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\LiveBroadcastPage;
use App\Filament\Pages\SiteLayout\HeaderLayoutPage;
use App\Filament\Pages\SiteSettings;
use App\Models\Role;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SiteSettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $admin;
    private User $editor;

    protected function setUp(): void
    {
        parent::setUp();

        $superAdminRole = Role::firstOrCreate(
            ['slug' => 'super_admin'],
            ['name' => 'Süper Yönetici', 'base_role' => 'super_admin', 'description' => 'Tam yetkili', 'is_system' => true]
        );

        $adminRole = Role::firstOrCreate(
            ['slug' => 'administrator'],
            ['name' => 'Yönetici', 'base_role' => 'administrator', 'description' => 'Yönetici', 'is_system' => true]
        );

        $editorRole = Role::firstOrCreate(
            ['slug' => 'editor'],
            ['name' => 'Editör', 'base_role' => 'editor', 'description' => 'İçerik editörü', 'is_system' => true]
        );

        $this->superAdmin = User::factory()->create([
            'role_id' => $superAdminRole->id,
            'is_active' => true,
        ]);

        $this->admin = User::factory()->create([
            'role_id' => $adminRole->id,
            'is_active' => true,
        ]);

        $this->editor = User::factory()->create([
            'role_id' => $editorRole->id,
            'is_active' => true,
        ]);

        SiteSetting::current();
    }

    public function test_super_admin_can_access_site_settings(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(SiteSettings::getUrl())
            ->assertSuccessful();
    }

    public function test_administrator_and_editor_cannot_access_site_settings(): void
    {
        $this->actingAs($this->admin)
            ->get(SiteSettings::getUrl())
            ->assertForbidden();

        $this->actingAs($this->editor)
            ->get(SiteSettings::getUrl())
            ->assertForbidden();
    }

    public function test_former_duplicate_fields_are_not_rendered_in_site_settings_form(): void
    {
        $component = Livewire::actingAs($this->superAdmin)
            ->test(SiteSettings::class);

        // Verify the 6 duplicate fields are NOT rendered as form fields
        $component->assertFormFieldDoesNotExist('site_name')
            ->assertFormFieldDoesNotExist('logo')
            ->assertFormFieldDoesNotExist('live_tv_type')
            ->assertFormFieldDoesNotExist('live_tv_url')
            ->assertFormFieldDoesNotExist('radio_name')
            ->assertFormFieldDoesNotExist('radio_stream_url');

        // Verify new system & SEO fields ARE rendered
        $component->assertFormFieldExists('title_suffix')
            ->assertFormFieldExists('system_email')
            ->assertFormFieldExists('default_og_image')
            ->assertFormFieldExists('default_meta_description')
            ->assertFormFieldExists('search_engine_indexing')
            ->assertFormFieldExists('canonical_url_mode')
            ->assertFormFieldExists('google_analytics_id')
            ->assertFormFieldExists('google_tag_manager_id')
            ->assertFormFieldExists('google_site_verification')
            ->assertFormFieldExists('custom_head_code')
            ->assertFormFieldExists('custom_body_code');
    }

    public function test_site_settings_form_can_be_saved_successfully(): void
    {
        Livewire::actingAs($this->superAdmin)
            ->test(SiteSettings::class)
            ->fillForm([
                'title_suffix' => '| DOST TV HD',
                'system_email' => 'admin@dosttv.com',
                'default_meta_description' => 'Test global SEO meta açıklaması.',
                'search_engine_indexing' => false,
                'canonical_url_mode' => 'domain_root',
                'google_analytics_id' => 'G-ABC1234567',
                'google_tag_manager_id' => 'GTM-XYZ9876',
                'google_site_verification' => 'google-test-token-123',
                'custom_head_code' => '<meta name="yandex-verification" content="test">',
                'custom_body_code' => '<!-- Test body script -->',
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified('Site ayarları başarıyla kaydedildi.');

        $setting = SiteSetting::current();
        $this->assertSame('| DOST TV HD', $setting->title_suffix);
        $this->assertSame('admin@dosttv.com', $setting->system_email);
        $this->assertSame('Test global SEO meta açıklaması.', $setting->default_meta_description);
        $this->assertFalse($setting->search_engine_indexing);
        $this->assertSame('domain_root', $setting->canonical_url_mode);
        $this->assertSame('G-ABC1234567', $setting->google_analytics_id);
        $this->assertSame('GTM-XYZ9876', $setting->google_tag_manager_id);
        $this->assertSame('google-test-token-123', $setting->google_site_verification);
        $this->assertSame('<meta name="yandex-verification" content="test">', $setting->custom_head_code);
        $this->assertSame('<!-- Test body script -->', $setting->custom_body_code);
    }

    public function test_header_layout_page_remains_intact_and_functional(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(HeaderLayoutPage::getUrl())
            ->assertSuccessful();

        Livewire::actingAs($this->superAdmin)
            ->test(HeaderLayoutPage::class)
            ->assertFormFieldExists('site_name')
            ->assertFormFieldExists('logo')
            ->assertFormFieldExists('favicon')
            ->assertFormFieldExists('logo_alt_text')
            ->assertFormFieldExists('header_is_sticky')
            ->assertFormFieldExists('search_is_visible');
    }

    public function test_live_broadcast_page_remains_intact_and_functional(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(LiveBroadcastPage::getUrl())
            ->assertSuccessful();

        Livewire::actingAs($this->superAdmin)
            ->test(LiveBroadcastPage::class)
            ->assertFormFieldExists('live_tv_type', 'tvForm')
            ->assertFormFieldExists('live_tv_url', 'tvForm')
            ->assertFormFieldExists('radio_name', 'fmForm')
            ->assertFormFieldExists('radio_stream_url', 'fmForm');
    }
}
