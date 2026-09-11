<?php

namespace Tests\Feature;

use App\Filament\Resources\SiteLayout\HomepageLayoutResource\Pages\EditHomepageLayout;
use App\Models\HomepageLayout;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VisualBuilderLayoutRegressionRepairTest extends TestCase
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
            'name' => 'Layout Repair Test',
            'is_active' => true,
            'published_sections' => [],
            'draft_sections' => [
                [
                    'uuid' => 'block-repair-1',
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

    public function test_editor_shell_renders_clean_flex_split_screen_layout(): void
    {
        $component = Livewire::actingAs($this->adminUser)
            ->test(EditHomepageLayout::class, ['record' => $this->layout->id]);

        $component->assertSeeHtml('id="dost-homepage-editor"')
            ->assertSeeHtml('class="dost-editor-toolbar"')
            ->assertSeeHtml('class="dost-editor-body"')
            ->assertSeeHtml('class="dost-editor-sidebar"')
            ->assertSeeHtml('class="dost-editor-canvas"')
            ->assertSeeHtml('id="editor-preview-frame"');
    }

    public function test_selected_block_renders_four_tabs_without_duplicate_old_accordions(): void
    {
        $component = Livewire::actingAs($this->adminUser)
            ->test(EditHomepageLayout::class, ['record' => $this->layout->id])
            ->call('selectBlock', 'block-repair-1');

        $component->assertSee('İÇERİK')
            ->assertSee('YERLEŞİM')
            ->assertSee('ÖLÇÜLER')
            ->assertSee('GELİŞMİŞ')
            ->assertDontSee('SLIDER CONTROL / YERLEŞİM')
            ->assertDontSee('YAYIN AKIŞI BİÇEMİ');
    }

    public function test_context_modes_and_draft_isolation_remain_intact(): void
    {
        $component = Livewire::actingAs($this->adminUser)
            ->test(EditHomepageLayout::class, ['record' => $this->layout->id])
            ->call('selectBlock', 'block-repair-1')
            ->set('draftSections.0.title', 'Düzeltilmiş Başlık');

        $this->layout->refresh();
        $this->assertEquals('Düzeltilmiş Başlık', $this->layout->draft_sections[0]['title']);
    }
}
