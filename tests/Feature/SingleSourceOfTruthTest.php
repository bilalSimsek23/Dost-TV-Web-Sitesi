<?php

namespace Tests\Feature;

use App\Filament\Pages\SiteLayout\AppearanceLayoutPage;

use App\Filament\Pages\SiteLayout\HeaderLayoutPage;
use App\Filament\Pages\SiteLayout\HomepageLayoutPage;
use App\Filament\Pages\ThemeSettings;
use App\Filament\Resources\SiteLayout\HomepageLayoutResource\Pages\EditHomepageLayout;
use App\Models\HomepageLayout;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SingleSourceOfTruthTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'administrator']);
    }

    public function test_legacy_pages_are_disabled_from_navigation_and_access(): void
    {
        $this->actingAs($this->admin);

        // Legacy pages must return false for access
        $this->assertFalse(HomepageLayoutPage::canAccess());
        $this->assertFalse(ThemeSettings::canAccess());

        $this->get('/admin/site-layout/homepage')->assertNotFound();
    }

    public function test_visual_builder_draft_save_and_publish_flow(): void
    {
        $layout = HomepageLayout::create([
            'name' => 'Ana Sayfa Düzeni',
            'is_active' => true,
            'draft_sections' => [
                ['key' => 'hero', 'block_type' => 'hero', 'visible' => true],
            ],
            'published_sections' => [
                ['key' => 'hero', 'block_type' => 'hero', 'visible' => true],
            ],
        ]);

        $component = Livewire::actingAs($this->admin)
            ->test(EditHomepageLayout::class, ['record' => $layout->id]);

        // Initially draft equals published -> Site güncel
        $this->assertFalse($component->get('hasUnpublishedChanges'));

        // Update draft sections
        $newDraft = [
            ['key' => 'hero', 'block_type' => 'hero', 'visible' => true],
            ['key' => 'today_schedule', 'block_type' => 'today_schedule', 'visible' => true, 'uuid' => 'sch1'],
        ];

        $component->set('draftSections', $newDraft)
            ->call('saveDraft', true)
            ->assertNotified('Taslak kaydedildi');

        // Check draft updated in DB but published_sections remained unchanged
        $fresh = $layout->fresh();
        $this->assertCount(3, $fresh->draft_sections); // 2 sections + 1 _fixed_settings
        $this->assertCount(1, $fresh->published_sections);

        // Re-test component property -> hasUnpublishedChanges is true
        $component = Livewire::actingAs($this->admin)
            ->test(EditHomepageLayout::class, ['record' => $layout->id]);
        $this->assertTrue($component->get('hasUnpublishedChanges'));

        // Call publish
        $component->call('publish', false)
            ->assertNotified('Değişiklikler yayınlandı');

        // Check published_sections updated in DB
        $fresh = $layout->fresh();
        $this->assertCount(3, $fresh->published_sections);
    }
}
