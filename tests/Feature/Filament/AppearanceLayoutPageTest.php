<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\SiteLayout\AppearanceLayoutPage;
use App\Models\FontFamily;
use App\Models\SiteSetting;
use App\Models\ThemeSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AppearanceLayoutPageTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected FontFamily $font;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'administrator']);
        $this->font = FontFamily::firstOrCreate(
            ['slug' => 'inter'],
            [
                'name' => 'Inter',
                'source_type' => 'google',
                'is_active' => true,
                'is_default' => true,
            ]
        );

        ThemeSetting::firstOrCreate(
            ['key' => 'accessibility.reduced_motion_support'],
            ['group' => 'accessibility', 'value' => '0', 'value_type' => 'boolean', 'label' => 'Reduced Motion']
        );
    }

    public function test_authorized_user_can_access_appearance_layout_page(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/site-layout/appearance')
            ->assertSuccessful()
            ->assertSee('Görünüm Yönetimi');
    }

    public function test_active_font_and_appearance_settings_can_be_saved(): void
    {
        $newFont = FontFamily::create([
            'name' => 'Roboto',
            'slug' => 'roboto',
            'source_type' => 'google',
            'is_active' => true,
            'is_default' => false,
        ]);

        Livewire::actingAs($this->admin)
            ->test(AppearanceLayoutPage::class)
            ->fillForm([
                'active_font_id' => $newFont->id,
                'theme_mode' => 'dark',
                'reduced_motion' => true,
                'custom_css' => 'body { color: #f8fafc; }',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue($newFont->fresh()->is_default);
        $this->assertEquals('body { color: #f8fafc; }', SiteSetting::current()->custom_css);
        $this->assertEquals('1', ThemeSetting::where('key', 'accessibility.reduced_motion_support')->first()->value);
    }

    public function test_custom_css_rejects_script_tags(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AppearanceLayoutPage::class)
            ->fillForm([
                'active_font_id' => $this->font->id,
                'custom_css' => '<script>alert("xss")</script>',
            ])
            ->call('save');

        $this->assertNotEquals('<script>alert("xss")</script>', SiteSetting::current()->custom_css);
    }
}
