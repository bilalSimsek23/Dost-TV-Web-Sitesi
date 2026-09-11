<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Episode;
use App\Models\Program;
use App\Models\ProgramSeason;
use App\Models\VideoCollection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EpisodeDeepLinkAndPlayerTest extends TestCase
{
    use RefreshDatabase;

    protected Program $program;
    protected Episode $ep1;
    protected Episode $targetEpisode;

    protected function setUp(): void
    {
        parent::setUp();

        $this->program = Program::create([
            'name' => 'Sağlık Sohbetleri',
            'slug' => 'saglik-sohbetleri',
            'is_active' => true,
            'show_on_public' => true,
        ]);

        ProgramSeason::create([
            'program_id' => $this->program->id,
            'season_number' => 1,
            'season_year' => '2026',
            'public_label' => '2026 Sezonu',
        ]);

        $this->ep1 = Episode::create([
            'program_id' => $this->program->id,
            'title' => 'Genel Sağlık Tavsiyeleri',
            'slug' => 'genel-saglik-tavsiyeleri',
            'video_source' => 'youtube',
            'youtube_url' => 'https://www.youtube.com/watch?v=ABC11111111',
            'season_number' => 1,
            'season_year' => '2026',
            'status' => 'published',
            'is_active' => true,
            'show_on_public' => true,
            'view_count' => 100,
        ]);

        $this->targetEpisode = Episode::create([
            'program_id' => $this->program->id,
            'title' => 'Modern Ortopedinin Temel Hedefi',
            'slug' => 'modern-ortopedinin-temel-hedefi',
            'video_source' => 'youtube',
            'youtube_url' => 'https://www.youtube.com/watch?v=XYZ99999999',
            'season_number' => 1,
            'season_year' => '2026',
            'status' => 'published',
            'is_active' => true,
            'show_on_public' => true,
            'view_count' => 9500,
        ]);
    }

    public function test_clicking_episode_card_deep_links_to_program_show_with_target_episode_in_player(): void
    {
        // 1. Verify Video Card component renders deep-link URL containing ?episode={id}
        $cardHtml = view('components.site.video-card', ['episode' => $this->targetEpisode])->render();
        $this->assertStringContainsString(e($this->targetEpisode->public_url), $cardHtml);

        // 2. GET program show page with ?episode={targetEpisode->id}
        $response = $this->get($this->targetEpisode->public_url);

        $response->assertSuccessful();

        // 3. Player iframe should load the target episode's embed URL
        $response->assertSee('https://www.youtube.com/embed/XYZ99999999');

        // 4. Target episode title and label must be highlighted
        $response->assertSee('Modern Ortopedinin Temel Hedefi');
        $response->assertSee('Oynatılıyor');

        // 5. Refresh page with same URL parameter keeps target episode loaded
        $refreshResponse = $this->get($this->targetEpisode->public_url);
        $refreshResponse->assertSuccessful()
            ->assertSee('https://www.youtube.com/embed/XYZ99999999');
    }

    public function test_kart_a_and_kart_b_deep_link_verification(): void
    {
        // Kart A: Söze Yar Olmak
        $progA = Program::create(['name' => 'Söze Yar Olmak', 'slug' => 'soze-yar-olmak', 'is_active' => true, 'show_on_public' => true]);
        $epA = Episode::create(['program_id' => $progA->id, 'title' => 'Peygamberimizin Hayatı', 'slug' => 'peygamberimizin-hayati', 'video_source' => 'youtube', 'youtube_url' => 'https://www.youtube.com/watch?v=KARTA111111', 'status' => 'published', 'is_active' => true, 'show_on_public' => true]);

        // Kart B: Sağlık Sohbetleri
        $progB = Program::create(['name' => 'Diğer Sağlık Sohbetleri', 'slug' => 'diger-saglik-sohbetleri', 'is_active' => true, 'show_on_public' => true]);
        $epB = Episode::create(['program_id' => $progB->id, 'title' => 'Kalp Sağlığı', 'slug' => 'kalp-sagligi', 'video_source' => 'youtube', 'youtube_url' => 'https://www.youtube.com/watch?v=KARTB222222', 'status' => 'published', 'is_active' => true, 'show_on_public' => true]);

        // Kart A GET Request
        $resA = $this->get($epA->public_url);
        $resA->assertSuccessful();
        $resA->assertSee('https://www.youtube.com/embed/KARTA111111');
        $resA->assertSee('Peygamberimizin Hayatı');
        $resA->assertSee('Oynatılıyor');

        // Kart B GET Request
        $resB = $this->get($epB->public_url);
        $resB->assertSuccessful();
        $resB->assertSee('https://www.youtube.com/embed/KARTB222222');
        $resB->assertSee('Kalp Sağlığı');
        $resB->assertSee('Oynatılıyor');
    }

    public function test_invalid_episode_query_param_falls_back_to_default_episode(): void
    {
        $response = $this->get(route('programs.show', [
            'program' => $this->program->slug,
            'episode' => 9999999,
        ]));

        $response->assertSuccessful();
        $response->assertSee('https://www.youtube.com/embed/ABC11111111');
    }

    public function test_most_viewed_video_collection_places_highest_view_count_at_top(): void
    {
        $category = Category::create([
            'name' => 'Tıp ve Sağlık',
            'slug' => 'tip-ve-saglik',
            'is_active' => true,
        ]);
        $this->program->categories()->attach($category->id);

        $lowViewEp = Episode::create([
            'program_id' => $this->program->id,
            'title' => 'Düşük İzlenme 10 Views',
            'slug' => 'dusuk-izlenme',
            'video_source' => 'youtube',
            'youtube_url' => 'https://www.youtube.com/watch?v=LOW11111111',
            'status' => 'published',
            'is_active' => true,
            'show_on_public' => true,
            'view_count' => 10,
        ]);

        $highViewEp = Episode::create([
            'program_id' => $this->program->id,
            'title' => 'En Yüksek İzlenme 50000 Views',
            'slug' => 'yuksek-izlenme',
            'video_source' => 'youtube',
            'youtube_url' => 'https://www.youtube.com/watch?v=HIGH9999999',
            'status' => 'published',
            'is_active' => true,
            'show_on_public' => true,
            'view_count' => 50000,
        ]);

        $collection = VideoCollection::create([
            'name' => 'Sağlık En Çok İzlenenler',
            'slug' => 'saglik-en-cok-izlenenler',
            'source_type' => 'category',
            'category_id' => $category->id,
            'sort_mode' => 'most_viewed',
            'is_active' => true,
        ]);

        $episodes = $collection->resolveEpisodes();

        $this->assertEquals($highViewEp->id, $episodes->first()->id);
        $this->assertEquals(50000, $episodes->first()->view_count);
    }
}
