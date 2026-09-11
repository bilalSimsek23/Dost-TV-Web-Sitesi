<?php

namespace Tests\Feature;

use App\Models\HomepageLayout;
use App\Models\Program;
use App\Models\User;
use App\Services\Home\HomepageBlockRegistry;
use Filament\Pages\Dashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VisualBuilderV2ResponsiveIndependentEditingTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected HomepageLayout $layout;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'administrator',
            'is_active' => true,
        ]);

        $this->layout = HomepageLayout::create([
            'name' => 'Test Responsive Layout',
            'is_active' => true,
            'draft_sections' => [
                [
                    'uuid' => 'block-responsive-1',
                    'block_type' => 'content_shelf',
                    'title' => 'Test Shelf',
                    'visible' => true,
                    'display_variant' => 'grid',
                    'desktop_columns' => 4,
                    'gap_size' => 'md',
                ],
            ],
        ]);
    }

    public function test_auto_responsive_map_derives_correct_fallbacks_for_free_desktop_columns_7_8_9(): void
    {
        $map7 = HomepageBlockRegistry::getAutoResponsiveMap(7);
        $this->assertEquals(['tablet' => 4, 'mobile' => 2], $map7);

        $map8 = HomepageBlockRegistry::getAutoResponsiveMap(8);
        $this->assertEquals(['tablet' => 4, 'mobile' => 2], $map8);

        $map9 = HomepageBlockRegistry::getAutoResponsiveMap(9);
        $this->assertEquals(['tablet' => 5, 'mobile' => 3], $map9);

        $resolved7 = HomepageBlockRegistry::resolveResponsiveSettings([
            'desktop_columns' => 7,
            'display_variant' => 'grid',
        ]);
        $this->assertEquals(7, $resolved7['desktop']['columns']);
        $this->assertEquals(4, $resolved7['tablet']['columns']);
        $this->assertEquals(2, $resolved7['mobile']['columns']);

        $resolved8 = HomepageBlockRegistry::resolveResponsiveSettings([
            'desktop_columns' => 8,
            'display_variant' => 'grid',
        ]);
        $this->assertEquals(8, $resolved8['desktop']['columns']);
        $this->assertEquals(4, $resolved8['tablet']['columns']);
        $this->assertEquals(2, $resolved8['mobile']['columns']);
    }

    public function test_updating_desktop_columns_to_7_preserves_desktop_state(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(\App\Filament\Resources\SiteLayout\HomepageLayoutResource\Pages\EditHomepageLayout::class, [
            'record' => $this->layout->id,
        ])
        ->call('selectBlock', 'block-responsive-1')
        ->call('updateDevicePresentation', 'block-responsive-1', 'desktop', 'desktop_columns', 7)
        ->assertDispatched('preview-updated');

        $this->layout->refresh();
        $sections = $this->layout->draft_sections;
        $this->assertEquals(7, $sections[0]['desktop_columns']);
    }

    public function test_tablet_override_does_not_mutate_desktop_or_mobile(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(\App\Filament\Resources\SiteLayout\HomepageLayoutResource\Pages\EditHomepageLayout::class, [
            'record' => $this->layout->id,
        ])
        ->call('selectBlock', 'block-responsive-1')
        ->call('updateDevicePresentation', 'block-responsive-1', 'desktop', 'desktop_columns', 7)
        ->call('updateDevicePresentation', 'block-responsive-1', 'tablet', 'columns', 5)
        ->assertDispatched('preview-updated');

        $this->layout->refresh();
        $sections = $this->layout->draft_sections;

        $this->assertEquals(7, $sections[0]['desktop_columns']);
        $this->assertTrue($sections[0]['custom_responsive']);
        $this->assertEquals(5, $sections[0]['responsive_settings']['tablet']['columns']);

        $resolved = HomepageBlockRegistry::resolveResponsiveSettings($sections[0]);
        $this->assertEquals(7, $resolved['desktop']['columns']);
        $this->assertEquals(5, $resolved['tablet']['columns']);
        $this->assertEquals(2, $resolved['mobile']['columns']); // Mobile remains auto 2
    }

    public function test_mobile_override_does_not_mutate_tablet_or_desktop(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(\App\Filament\Resources\SiteLayout\HomepageLayoutResource\Pages\EditHomepageLayout::class, [
            'record' => $this->layout->id,
        ])
        ->call('selectBlock', 'block-responsive-1')
        ->call('updateDevicePresentation', 'block-responsive-1', 'desktop', 'desktop_columns', 7)
        ->call('updateDevicePresentation', 'block-responsive-1', 'tablet', 'columns', 5)
        ->call('updateDevicePresentation', 'block-responsive-1', 'mobile', 'columns', 3)
        ->assertDispatched('preview-updated');

        $this->layout->refresh();
        $sections = $this->layout->draft_sections;

        $this->assertEquals(7, $sections[0]['desktop_columns']);
        $this->assertEquals(5, $sections[0]['responsive_settings']['tablet']['columns']);
        $this->assertEquals(3, $sections[0]['responsive_settings']['mobile']['columns']);
    }

    public function test_reset_device_override_reverts_single_device_to_auto(): void
    {
        $this->actingAs($this->admin);

        $testComponent = Livewire::test(\App\Filament\Resources\SiteLayout\HomepageLayoutResource\Pages\EditHomepageLayout::class, [
            'record' => $this->layout->id,
        ])
        ->call('selectBlock', 'block-responsive-1')
        ->call('updateDevicePresentation', 'block-responsive-1', 'desktop', 'desktop_columns', 7)
        ->call('updateDevicePresentation', 'block-responsive-1', 'tablet', 'columns', 5)
        ->call('updateDevicePresentation', 'block-responsive-1', 'mobile', 'columns', 3);

        // Reset tablet override
        $testComponent->call('resetDeviceOverride', 'block-responsive-1', 'tablet');

        $this->layout->refresh();
        $sections = $this->layout->draft_sections;

        $this->assertArrayNotHasKey('tablet', $sections[0]['responsive_settings'] ?? []);
        $this->assertEquals(3, $sections[0]['responsive_settings']['mobile']['columns']); // Mobile remains custom 3

        $resolved = HomepageBlockRegistry::resolveResponsiveSettings($sections[0]);
        $this->assertEquals(7, $resolved['desktop']['columns']);
        $this->assertEquals(4, $resolved['tablet']['columns']); // Reverted to auto map (4 for desktop 7)
        $this->assertEquals(3, $resolved['mobile']['columns']);

        // Reset mobile override
        $testComponent->call('resetDeviceOverride', 'block-responsive-1', 'mobile');

        $this->layout->refresh();
        $sections2 = $this->layout->draft_sections;
        $this::assertFalse($sections2[0]['custom_responsive'] ?? false);

        $resolved2 = HomepageBlockRegistry::resolveResponsiveSettings($sections2[0]);
        $this->assertEquals(7, $resolved2['desktop']['columns']);
        $this->assertEquals(4, $resolved2['tablet']['columns']);
        $this->assertEquals(2, $resolved2['mobile']['columns']); // Reverted to auto map (2)
    }

    public function test_shelf_grid_renders_custom_columns_via_css_media_queries(): void
    {
        $program1 = Program::create([
            'name' => 'Program 1',
            'slug' => 'program-1',
            'is_active' => true,
            'show_on_public' => true,
        ]);
        $program2 = Program::create([
            'name' => 'Program 2',
            'slug' => 'program-2',
            'is_active' => true,
            'show_on_public' => true,
        ]);

        $items = collect([$program1, $program2]);

        $renderedHtml = view('components.site.blocks.shelf-grid', [
            'items' => $items,
            'block' => [
                'display_variant' => 'grid',
                'desktop_columns' => 7,
                'custom_responsive' => true,
                'responsive_settings' => [
                    'tablet' => [
                        'display_variant' => 'grid',
                        'columns' => 5,
                        'gap_size' => '14',
                    ],
                    'mobile' => [
                        'display_variant' => 'horizontal_carousel',
                        'columns' => 2,
                        'gap_size' => '8',
                    ],
                ],
            ],
            'cardComponent' => 'site.program-card',
        ])->render();

        // Verify CSS media queries output exact column counts without relying on Tailwind whitelist
        $this->assertStringContainsString('grid-template-columns: repeat(7, minmax(0, 1fr))', $renderedHtml);
        $this->assertStringContainsString('grid-template-columns: repeat(5, minmax(0, 1fr))', $renderedHtml);
        $this->assertStringContainsString('calc((100% - 8px) / 2)', $renderedHtml);
    }
}
