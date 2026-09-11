<?php

namespace Tests\Feature;

use App\Models\HomepageLayout;
use App\Models\SiteSetting;
use App\Services\Home\HomepageBlockRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgressiveGradientAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        SiteSetting::current();

        HomepageLayout::create([
            'name' => 'Aktif Ana Sayfa',
            'is_active' => true,
            'published_sections' => [
                [
                    'uuid' => 'sec-1',
                    'block_type' => 'program_showcase',
                    'title' => 'Güncel Programlar',
                    'use_custom_colors' => false,
                ],
            ],
            'draft_sections' => [],
        ]);
    }

    public function test_homepage_renders_progressive_percentage_gradient_container(): void
    {
        $response = $this->get('/');
        $response->assertSuccessful();

        $response->assertSee('dost-progressive-bg', false);
    }

    public function test_default_section_colors_are_transparent_to_allow_gradient_fill(): void
    {
        $block = [
            'block_type' => 'program_showcase',
            'use_custom_colors' => false,
        ];

        $resolved = HomepageBlockRegistry::resolveSectionColors($block);
        $this->assertEquals('transparent', $resolved['bg']);
        $this->assertStringContainsString('background-color: transparent;', $resolved['shell_style']);
    }

    public function test_footer_maintains_isolated_dark_theme_background(): void
    {
        $response = $this->get('/');
        $response->assertSuccessful();

        $response->assertSee('background-color: var(--color-bg);', false);
    }
}
