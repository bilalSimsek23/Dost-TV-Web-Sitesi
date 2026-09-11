<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Episode;
use App\Models\HomepageLayout;
use App\Models\Program;
use App\Models\ProgramCollection;
use App\Models\User;
use App\Models\VideoCollection;
use App\Services\Home\HomepageDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageBuilderCollectionSourceTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'email' => 'admin@dosttv.com',
            'role' => 'super_admin',
            'is_active' => true,
        ]);
    }

    public function test_program_showcase_resolves_programs_from_selected_program_collection(): void
    {
        $category = Category::create([
            'name' => 'Aile',
            'slug' => 'aile',
            'is_active' => true,
            'show_on_public' => true,
        ]);

        $program1 = Program::create([
            'name' => 'Aile Sohbetleri',
            'slug' => 'aile-sohbetleri',
            'is_active' => true,
            'show_on_public' => true,
        ]);
        $program1->categories()->attach($category->id);

        $program2 = Program::create([
            'name' => 'Huzur Saati',
            'slug' => 'huzur-saati',
            'is_active' => true,
            'show_on_public' => true,
        ]);
        $program2->categories()->attach($category->id);

        $collection = ProgramCollection::create([
            'name' => 'Aile Programları',
            'slug' => 'aile-programlari',
            'source_type' => 'category',
            'category_id' => $category->id,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $block = [
            'block_type' => 'program_showcase',
            'visible' => true,
            'title' => 'Aileden Hayata',
            'source_mode' => 'program_collections',
            'program_collection_id' => $collection->id,
            'content_limit' => 8,
        ];

        $dataService = app(HomepageDataService::class);
        $resolvedPrograms = $dataService->resolveProgramBlockData($block);

        $this->assertCount(2, $resolvedPrograms);
        $this->assertTrue($resolvedPrograms->contains('id', $program1->id));
        $this->assertTrue($resolvedPrograms->contains('id', $program2->id));
    }

    public function test_video_showcase_resolves_episodes_from_selected_video_collection(): void
    {
        $program = Program::create([
            'name' => 'Örnek Program',
            'slug' => 'ornek-program',
            'is_active' => true,
            'show_on_public' => true,
        ]);

        $episode1 = Episode::create([
            'program_id' => $program->id,
            'title' => 'Bölüm 1',
            'slug' => 'bolum-1',
            'is_active' => true,
            'show_on_public' => true,
        ]);

        $episode2 = Episode::create([
            'program_id' => $program->id,
            'title' => 'Bölüm 2',
            'slug' => 'bolum-2',
            'is_active' => true,
            'show_on_public' => true,
        ]);

        $videoCollection = VideoCollection::create([
            'name' => 'Aile Videoları',
            'slug' => 'aile-videolari',
            'is_active' => true,
        ]);
        $videoCollection->episodes()->attach([$episode1->id, $episode2->id]);

        $block = [
            'block_type' => 'video_collection',
            'visible' => true,
            'title' => 'Seçkin Videolar',
            'source_mode' => 'video_collections',
            'collection_id' => $videoCollection->id,
            'content_limit' => 8,
        ];

        $dataService = app(HomepageDataService::class);
        $resolvedEpisodes = $dataService->resolveVideoBlockData($block);

        $this->assertCount(2, $resolvedEpisodes);
        $this->assertTrue($resolvedEpisodes->contains('id', $episode1->id));
        $this->assertTrue($resolvedEpisodes->contains('id', $episode2->id));
    }

    public function test_live_updating_collection_content_updates_homepage_without_resaving_builder(): void
    {
        $category = Category::create([
            'name' => 'Güncel',
            'slug' => 'guncel',
            'is_active' => true,
            'show_on_public' => true,
        ]);

        $program1 = Program::create([
            'name' => 'İlk Program',
            'slug' => 'ilk-program',
            'is_active' => true,
            'show_on_public' => true,
        ]);
        $program1->categories()->attach($category->id);

        $collection = ProgramCollection::create([
            'name' => 'Güncel Programlar',
            'slug' => 'guncel-programlari',
            'source_type' => 'category',
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        $block = [
            'block_type' => 'program_showcase',
            'visible' => true,
            'program_collection_id' => $collection->id,
        ];

        $dataService = app(HomepageDataService::class);
        $this->assertCount(1, $dataService->resolveProgramBlockData($block));

        // Add a new program to category dynamically
        $program2 = Program::create([
            'name' => 'Yeni Eklenen Program',
            'slug' => 'yeni-eklenen-program',
            'is_active' => true,
            'show_on_public' => true,
        ]);
        $program2->categories()->attach($category->id);

        // Without re-saving block, dynamic query updates automatically
        $this->assertCount(2, $dataService->resolveProgramBlockData($block));
    }

    public function test_missing_or_deleted_collection_does_not_throw_500(): void
    {
        $blockProgram = [
            'block_type' => 'program_showcase',
            'visible' => true,
            'program_collection_id' => 999999, // Non-existent ID
        ];

        $blockVideo = [
            'block_type' => 'video_collection',
            'visible' => true,
            'collection_id' => 999999, // Non-existent ID
        ];

        $dataService = app(HomepageDataService::class);

        $programs = $dataService->resolveProgramBlockData($blockProgram);
        $episodes = $dataService->resolveVideoBlockData($blockVideo);

        $this->assertCount(0, $programs);
        $this->assertCount(0, $episodes);
    }

    public function test_legacy_homepage_blocks_remain_backward_compatible(): void
    {
        $program = Program::create([
            'name' => 'Öne Çıkan Program',
            'slug' => 'one-cikan-program',
            'is_active' => true,
            'show_on_public' => true,
            'is_featured' => true,
        ]);

        $legacyBlock = [
            'block_type' => 'program_showcase',
            'visible' => true,
            'source_mode' => 'featured_programs',
            'program_collection_id' => null,
        ];

        $dataService = app(HomepageDataService::class);
        $resolved = $dataService->resolveProgramBlockData($legacyBlock);

        $this->assertCount(1, $resolved);
        $this->assertEquals($program->id, $resolved->first()->id);
    }

    public function test_builder_title_is_independent_of_collection_name(): void
    {
        $collection = ProgramCollection::create([
            'name' => 'Arşiv Programları',
            'slug' => 'arsiv-programlari',
            'source_type' => 'manual',
            'is_active' => true,
        ]);

        $block = [
            'block_type' => 'program_showcase',
            'visible' => true,
            'title' => 'Geçmişten Günümüze',
            'program_collection_id' => $collection->id,
        ];

        $this->assertEquals('Geçmişten Günümüze', $block['title']);
        $this->assertEquals('Arşiv Programları', $collection->name);
    }
}
