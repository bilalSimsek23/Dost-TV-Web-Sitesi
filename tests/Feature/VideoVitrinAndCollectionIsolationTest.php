<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Episode;
use App\Models\Program;
use App\Models\VideoCollection;
use App\Services\Home\HomepageDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VideoVitrinAndCollectionIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_video_vitrini_and_full_video_collection_isolation(): void
    {
        $categoryA = Category::create(['name' => 'Sağlık Sohbetleri', 'slug' => 'saglik-sohbetleri', 'is_active' => true]);
        $categoryB = Category::create(['name' => 'Hakikat İklimi', 'slug' => 'hakikat-iklimi', 'is_active' => true]);

        $programA = Program::factory()->create(['name' => 'Sağlık Programı', 'slug' => 'saglik-programi', 'is_active' => true]);
        $programA->categories()->attach($categoryA->id);

        $programB = Program::factory()->create(['name' => 'Hakikat Programı', 'slug' => 'hakikat-programi', 'is_active' => true]);
        $programB->categories()->attach($categoryB->id);

        // Create 50 episodes (25 in Category A, 25 in Category B)
        $episodesA = Episode::factory()->count(25)->create([
            'title' => 'Sağlık Sohbeti Bölümü',
            'program_id' => $programA->id,
            'show_on_public' => true,
            'is_active' => true,
        ]);

        $episodesB = Episode::factory()->count(25)->create([
            'title' => 'Hakikat İklimi Bölümü',
            'program_id' => $programB->id,
            'show_on_public' => true,
            'is_active' => true,
        ]);

        $allEpisodes = $episodesA->concat($episodesB);

        // Create Video Collection with manual binding of all 50 episodes
        $collection = VideoCollection::create([
            'name' => 'Akıştan Videolar',
            'slug' => 'akistan-videolar',
            'source_type' => 'manual',
            'is_active' => true,
        ]);
        $collection->episodes()->attach($allEpisodes->pluck('id')->all());

        // 1. Test Page 1 of Video Collection detail page (default 24 per page)
        $response = $this->get(route('collections.show', $collection));
        $response->assertSuccessful();
        $response->assertSee('Akıştan Videolar');
        $response->assertSee('TÜMÜ (50)');
        $response->assertSee('Sağlık Sohbetleri (25)');
        $response->assertSee('Hakikat İklimi (25)');

        /** @var \Illuminate\Pagination\LengthAwarePaginator $page1Paginator */
        $page1Paginator = $response->viewData('episodes');
        $this->assertEquals(24, $page1Paginator->count());
        $this->assertEquals(50, $page1Paginator->total());
        $this->assertEquals(1, $page1Paginator->currentPage());
        $this->assertEquals(3, $page1Paginator->lastPage());

        // 2. Test Page 2 of Video Collection detail page
        $page2Response = $this->get(route('collections.show', ['collection' => $collection, 'page' => 2]));
        $page2Response->assertSuccessful();
        /** @var \Illuminate\Pagination\LengthAwarePaginator $page2Paginator */
        $page2Paginator = $page2Response->viewData('episodes');
        $this->assertEquals(24, $page2Paginator->count());
        $this->assertEquals(2, $page2Paginator->currentPage());

        // 3. Test Page 3 of Video Collection detail page (2 remaining items)
        $page3Response = $this->get(route('collections.show', ['collection' => $collection, 'page' => 3]));
        $page3Response->assertSuccessful();
        /** @var \Illuminate\Pagination\LengthAwarePaginator $page3Paginator */
        $page3Paginator = $page3Response->viewData('episodes');
        $this->assertEquals(2, $page3Paginator->count());

        // 4. Test Category filter with pagination (?category=saglik-sohbetleri&page=1)
        $categoryAResponse = $this->get(route('collections.show', ['collection' => $collection, 'category' => 'saglik-sohbetleri']));
        $categoryAResponse->assertSuccessful();
        /** @var \Illuminate\Pagination\LengthAwarePaginator $catPaginator */
        $catPaginator = $categoryAResponse->viewData('episodes');
        $this->assertEquals(24, $catPaginator->count());
        $this->assertEquals(25, $catPaginator->total());

        // 5. Test Homepage Video Vitrini block with 8 videos limit / explicit selection
        $homepageBlock = [
            'block_type' => 'video_collection',
            'collection_id' => $collection->id,
            'content_limit' => 8,
        ];

        $homepageService = app(HomepageDataService::class);
        $vitrinEpisodes = $homepageService->resolveVideoBlockData($homepageBlock);

        // Homepage vitrin resolves exactly 8 videos
        $this->assertCount(8, $vitrinEpisodes);
    }
}
