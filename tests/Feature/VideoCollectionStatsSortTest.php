<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Episode;
use App\Models\Program;
use App\Models\VideoCollection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VideoCollectionStatsSortTest extends TestCase
{
    use RefreshDatabase;

    protected Category $category;
    protected Program $program;
    protected Episode $ep1;
    protected Episode $ep2;
    protected Episode $ep3;
    protected Episode $epNullStats;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::create([
            'name' => 'İlim Ve İrfan',
            'slug' => 'ilim-ve-irfan',
            'is_active' => true,
        ]);

        $this->program = Program::create([
            'name' => 'Tarih Sohbetleri',
            'slug' => 'tarih-sohbetleri',
            'is_active' => true,
            'show_on_public' => true,
        ]);
        $this->program->categories()->attach($this->category->id);

        $this->ep1 = Episode::create([
            'program_id' => $this->program->id,
            'title' => 'Bölüm 1 - Düşük İzlenme',
            'slug' => 'bolum-1',
            'status' => 'published',
            'is_active' => true,
            'show_on_public' => true,
            'aired_at' => '2026-01-01',
            'view_count' => 100,
            'like_count' => 50,
            'comment_count' => 10,
        ]);

        $this->ep2 = Episode::create([
            'program_id' => $this->program->id,
            'title' => 'Bölüm 2 - Yüksek İzlenme',
            'slug' => 'bolum-2',
            'status' => 'published',
            'is_active' => true,
            'show_on_public' => true,
            'aired_at' => '2026-01-02',
            'view_count' => 5000,
            'like_count' => 200,
            'comment_count' => 80,
        ]);

        $this->ep3 = Episode::create([
            'program_id' => $this->program->id,
            'title' => 'Bölüm 3 - Orta İzlenme',
            'slug' => 'bolum-3',
            'status' => 'published',
            'is_active' => true,
            'show_on_public' => true,
            'aired_at' => '2026-01-03',
            'view_count' => 1500,
            'like_count' => 900,
            'comment_count' => 5,
        ]);

        $this->epNullStats = Episode::create([
            'program_id' => $this->program->id,
            'title' => 'Bölüm 4 - İstatistik Yok',
            'slug' => 'bolum-4',
            'status' => 'published',
            'is_active' => true,
            'show_on_public' => true,
            'aired_at' => '2026-01-04',
            'view_count' => null,
            'like_count' => null,
            'comment_count' => null,
        ]);
    }

    public function test_newest_sorting(): void
    {
        $collection = VideoCollection::create([
            'name' => 'Kategori En Yeni',
            'slug' => 'kat-en-yeni',
            'source_type' => 'category',
            'category_id' => $this->category->id,
            'sort_mode' => 'newest',
            'is_active' => true,
        ]);

        $resolved = $collection->resolveEpisodes();

        $this->assertCount(4, $resolved);
        $this->assertEquals($this->epNullStats->id, $resolved[0]->id); // 2026-01-04
        $this->assertEquals($this->ep3->id, $resolved[1]->id);       // 2026-01-03
        $this->assertEquals($this->ep2->id, $resolved[2]->id);       // 2026-01-02
        $this->assertEquals($this->ep1->id, $resolved[3]->id);       // 2026-01-01
    }

    public function test_oldest_sorting(): void
    {
        $collection = VideoCollection::create([
            'name' => 'Kategori En Eski',
            'slug' => 'kat-en-eski',
            'source_type' => 'category',
            'category_id' => $this->category->id,
            'sort_mode' => 'oldest',
            'is_active' => true,
        ]);

        $resolved = $collection->resolveEpisodes();

        $this->assertCount(4, $resolved);
        $this->assertEquals($this->ep1->id, $resolved[0]->id); // 2026-01-01
        $this->assertEquals($this->ep2->id, $resolved[1]->id); // 2026-01-02
        $this->assertEquals($this->ep3->id, $resolved[2]->id); // 2026-01-03
    }

    public function test_most_viewed_sorting_puts_null_stats_at_the_end(): void
    {
        $collection = VideoCollection::create([
            'name' => 'En Çok İzlenenler',
            'slug' => 'en-cok-izlenenler',
            'source_type' => 'category',
            'category_id' => $this->category->id,
            'sort_mode' => 'most_viewed',
            'is_active' => true,
        ]);

        $resolved = $collection->resolveEpisodes();

        $this->assertCount(4, $resolved);
        $this->assertEquals($this->ep2->id, $resolved[0]->id);        // 5000 views
        $this->assertEquals($this->ep3->id, $resolved[1]->id);        // 1500 views
        $this->assertEquals($this->ep1->id, $resolved[2]->id);        // 100 views
        $this->assertEquals($this->epNullStats->id, $resolved[3]->id); // null views at the end
    }

    public function test_most_liked_sorting(): void
    {
        $collection = VideoCollection::create([
            'name' => 'En Çok Beğenilenler',
            'slug' => 'en-cok-begenilenler',
            'source_type' => 'category',
            'category_id' => $this->category->id,
            'sort_mode' => 'most_liked',
            'is_active' => true,
        ]);

        $resolved = $collection->resolveEpisodes();

        $this->assertCount(4, $resolved);
        $this->assertEquals($this->ep3->id, $resolved[0]->id);        // 900 likes
        $this->assertEquals($this->ep2->id, $resolved[1]->id);        // 200 likes
        $this->assertEquals($this->ep1->id, $resolved[2]->id);        // 50 likes
        $this->assertEquals($this->epNullStats->id, $resolved[3]->id); // null likes at the end
    }

    public function test_most_commented_sorting(): void
    {
        $collection = VideoCollection::create([
            'name' => 'En Çok Yorum Alanlar',
            'slug' => 'en-cok-yorum-alanlar',
            'source_type' => 'category',
            'category_id' => $this->category->id,
            'sort_mode' => 'most_commented',
            'is_active' => true,
        ]);

        $resolved = $collection->resolveEpisodes();

        $this->assertCount(4, $resolved);
        $this->assertEquals($this->ep2->id, $resolved[0]->id);        // 80 comments
        $this->assertEquals($this->ep1->id, $resolved[1]->id);        // 10 comments
        $this->assertEquals($this->ep3->id, $resolved[2]->id);        // 5 comments
        $this->assertEquals($this->epNullStats->id, $resolved[3]->id); // null comments at the end
    }

    public function test_homepage_limit_is_applied_after_sorting(): void
    {
        $collection = VideoCollection::create([
            'name' => 'Limitli En Çok İzlenen',
            'slug' => 'limitli-most-viewed',
            'source_type' => 'category',
            'category_id' => $this->category->id,
            'sort_mode' => 'most_viewed',
            'is_active' => true,
        ]);

        $resolvedLimit2 = $collection->resolveEpisodes(2);

        $this->assertCount(2, $resolvedLimit2);
        $this->assertEquals($this->ep2->id, $resolvedLimit2[0]->id); // 5000 views
        $this->assertEquals($this->ep3->id, $resolvedLimit2[1]->id); // 1500 views
    }

    public function test_hybrid_collection_sorts_pinned_and_auto_episodes(): void
    {
        $collection = VideoCollection::create([
            'name' => 'Hibrit Koleksiyon',
            'slug' => 'hibrit-koleksiyon',
            'source_type' => 'hybrid',
            'category_id' => $this->category->id,
            'sort_mode' => 'most_viewed',
            'is_active' => true,
        ]);

        // Pin ep1 (100 views)
        $collection->episodes()->attach($this->ep1->id, ['sort_order' => 1]);

        $resolved = $collection->resolveEpisodes();

        $this->assertCount(4, $resolved);
        $this->assertEquals($this->ep1->id, $resolved[0]->id); // Pinned ep1 comes first
        // Remaining auto episodes sorted by most_viewed: ep2 (5000), ep3 (1500), epNullStats (null)
        $this->assertEquals($this->ep2->id, $resolved[1]->id);
        $this->assertEquals($this->ep3->id, $resolved[2]->id);
        $this->assertEquals($this->epNullStats->id, $resolved[3]->id);
    }
}
