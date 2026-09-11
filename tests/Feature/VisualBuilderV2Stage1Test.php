<?php

namespace Tests\Feature;

use App\Filament\Resources\SiteLayout\HomepageLayoutResource\Pages\EditHomepageLayout;
use App\Models\HomepageLayout;
use App\Models\ProgramCollection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VisualBuilderV2Stage1Test extends TestCase
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
            'name' => 'Visual Builder V2 Layout',
            'is_active' => true,
            'published_sections' => [],
            'draft_sections' => [
                [
                    'uuid' => 'block-prog-v2',
                    'block_type' => 'program_showcase',
                    'title' => 'Güncel Programlar',
                    'program_collection_id' => null,
                    'content_limit' => 6,
                    'display_variant' => 'grid',
                    'gap_size' => 16,
                    'padding_y' => 24,
                ],
            ],
        ]);
    }

    public function test_editor_shell_renders_four_tabs_and_header_context(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(EditHomepageLayout::class, ['record' => $this->layout->id])
            ->call('selectBlock', 'block-prog-v2')
            ->assertSee('İÇERİK')
            ->assertSee('YERLEŞİM')
            ->assertSee('ÖLÇÜLER')
            ->assertSee('GELİŞMİŞ')
            ->assertSee('Güncel Programlar')
            ->assertSee('Program Vitrini');
    }

    public function test_custom_numeric_gap_and_padding_inputs_update_draft_state(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(EditHomepageLayout::class, ['record' => $this->layout->id])
            ->call('selectBlock', 'block-prog-v2')
            ->set('draftSections.0.gap_size', 17)
            ->set('draftSections.0.padding_y', 32);

        $this->layout->refresh();
        $this->assertEquals(17, $this->layout->draft_sections[0]['gap_size']);
        $this->assertEquals(32, $this->layout->draft_sections[0]['padding_y']);
    }

    public function test_responsive_override_toggle_retains_device_columns(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(EditHomepageLayout::class, ['record' => $this->layout->id])
            ->call('selectBlock', 'block-prog-v2')
            ->set('draftSections.0.custom_responsive', true)
            ->set('draftSections.0.desktop_columns', 5)
            ->set('draftSections.0.tablet_columns', 3)
            ->set('draftSections.0.mobile_columns', 2);

        $this->layout->refresh();
        $this->assertTrue($this->layout->draft_sections[0]['custom_responsive']);
        $this->assertEquals(5, $this->layout->draft_sections[0]['desktop_columns']);
        $this->assertEquals(3, $this->layout->draft_sections[0]['tablet_columns']);
        $this->assertEquals(2, $this->layout->draft_sections[0]['mobile_columns']);
    }
}
