<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Episode;
use App\Models\Program;
use App\Models\User;
use App\Models\VideoCollection;
use App\Models\YoutubeChannel;
use App\Services\YouTube\YoutubeCenterDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class YoutubeCenterManagementTest extends TestCase
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

    public function test_youtube_page_layout_admin_page_is_removed()
    {
        $this->actingAs($this->admin);

        // The legacy /admin/site-layout/youtube page should return 404
        $response = $this->get('/admin/site-layout/youtube');
        $response->assertStatus(404);
    }

    public function test_public_page_resolves_active_video_collections_in_sort_order()
    {
        $category = Category::create(['name' => 'Kültür', 'slug' => 'kultur', 'is_active' => true]);
        $program = Program::create(['name' => 'Kültür Programı', 'slug' => 'kultur-prog', 'status' => 'active', 'show_on_public' => true]);
        $program->categories()->attach($category);

        $episode1 = Episode::create([
            'program_id' => $program->id,
            'title' => 'Kültür Bölüm 1',
            'slug' => 'kultur-bolum-1',
            'youtube_url' => 'https://www.youtube.com/watch?v=KUL1',
            'status' => 'published',
            'show_on_public' => true,
            'is_active' => true,
            'aired_at' => now(),
        ]);

        // Collection 1 (sort_order 2)
        $collection2 = VideoCollection::create([
            'name' => 'Kültür Videoları (İkinci)',
            'slug' => 'kultur-videolari-ikinci',
            'source_type' => 'category',
            'category_id' => $category->id,
            'sort_order' => 2,
            'is_active' => true,
        ]);

        // Collection 2 (sort_order 1)
        $collection1 = VideoCollection::create([
            'name' => 'Sizin İçin Seçtiklerimiz (Birinci)',
            'slug' => 'sizin-icin-sectiklerimiz-birinci',
            'source_type' => 'manual',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        $collection1->episodes()->attach($episode1->id);

        // Collection 3 (is_active false)
        $inactiveCollection = VideoCollection::create([
            'name' => 'Pasif Koleksiyon',
            'slug' => 'pasif-koleksiyon',
            'source_type' => 'manual',
            'sort_order' => 0,
            'is_active' => false,
        ]);

        $service = app(YoutubeCenterDataService::class);
        $data = $service->getCenterData();

        $this->assertCount(2, $data['category_shelves']);
        $this->assertEquals('Sizin İçin Seçtiklerimiz (Birinci)', $data['category_shelves'][0]['title']);
        $this->assertEquals('Kültür Videoları (İkinci)', $data['category_shelves'][1]['title']);
    }

    public function test_empty_collection_is_skipped_on_public_page()
    {
        $emptyCollection = VideoCollection::create([
            'name' => 'Boş Koleksiyon',
            'slug' => 'bos-koleksiyon',
            'source_type' => 'manual',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $service = app(YoutubeCenterDataService::class);
        $data = $service->getCenterData();

        $this->assertEmpty($data['category_shelves']);
    }

    public function test_public_page_renders_channels_and_active_video_collection_shelves()
    {
        $channel = YoutubeChannel::create([
            'name' => 'DOST TV Ana Kanal',
            'handle' => '@DostTV',
            'url' => 'https://www.youtube.com/@DostTV',
            'is_active' => true,
        ]);

        $category = Category::create(['name' => 'Sohbetler', 'slug' => 'sohbetler', 'is_active' => true]);
        $program = Program::create(['name' => 'Cuma Sohbetleri', 'slug' => 'cuma-sohbetleri', 'status' => 'active', 'show_on_public' => true]);
        $program->categories()->attach($category);

        Episode::create([
            'program_id' => $program->id,
            'title' => 'Cuma Özel Videosu',
            'slug' => 'cuma-ozel-videosu',
            'youtube_url' => 'https://www.youtube.com/watch?v=CUMA_1',
            'status' => 'published',
            'show_on_public' => true,
            'is_active' => true,
            'aired_at' => now(),
        ]);

        VideoCollection::create([
            'name' => 'Sohbet Kütüphanesi',
            'slug' => 'sohbet-kutuphanesi',
            'source_type' => 'category',
            'category_id' => $category->id,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $response = $this->get(route('youtube-channels.index'));

        $response->assertStatus(200);
        $response->assertSee('DOST TV YouTube Kanalları');
        $response->assertSee('DOST TV Ana Kanal');
        $response->assertSee('@DostTV');
        $response->assertSee('YouTube Videolarımız');
        $response->assertSee('Sohbet Kütüphanesi');
        $response->assertSee('Cuma Özel Videosu');
    }

    public function test_youtube_channels_admin_resource_remains_intact_and_accessible()
    {
        $this->actingAs($this->admin);

        $response = $this->get('/admin/youtube-channels');
        $response->assertStatus(200);
    }
}
