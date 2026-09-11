<?php

namespace Tests\Feature;

use App\Filament\Resources\SiteLayout\HomepageLayoutResource\Pages\EditHomepageLayout;
use App\Models\HomepageLayout;
use App\Models\User;
use App\Services\Home\HomepageBlockRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VisualBuilderSectionColorOverrideTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected HomepageLayout $layout;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'role' => 'administrator',
            'is_active' => true,
        ]);

        $this->layout = HomepageLayout::create([
            'name' => 'Test Ana Sayfa Düzeni',
            'is_active' => true,
            'published_sections' => [],
            'draft_sections' => [
                [
                    'uuid' => 'block-sched-1',
                    'block_type' => 'today_schedule',
                    'title' => 'Yayın Akışı',
                    'use_custom_colors' => true,
                    'section_background' => '#0b2545',
                    'section_accent' => '#f43f5e',
                ],
                [
                    'uuid' => 'block-prog-1',
                    'block_type' => 'program_showcase',
                    'title' => 'Güncel Programlar',
                    'use_custom_colors' => false,
                ],
            ],
        ]);
    }

    public function test_resolver_returns_custom_colors_when_enabled(): void
    {
        $schedColors = HomepageBlockRegistry::resolveSectionColors($this->layout->draft_sections[0]);
        $this->assertTrue($schedColors['use_custom_colors']);
        $this->assertEquals('#0b2545', $schedColors['bg']);
        $this->assertEquals('#f43f5e', $schedColors['accent']);
        $this->assertStringContainsString('background-color: #0b2545', $schedColors['shell_style']);
        $this->assertStringContainsString('--color-bg: #0b2545', $schedColors['shell_style']);

        $progColors = HomepageBlockRegistry::resolveSectionColors($this->layout->draft_sections[1]);
        $this->assertFalse($progColors['use_custom_colors']);
        $this->assertEquals('transparent', $progColors['bg']);
        $this->assertStringContainsString('background-color: transparent;', $progColors['shell_style']);
    }

    public function test_visual_builder_updates_section_custom_colors_interactively(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(EditHomepageLayout::class, ['record' => $this->layout->id])
            ->call('selectBlock', 'block-sched-1')
            ->set('draftSections.0.section_background', '#111827')
            ->set('draftSections.0.section_surface', '#1f2937')
            ->call('saveDraft', false);

        $updatedSection = $this->layout->fresh()->draft_sections[0];
        $this->assertEquals('#111827', $updatedSection['section_background']);
        $this->assertEquals('#1f2937', $updatedSection['section_surface']);

        $resolved = HomepageBlockRegistry::resolveSectionColors($updatedSection);
        $this->assertEquals('#111827', $resolved['bg']);
        $this->assertEquals('#1f2937', $resolved['surface']);
    }

    public function test_disabling_custom_colors_reverts_section_to_global_theme(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(EditHomepageLayout::class, ['record' => $this->layout->id])
            ->call('selectBlock', 'block-sched-1')
            ->set('draftSections.0.use_custom_colors', false)
            ->call('saveDraft', false);

        $updatedSection = $this->layout->fresh()->draft_sections[0];
        $this->assertFalse($updatedSection['use_custom_colors']);

        $resolved = HomepageBlockRegistry::resolveSectionColors($updatedSection);
        $this->assertFalse($resolved['use_custom_colors']);
        $this->assertEquals('transparent', $resolved['bg']);
    }
}
