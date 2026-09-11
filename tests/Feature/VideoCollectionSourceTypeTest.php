<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Episode;
use App\Models\Program;
use App\Models\ScheduleTemplate;
use App\Models\ScheduleTemplateItem;
use App\Models\User;
use App\Models\VideoCollection;
use App\Services\Home\HomepageDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VideoCollectionSourceTypeTest extends TestCase
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

    public function test_legacy_manual_video_collection_works_as_before(): void
    {
        $program = Program::create([
            'name' => 'Program 1',
            'slug' => 'program-1',
            'is_active' => true,
            'show_on_public' => true,
        ]);

        $episode = Episode::create([
            'program_id' => $program->id,
            'title' => 'Manuel Bölüm',
            'slug' => 'manuel-bolum',
            'is_active' => true,
            'show_on_public' => true,
        ]);

        $collection = VideoCollection::create([
            'name' => 'Manuel Koleksiyon',
            'slug' => 'manuel-koleksiyon',
            'source_type' => 'manual',
            'is_active' => true,
        ]);
        $collection->episodes()->attach($episode->id);

        $resolved = $collection->resolveEpisodes();

        $this->assertCount(1, $resolved);
        $this->assertEquals($episode->id, $resolved->first()->id);
    }

    public function test_category_video_collection_automatically_includes_videos_and_updates_dynamically(): void
    {
        $category = Category::create([
            'name' => 'Sağlık',
            'slug' => 'saglik',
            'is_active' => true,
            'show_on_public' => true,
        ]);

        $program = Program::create([
            'name' => 'Sağlık Saati',
            'slug' => 'saglik-saati',
            'is_active' => true,
            'show_on_public' => true,
        ]);
        $program->categories()->attach($category->id);

        $episode1 = Episode::create([
            'program_id' => $program->id,
            'title' => 'Sağlık Bölüm 1',
            'slug' => 'saglik-bolum-1',
            'is_active' => true,
            'show_on_public' => true,
            'aired_at' => now()->subDays(2),
        ]);

        $collection = VideoCollection::create([
            'name' => 'Sağlık Videoları',
            'slug' => 'saglik-videolari',
            'source_type' => 'category',
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        // 1. Initial resolution includes episode1
        $resolved = $collection->resolveEpisodes();
        $this->assertCount(1, $resolved);
        $this->assertEquals($episode1->id, $resolved->first()->id);
        $this->assertEquals(0, $collection->episodes()->count(), 'Automatic videos must NOT be written to pivot table.');

        // 2. Attaching a new episode dynamically updates collection result
        $episode2 = Episode::create([
            'program_id' => $program->id,
            'title' => 'Sağlık Bölüm 2',
            'slug' => 'saglik-bolum-2',
            'is_active' => true,
            'show_on_public' => true,
            'aired_at' => now()->subDay(),
        ]);

        $resolvedUpdated = $collection->resolveEpisodes();
        $this->assertCount(2, $resolvedUpdated);

        // 3. Removing category connection removes videos from collection
        $program->categories()->detach($category->id);
        $resolvedEmpty = $collection->resolveEpisodes();
        $this->assertCount(0, $resolvedEmpty);
    }

    public function test_sorting_modes_latest_oldest_random(): void
    {
        $category = Category::create(['name' => 'Tarih', 'slug' => 'tarih', 'is_active' => true]);
        $program = Program::create(['name' => 'Tarih Programı', 'slug' => 'tarih-prog', 'is_active' => true, 'show_on_public' => true]);
        $program->categories()->attach($category->id);

        $oldEpisode = Episode::create([
            'program_id' => $program->id,
            'title' => 'Eski Video',
            'slug' => 'eski-video',
            'is_active' => true,
            'show_on_public' => true,
            'aired_at' => '2025-01-01',
        ]);

        $newEpisode = Episode::create([
            'program_id' => $program->id,
            'title' => 'Yeni Video',
            'slug' => 'yeni-video',
            'is_active' => true,
            'show_on_public' => true,
            'aired_at' => '2026-08-01',
        ]);

        // Latest
        $latestCol = VideoCollection::create([
            'name' => 'En Yeni Koleksiyon',
            'slug' => 'en-yeni',
            'source_type' => 'category',
            'category_id' => $category->id,
            'sort_mode' => 'latest',
            'is_active' => true,
        ]);
        $resolvedLatest = $latestCol->resolveEpisodes();
        $this->assertEquals($newEpisode->id, $resolvedLatest->first()->id);

        // Oldest
        $oldestCol = VideoCollection::create([
            'name' => 'En Eski Koleksiyon',
            'slug' => 'en-eski',
            'source_type' => 'category',
            'category_id' => $category->id,
            'sort_mode' => 'oldest',
            'is_active' => true,
        ]);
        $resolvedOldest = $oldestCol->resolveEpisodes();
        $this->assertEquals($oldEpisode->id, $resolvedOldest->first()->id);

        // Random (Deterministic on same day)
        $randomCol = VideoCollection::create([
            'name' => 'Karışık Koleksiyon',
            'slug' => 'karisik',
            'source_type' => 'category',
            'category_id' => $category->id,
            'sort_mode' => 'random',
            'is_active' => true,
        ]);
        $random1 = $randomCol->resolveEpisodes()->pluck('id')->all();
        $random2 = $randomCol->resolveEpisodes()->pluck('id')->all();
        $this->assertEquals($random1, $random2, 'Random shuffle must be deterministic on the same day.');
    }

    public function test_featured_source_resolves_episodes_of_active_period_live_programs(): void
    {
        $template = ScheduleTemplate::create([
            'name' => 'Canlı Yayın Şablonu',
            'status' => 'published',
            'is_active' => true,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addMonth(),
        ]);

        $liveProgram = Program::create([
            'name' => 'Canlı Yayınlanan Program',
            'slug' => 'canli-prog',
            'is_active' => true,
            'show_on_public' => true,
        ]);

        $tapeProgram = Program::create([
            'name' => 'Bant Yayın Programı',
            'slug' => 'bant-prog',
            'is_active' => true,
            'show_on_public' => true,
        ]);

        $legacyFeaturedProgram = Program::create([
            'name' => 'Yayın Akışında Olmayan Yıldızlı Program',
            'slug' => 'yildizli-prog',
            'is_active' => true,
            'show_on_public' => true,
            'is_featured' => true,
        ]);

        // Live schedule item
        ScheduleTemplateItem::create([
            'schedule_template_id' => $template->id,
            'program_id' => $liveProgram->id,
            'day_of_week' => 1,
            'start_time' => '20:00:00',
            'end_time' => '21:30:00',
            'is_live' => true,
            'is_active' => true,
        ]);

        // Non-live schedule item
        ScheduleTemplateItem::create([
            'schedule_template_id' => $template->id,
            'program_id' => $tapeProgram->id,
            'day_of_week' => 2,
            'start_time' => '14:00:00',
            'end_time' => '15:00:00',
            'is_live' => false,
            'is_active' => true,
        ]);

        $liveEp = Episode::create([
            'program_id' => $liveProgram->id,
            'title' => 'Canlı Program Bölümü',
            'slug' => 'canli-prog-bolumu',
            'is_active' => true,
            'show_on_public' => true,
        ]);

        $tapeEp = Episode::create([
            'program_id' => $tapeProgram->id,
            'title' => 'Bant Program Bölümü',
            'slug' => 'bant-prog-bolumu',
            'is_active' => true,
            'show_on_public' => true,
        ]);

        $legacyEp = Episode::create([
            'program_id' => $legacyFeaturedProgram->id,
            'title' => 'Yıldızlı Program Bölümü',
            'slug' => 'yildizli-prog-bolumu',
            'is_active' => true,
            'show_on_public' => true,
        ]);

        $collection = VideoCollection::create([
            'name' => 'Öne Çıkan Videolar',
            'slug' => 'one-cikan-videolar',
            'source_type' => 'featured',
            'is_active' => true,
        ]);

        $resolved = $collection->resolveEpisodes();

        // Must ONLY resolve episodes of active period LIVE programs
        $this->assertCount(1, $resolved);
        $this->assertEquals($liveEp->id, $resolved->first()->id);
    }

    public function test_active_period_program_videos_source_type_fetches_active_schedule_program_episodes(): void
    {
        $template = ScheduleTemplate::create([
            'name' => 'Yaz Şablonu',
            'status' => 'published',
            'is_active' => true,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addMonth(),
        ]);

        $program = Program::create([
            'name' => 'Yayın Akışı Programı',
            'slug' => 'yayin-akisi-prog',
            'is_active' => true,
            'show_on_public' => true,
        ]);

        ScheduleTemplateItem::create([
            'schedule_template_id' => $template->id,
            'program_id' => $program->id,
            'day_of_week' => 1,
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'is_active' => true,
        ]);

        $episode = Episode::create([
            'program_id' => $program->id,
            'title' => 'Akış Programı Bölümü',
            'slug' => 'akis-prog-bolumu',
            'is_active' => true,
            'show_on_public' => true,
        ]);

        $collection = VideoCollection::create([
            'name' => 'Yayın Dönemi Videoları',
            'slug' => 'yayin-donemi-videolari',
            'source_type' => 'active_period_program_videos',
            'is_active' => true,
        ]);

        $resolved = $collection->resolveEpisodes();

        $this->assertCount(1, $resolved);
        $this->assertEquals($episode->id, $resolved->first()->id);
    }

    public function test_hybrid_source_type_combines_pinned_and_auto_episodes_without_duplicates(): void
    {
        $category = Category::create(['name' => 'Eğitim', 'slug' => 'egitim', 'is_active' => true]);
        $program = Program::create(['name' => 'Eğitim Programı', 'slug' => 'egitim-prog', 'is_active' => true, 'show_on_public' => true]);
        $program->categories()->attach($category->id);

        $pinnedEp = Episode::create([
            'program_id' => $program->id,
            'title' => 'Sabitlenen Bölüm',
            'slug' => 'sabitlenen-bolum',
            'is_active' => true,
            'show_on_public' => true,
        ]);

        $autoEp = Episode::create([
            'program_id' => $program->id,
            'title' => 'Otomatik Bölüm',
            'slug' => 'otomatik-bolum',
            'is_active' => true,
            'show_on_public' => true,
        ]);

        $collection = VideoCollection::create([
            'name' => 'Hibrit Koleksiyon',
            'slug' => 'hibrit-koleksiyon',
            'source_type' => 'hybrid',
            'category_id' => $category->id,
            'is_active' => true,
        ]);
        $collection->episodes()->attach($pinnedEp->id);

        $resolved = $collection->resolveEpisodes();

        $this->assertCount(2, $resolved);
        $this->assertEquals($pinnedEp->id, $resolved->first()->id, 'Pinned episode must come first.');
        $this->assertEquals($autoEp->id, $resolved->last()->id);
    }

    public function test_homepage_builder_resolves_selected_video_collection_via_homepage_data_service(): void
    {
        $category = Category::create(['name' => 'Kültür', 'slug' => 'kultur', 'is_active' => true]);
        $program = Program::create(['name' => 'Kültür Programı', 'slug' => 'kultur-prog', 'is_active' => true, 'show_on_public' => true]);
        $program->categories()->attach($category->id);

        $episode = Episode::create([
            'program_id' => $program->id,
            'title' => 'Kültür Bölüm',
            'slug' => 'kultur-bolum',
            'is_active' => true,
            'show_on_public' => true,
        ]);

        $collection = VideoCollection::create([
            'name' => 'Kültür Videoları',
            'slug' => 'kultur-videolari',
            'source_type' => 'category',
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        $block = [
            'block_type' => 'video_collection',
            'visible' => true,
            'source_mode' => 'video_collections',
            'collection_id' => $collection->id,
            'content_limit' => 8,
        ];

        $dataService = app(HomepageDataService::class);
        $resolved = $dataService->resolveVideoBlockData($block);

        $this->assertCount(1, $resolved);
        $this->assertEquals($episode->id, $resolved->first()->id);
    }

    public function test_featured_and_active_period_program_videos_resolve_distinct_pools_and_featured_is_live_only(): void
    {
        $template = ScheduleTemplate::create([
            'name' => 'Fark Testi Şablonu',
            'status' => 'published',
            'is_active' => true,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addMonth(),
        ]);

        $liveProg = Program::create([
            'name' => 'Canlı Program',
            'slug' => 'canli-prog-fark',
            'is_active' => true,
            'show_on_public' => true,
        ]);

        $tapeProg = Program::create([
            'name' => 'Bant Program',
            'slug' => 'bant-prog-fark',
            'is_active' => true,
            'show_on_public' => true,
        ]);

        ScheduleTemplateItem::create([
            'schedule_template_id' => $template->id,
            'program_id' => $liveProg->id,
            'day_of_week' => 1,
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'is_live' => true,
            'is_active' => true,
        ]);

        ScheduleTemplateItem::create([
            'schedule_template_id' => $template->id,
            'program_id' => $tapeProg->id,
            'day_of_week' => 2,
            'start_time' => '12:00:00',
            'end_time' => '13:00:00',
            'is_live' => false,
            'is_active' => true,
        ]);

        $liveEp = Episode::create([
            'program_id' => $liveProg->id,
            'title' => 'Canlı Bölüm',
            'slug' => 'canli-bolum-fark',
            'is_active' => true,
            'show_on_public' => true,
        ]);

        $tapeEp = Episode::create([
            'program_id' => $tapeProg->id,
            'title' => 'Bant Bölüm',
            'slug' => 'bant-bolum-fark',
            'is_active' => true,
            'show_on_public' => true,
        ]);

        $featuredCol = VideoCollection::create([
            'name' => 'Öne Çıkan Videolar Test',
            'slug' => 'one-cikan-test',
            'source_type' => 'featured',
            'is_active' => true,
        ]);

        $activePeriodCol = VideoCollection::create([
            'name' => 'Akıştan Videolar Test',
            'slug' => 'akistan-test',
            'source_type' => 'active_period_program_videos',
            'is_active' => true,
        ]);

        $featuredResolved = $featuredCol->resolveEpisodes();
        $activePeriodResolved = $activePeriodCol->resolveEpisodes();

        $this->assertCount(1, $featuredResolved, 'Featured collection must only resolve live program episodes.');
        $this->assertEquals($liveEp->id, $featuredResolved->first()->id);

        $this->assertCount(2, $activePeriodResolved, 'Active period collection must resolve all schedule program episodes.');
    }
}
