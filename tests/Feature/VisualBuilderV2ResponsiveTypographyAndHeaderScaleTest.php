<?php

namespace Tests\Feature;

use App\Filament\Resources\SiteLayout\HomepageLayoutResource\Pages\EditHomepageLayout;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\Home\HomepageBlockRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VisualBuilderV2ResponsiveTypographyAndHeaderScaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_auto_typography_map_derives_proportioned_defaults(): void
    {
        $block = [
            'heading_size' => 28,
            'subtitle_size' => 16,
            'cta_size' => 15,
            'card_title_size' => 16,
            'meta_size' => 13,
            'padding_y' => 'md',
        ];

        $autoMap = HomepageBlockRegistry::getAutoTypographyMap($block);

        $this->assertEquals(28, $autoMap['desktop']['heading_size']);
        $this->assertEquals(24, $autoMap['tablet']['heading_size']);
        $this->assertEquals(20, $autoMap['mobile']['heading_size']);

        $this->assertEquals(16, $autoMap['desktop']['card_title_size']);
        $this->assertEquals(15, $autoMap['tablet']['card_title_size']);
        $this->assertEquals(14, $autoMap['mobile']['card_title_size']);

        $this->assertEquals(15, $autoMap['desktop']['schedule_time_size']);
        $this->assertEquals(14, $autoMap['tablet']['schedule_time_size']);
        $this->assertEquals(13, $autoMap['mobile']['schedule_time_size']);
    }

    public function test_resolve_responsive_settings_supports_device_overrides(): void
    {
        $block = [
            'uuid' => 'test-block-1',
            'desktop_columns' => 4,
            'heading_size' => 28,
            'custom_responsive' => true,
            'responsive_settings' => [
                'mobile' => [
                    'columns' => 1,
                    'heading_size' => 18,
                    'section_padding_top' => 12,
                    'schedule_time_size' => 12,
                ],
            ],
        ];

        $resolved = HomepageBlockRegistry::resolveResponsiveSettings($block);

        $this->assertTrue($resolved['custom_responsive']);
        $this->assertEquals(28, $resolved['desktop']['heading_size']);
        $this->assertEquals(18, $resolved['mobile']['heading_size']);
        $this->assertEquals(12, $resolved['mobile']['section_padding_top']);
        $this->assertEquals(12, $resolved['mobile']['schedule_time_size']);
    }

    public function test_site_setting_header_responsive_settings_normalization(): void
    {
        $defaults = SiteSetting::getDefaultHeaderResponsiveSettings();

        $this->assertEquals(80, $defaults['desktop']['header_height']);
        $this->assertEquals(72, $defaults['tablet']['header_height']);
        $this->assertEquals(60, $defaults['mobile']['header_height']);

        $setting = new SiteSetting([
            'header_responsive_settings' => [
                'mobile' => [
                    'header_height' => 54,
                    'logo_width' => 95,
                ],
            ],
        ]);

        $normalized = $setting->normalized_header_responsive_settings;

        $this->assertEquals(80, $normalized['desktop']['header_height']);
        $this->assertEquals(54, $normalized['mobile']['header_height']);
        $this->assertEquals(95, $normalized['mobile']['logo_width']);
    }

    public function test_livewire_update_device_presentation_updates_typography_and_spacing(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
        ]);

        $blockUuid = 'block-typo-test';
        $sections = [
            [
                'uuid' => $blockUuid,
                'block_type' => 'content_shelf',
                'title' => 'Test Shelf',
                'desktop_columns' => 4,
                'heading_size' => 28,
            ],
        ];

        $layout = \App\Models\HomepageLayout::create([
            'name' => 'Test Layout',
            'is_active' => true,
            'draft_sections' => $sections,
        ]);

        Livewire::actingAs($admin)
            ->test(EditHomepageLayout::class, ['record' => $layout->id])
            ->call('updateDevicePresentation', $blockUuid, 'mobile', 'heading_size', 18)
            ->call('updateDevicePresentation', $blockUuid, 'mobile', 'section_padding_top', 14);

        $freshLayout = $layout->fresh();
        $draft = $freshLayout->draft_sections[0];

        $this->assertTrue($draft['custom_responsive']);
        $this->assertEquals(18, $draft['responsive_settings']['mobile']['heading_size']);
        $this->assertEquals(14, $draft['responsive_settings']['mobile']['section_padding_top']);
    }
}
