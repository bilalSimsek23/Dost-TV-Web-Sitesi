<?php

namespace Tests\Feature;

use App\Filament\Resources\VideoCollections\Pages\EditVideoCollection;
use App\Models\Category;
use App\Models\Episode;
use App\Models\Program;
use App\Models\ProgramCollection;
use App\Models\User;
use App\Models\VideoCollection;
use App\Support\CollectionPageDesignResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CollectionDetailGridNormalizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected VideoCollection $videoCollection;

    protected ProgramCollection $programCollection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'email' => 'admin@dosttv.com',
            'role' => 'administrator',
        ]);

        $category = Category::create(['name' => 'Din ve Hayat', 'slug' => 'din-ve-hayat']);
        $program = Program::create(['name' => 'Akıştan Program', 'slug' => 'akistan-program', 'category_id' => $category->id, 'status' => 'active']);
        $episodes = collect();
        for ($i = 1; $i <= 6; $i++) {
            $episodes->push(Episode::create([
                'program_id' => $program->id,
                'title' => "Bölüm {$i}",
                'slug' => "bolum-{$i}",
                'youtube_video_id' => "video_{$i}",
                'published_at' => now()->subDays($i),
                'is_published' => true,
            ]));
        }

        $this->videoCollection = VideoCollection::create([
            'name' => 'Akıştan Videolar',
            'slug' => 'akistan-videolar',
            'is_active' => true,
            'source_type' => 'manual',
            'public_settings' => [
                'display_variant' => 'grid',
                'desktop_columns' => 4,
                'tablet_columns' => 3,
                'mobile_columns' => 1,
                'gap_size' => 'medium',
                'show_description' => true,
            ],
        ]);
        $this->videoCollection->episodes()->attach($episodes->pluck('id'));

        $this->programCollection = ProgramCollection::create([
            'name' => 'Öne Çıkan Programlar',
            'slug' => 'one-cikan-programlar',
            'is_active' => true,
            'source_type' => 'manual',
            'public_settings' => [
                'display_variant' => 'grid',
                'desktop_columns' => 5,
                'tablet_columns' => 3,
                'mobile_columns' => 2,
                'gap_size' => 'medium',
                'show_description' => true,
            ],
        ]);
        $this->programCollection->programs()->attach([$program->id]);
    }

    public function test_collection_page_design_resolver_generates_correct_responsive_grid_classes(): void
    {
        $videoResolved = CollectionPageDesignResolver::resolve($this->videoCollection, 'video');
        $this->assertEquals(4, $videoResolved['desktop_columns']);
        $this->assertEquals(3, $videoResolved['tablet_columns']);
        $this->assertEquals(1, $videoResolved['mobile_columns']);
        $this->assertStringContainsString('lg:grid-cols-4', $videoResolved['grid_class']);
        $this->assertStringContainsString('sm:grid-cols-3', $videoResolved['grid_class']);
        $this->assertStringContainsString('grid-cols-1', $videoResolved['grid_class']);

        $programResolved = CollectionPageDesignResolver::resolve($this->programCollection, 'program');
        $this->assertEquals(5, $programResolved['desktop_columns']);
        $this->assertStringContainsString('lg:grid-cols-5', $programResolved['grid_class']);
        $this->assertStringContainsString('grid-cols-2', $programResolved['grid_class']);
    }

    public function test_video_collection_detail_page_renders_clean_responsive_grid(): void
    {
        $response = $this->get('/koleksiyonlar/akistan-videolar');
        $response->assertSuccessful()
            ->assertSee('Akıştan Videolar')
            ->assertSee('lg:grid-cols-4', false)
            ->assertSee('sm:grid-cols-3', false)
            ->assertSee('grid-cols-1', false);
    }

    public function test_program_collection_detail_page_renders_clean_responsive_grid(): void
    {
        $response = $this->get('/program-koleksiyonlari/one-cikan-programlar');
        $response->assertSuccessful()
            ->assertSee('Öne Çıkan Programlar')
            ->assertSee('lg:grid-cols-5', false)
            ->assertSee('sm:grid-cols-3', false)
            ->assertSee('grid-cols-2', false);
    }

    public function test_admin_panel_desktop_columns_update_reflects_on_public_detail_page(): void
    {
        // Change desktop_columns to 5 on model public_settings
        $this->videoCollection->update([
            'public_settings' => [
                'display_variant' => 'grid',
                'desktop_columns' => 5,
                'tablet_columns' => 3,
                'mobile_columns' => 1,
                'gap_size' => 'medium',
                'page_size' => 'all',
            ],
        ]);

        $response = $this->get('/koleksiyonlar/akistan-videolar');
        $response->assertSuccessful()
            ->assertSee('lg:grid-cols-5', false);
    }

    public function test_homepage_shelves_and_video_vitrini_remain_unaffected(): void
    {
        $response = $this->get('/');
        $response->assertSuccessful();
    }
}
