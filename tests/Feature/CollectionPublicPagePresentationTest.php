<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Episode;
use App\Models\Program;
use App\Models\ProgramCollection;
use App\Models\ScheduleTemplate;
use App\Models\ScheduleTemplateItem;
use App\Models\VideoCollection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CollectionPublicPagePresentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_null_public_settings_preserves_default_layout_and_shows_description(): void
    {
        $programCollection = ProgramCollection::create([
            'name' => 'Varsayılan Program Koleksiyonu',
            'slug' => 'varsayilan-program-koleksiyonu',
            'description' => 'Varsayılan açıklama metni.',
            'source_type' => 'manual',
            'is_active' => true,
            'public_settings' => null,
        ]);

        $program = Program::create([
            'name' => 'Test Program 1',
            'slug' => 'test-program-1',
            'is_active' => true,
            'show_on_public' => true,
        ]);
        $programCollection->programs()->attach($program->id, ['sort_order' => 1]);

        $response = $this->get('/program-koleksiyonlari/varsayilan-program-koleksiyonu');

        $response->assertOk();
        $response->assertSee('Varsayılan Program Koleksiyonu');
        $response->assertSee('Varsayılan açıklama metni.');
        $response->assertSee('grid-cols-2');
        $response->assertSee('sm:grid-cols-3');
        $response->assertSee('lg:grid-cols-4');
        $response->assertSee('gap-6');
    }

    public function test_homepage_limit_and_carousel_do_not_affect_collection_public_page(): void
    {
        $programCollection = ProgramCollection::create([
            'name' => 'Büyük Koleksiyon',
            'slug' => 'buyuk-koleksiyon',
            'source_type' => 'manual',
            'is_active' => true,
            'public_settings' => [
                'display_variant' => 'grid',
                'desktop_columns' => 4,
                'page_size' => 'all',
            ],
        ]);

        for ($i = 1; $i <= 10; $i++) {
            $prog = Program::create([
                'name' => "Koleksiyon Programı {$i}",
                'slug' => "koleksiyon-programi-{$i}",
                'is_active' => true,
                'show_on_public' => true,
            ]);
            $programCollection->programs()->attach($prog->id, ['sort_order' => $i]);
        }

        $response = $this->get('/program-koleksiyonlari/buyuk-koleksiyon');
        $response->assertOk();
        $response->assertSee('Koleksiyon Programı 10');
        $response->assertSee('lg:grid-cols-4');
        $response->assertDontSee('flex overflow-x-auto');
    }

    public function test_program_public_grid_column_and_gap_settings_work(): void
    {
        $programCollection = ProgramCollection::create([
            'name' => 'Özel Görünüm Koleksiyonu',
            'slug' => 'ozel-gorunum-koleksiyonu',
            'source_type' => 'manual',
            'is_active' => true,
            'public_settings' => [
                'display_variant' => 'grid',
                'desktop_columns' => 5,
                'tablet_columns' => 4,
                'mobile_columns' => 1,
                'gap_size' => 'large',
                'show_description' => false,
            ],
        ]);

        $program = Program::create([
            'name' => 'Test Program 1',
            'slug' => 'test-program-1',
            'is_active' => true,
            'show_on_public' => true,
        ]);
        $programCollection->programs()->attach($program->id, ['sort_order' => 1]);

        $response = $this->get('/program-koleksiyonlari/ozel-gorunum-koleksiyonu');

        $response->assertOk();
        $response->assertSee('grid-cols-1');
        $response->assertSee('sm:grid-cols-4');
        $response->assertSee('lg:grid-cols-5');
        $response->assertSee('gap-6');
    }

    public function test_program_public_carousel_and_description_hide_work(): void
    {
        $programCollection = ProgramCollection::create([
            'name' => 'Carousel Koleksiyonu',
            'slug' => 'carousel-koleksiyonu',
            'description' => 'Gizlenecek açıklama',
            'source_type' => 'manual',
            'is_active' => true,
            'public_settings' => [
                'display_variant' => 'carousel',
                'show_description' => false,
            ],
        ]);

        $program = Program::create([
            'name' => 'Test Program 1',
            'slug' => 'test-program-1',
            'is_active' => true,
            'show_on_public' => true,
        ]);
        $programCollection->programs()->attach($program->id, ['sort_order' => 1]);

        $response = $this->get('/program-koleksiyonlari/carousel-koleksiyonu');

        $response->assertOk();
        $response->assertSee('shelf-container');
        $response->assertDontSee('Gizlenecek açıklama');
    }

    public function test_page_size_pagination_works_on_program_collection(): void
    {
        $programCollection = ProgramCollection::create([
            'name' => 'Sayfalı Programlar',
            'slug' => 'sayfali-programlar',
            'source_type' => 'manual',
            'is_active' => true,
            'public_settings' => [
                'page_size' => 12,
            ],
        ]);

        for ($i = 1; $i <= 15; $i++) {
            $prog = Program::create([
                'name' => "Sayfa Programı {$i}",
                'slug' => "sayfa-programi-{$i}",
                'is_active' => true,
                'show_on_public' => true,
            ]);
            $programCollection->programs()->attach($prog->id, ['sort_order' => $i]);
        }

        $page1 = $this->get('/program-koleksiyonlari/sayfali-programlar');
        $page1->assertOk();
        $page1->assertSee('Sayfa Programı 1');
        $page1->assertSee('Sayfa Programı 12');
        $page1->assertDontSee('Sayfa Programı 13');

        $page2 = $this->get('/program-koleksiyonlari/sayfali-programlar?page=2');
        $page2->assertOk();
        $page2->assertSee('Sayfa Programı 13');
        $page2->assertSee('Sayfa Programı 15');
    }

    public function test_video_collection_controller_resolves_all_source_types(): void
    {
        $category = Category::create([
            'name' => 'Dini İlimler',
            'slug' => 'dini-ilimler',
            'is_active' => true,
        ]);

        $program = Program::create([
            'name' => 'Fıkıh Saati',
            'slug' => 'fikih-saati',
            'is_active' => true,
            'show_on_public' => true,
        ]);
        $program->categories()->attach($category->id);

        $episode1 = Episode::create([
            'program_id' => $program->id,
            'title' => 'Fıkıh 1. Bölüm',
            'slug' => 'fikih-1-bolum',
            'video_url' => 'https://www.youtube.com/watch?v=video111',
            'is_active' => true,
            'show_on_public' => true,
            'aired_at' => now()->subDays(2),
        ]);

        // 1. Manual source
        $manualColl = VideoCollection::create([
            'name' => 'Manuel Video Koleksiyonu',
            'slug' => 'manuel-video-koleksiyonu',
            'source_type' => 'manual',
            'is_active' => true,
        ]);
        $manualColl->episodes()->attach($episode1->id, ['sort_order' => 1]);
        $resManual = $this->get('/koleksiyonlar/manuel-video-koleksiyonu');
        $resManual->assertOk();
        $resManual->assertSee('Fıkıh 1. Bölüm');

        // 2. Category source
        $catColl = VideoCollection::create([
            'name' => 'Kategori Video Koleksiyonu',
            'slug' => 'kategori-video-koleksiyonu',
            'source_type' => 'category',
            'category_id' => $category->id,
            'is_active' => true,
        ]);
        $resCat = $this->get('/koleksiyonlar/kategori-video-koleksiyonu');
        $resCat->assertOk();
        $resCat->assertSee('Fıkıh 1. Bölüm');

        // 3. Featured source (live schedule programs)
        $template = ScheduleTemplate::create([
            'name' => 'Aktif Şablon',
            'status' => 'published',
            'start_date' => now()->subDay(),
            'is_active' => true,
        ]);
        ScheduleTemplateItem::create([
            'schedule_template_id' => $template->id,
            'program_id' => $program->id,
            'day_of_week' => 1,
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'is_live' => true,
            'is_active' => true,
        ]);

        $featuredColl = VideoCollection::create([
            'name' => 'Öne Çıkan Video Koleksiyonu',
            'slug' => 'one-cikan-video-koleksiyonu',
            'source_type' => 'featured',
            'is_active' => true,
        ]);
        $resFeatured = $this->get('/koleksiyonlar/one-cikan-video-koleksiyonu');
        $resFeatured->assertOk();
        $resFeatured->assertSee('Fıkıh 1. Bölüm');

        // 4. Active period program videos
        $activePeriodColl = VideoCollection::create([
            'name' => 'Aktif Dönem Videoları',
            'slug' => 'aktif-donem-videolari',
            'source_type' => 'active_period_program_videos',
            'is_active' => true,
        ]);
        $resActivePeriod = $this->get('/koleksiyonlar/aktif-donem-videolari');
        $resActivePeriod->assertOk();
        $resActivePeriod->assertSee('Fıkıh 1. Bölüm');

        // 5. Hybrid source
        $hybridColl = VideoCollection::create([
            'name' => 'Hibrit Video Koleksiyonu',
            'slug' => 'hibrit-video-koleksiyonu',
            'source_type' => 'hybrid',
            'category_id' => $category->id,
            'is_active' => true,
        ]);
        $resHybrid = $this->get('/koleksiyonlar/hibrit-video-koleksiyonu');
        $resHybrid->assertOk();
        $resHybrid->assertSee('Fıkıh 1. Bölüm');
    }

    public function test_video_public_grid_column_gap_and_carousel_settings_work(): void
    {
        $program = Program::create([
            'name' => 'Fıkıh Saati',
            'slug' => 'fikih-saati-2',
            'is_active' => true,
            'show_on_public' => true,
        ]);

        $episode = Episode::create([
            'program_id' => $program->id,
            'title' => 'Fıkıh 2. Bölüm',
            'slug' => 'fikih-2-bolum',
            'video_url' => 'https://www.youtube.com/watch?v=video222',
            'is_active' => true,
            'show_on_public' => true,
            'aired_at' => now()->subDays(1),
        ]);

        $videoColl = VideoCollection::create([
            'name' => 'Video Carousel Koleksiyonu',
            'slug' => 'video-carousel-koleksiyonu',
            'source_type' => 'manual',
            'is_active' => true,
            'public_settings' => [
                'display_variant' => 'carousel',
                'desktop_columns' => 6,
                'gap_size' => 'small',
            ],
        ]);
        $videoColl->episodes()->attach($episode->id, ['sort_order' => 1]);

        $response = $this->get('/koleksiyonlar/video-carousel-koleksiyonu');

        $response->assertOk();
        $response->assertSee('shelf-container');
    }
}
