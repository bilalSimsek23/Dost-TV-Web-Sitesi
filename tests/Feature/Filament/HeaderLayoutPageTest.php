<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\SiteLayout\HeaderLayoutPage;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class HeaderLayoutPageTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'administrator']);
        Storage::fake('public');

        Menu::firstOrCreate(
            ['location' => 'header_primary'],
            ['name' => 'Ana Üst Menü', 'is_active' => true]
        );
    }

    public function test_authorized_user_can_access_header_layout_page(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/site-layout/header')
            ->assertSuccessful()
            ->assertSee('Header / Üst Alan Yönetimi');
    }

    public function test_header_settings_can_be_saved(): void
    {
        $logo = UploadedFile::fake()->image('custom_logo.png');
        $favicon = UploadedFile::fake()->image('favicon.png');

        Livewire::actingAs($this->admin)
            ->test(HeaderLayoutPage::class)
            ->fillForm([
                'site_name' => 'Dost Medya TV',
                'logo' => $logo,
                'favicon' => $favicon,
                'logo_alt_text' => 'Dost Medya Kurumsal Logo',
                'live_button_text' => 'Şimdi İzle',
                'live_button_is_visible' => true,
                'header_is_sticky' => true,
                'search_is_visible' => false,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $settings = SiteSetting::current();
        $this->assertEquals('Dost Medya TV', $settings->site_name);
        $this->assertEquals('Dost Medya Kurumsal Logo', $settings->logo_alt_text);
        $this->assertEquals('Şimdi İzle', $settings->live_button_text);
        $this->assertTrue($settings->live_button_is_visible);
        $this->assertTrue($settings->header_is_sticky);
        $this->assertFalse($settings->search_is_visible);
        $this->assertNotNull($settings->logo);
        $this->assertNotNull($settings->favicon);
    }

    public function test_live_button_can_be_hidden(): void
    {
        Livewire::actingAs($this->admin)
            ->test(HeaderLayoutPage::class)
            ->fillForm([
                'site_name' => 'Dost TV',
                'live_button_is_visible' => false,
                'live_button_text' => 'Canlı Yayın',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertFalse(SiteSetting::current()->live_button_is_visible);
    }

    public function test_public_header_renders_saved_logo_or_fallback(): void
    {
        // 1. Without uploaded logo -> Fallback gradient icon renders
        $this->get(route('home'))
            ->assertSuccessful()
            ->assertSee('Dost TV', false)
            ->assertSee('Canlı İzle');

        // 2. With uploaded logo
        SiteSetting::current()->update([
            'logo' => 'branding/test_logo.png',
            'logo_alt_text' => 'Ozel Dost Logo',
            'live_button_text' => 'Tıkla İzle',
        ]);

        $this->get(route('home'))
            ->assertSuccessful()
            ->assertSee('Ozel Dost Logo')
            ->assertSee('Tıkla İzle');
    }
}
