<?php

namespace Tests\Feature;

use App\Models\FontFamily;
use App\Services\Home\HomepageBlockRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CardRadiusIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        FontFamily::create([
            'name' => 'Instrument Sans',
            'slug' => 'instrument-sans',
            'source_type' => 'google_fonts',
            'is_default' => true,
            'is_active' => true,
        ]);
    }

    public function test_card_radius_resolver_converts_named_and_numeric_options(): void
    {
        $this->assertEquals(0, HomepageBlockRegistry::resolveCardRadiusPx('none'));
        $this->assertEquals(6, HomepageBlockRegistry::resolveCardRadiusPx('sm'));
        $this->assertEquals(12, HomepageBlockRegistry::resolveCardRadiusPx('md'));
        $this->assertEquals(20, HomepageBlockRegistry::resolveCardRadiusPx('lg'));
        $this->assertEquals(28, HomepageBlockRegistry::resolveCardRadiusPx('xl'));
        $this->assertEquals(4, HomepageBlockRegistry::resolveCardRadiusPx(4));
        $this->assertEquals(32, HomepageBlockRegistry::resolveCardRadiusPx(32));
        $this->assertEquals(12, HomepageBlockRegistry::resolveCardRadiusPx(null));
    }

    public function test_program_card_component_renders_dynamic_border_radius(): void
    {
        $viewSharp = $this->blade('<x-site.program-card title="Test Program" card-radius="4" />');
        $viewSharp->assertSee('border-radius: 4px;', false);

        $viewRounded = $this->blade('<x-site.program-card title="Test Program" card-radius="32" />');
        $viewRounded->assertSee('border-radius: 32px;', false);

        $viewDefault = $this->blade('<x-site.program-card title="Test Program" />');
        $viewDefault->assertSee('border-radius: 12px;', false);
    }

    public function test_video_card_component_renders_dynamic_border_radius(): void
    {
        $viewSharp = $this->blade('<x-site.video-card title="Test Video" card-radius="4" />');
        $viewSharp->assertSee('border-radius: 4px;', false);
        $viewSharp->assertSee('border-top-left-radius: 4px;', false);

        $viewRounded = $this->blade('<x-site.video-card title="Test Video" card-radius="32" />');
        $viewRounded->assertSee('border-radius: 32px;', false);
        $viewRounded->assertSee('border-top-left-radius: 32px;', false);

        $viewDefault = $this->blade('<x-site.video-card title="Test Video" />');
        $viewDefault->assertSee('border-radius: 12px;', false);
    }
}
