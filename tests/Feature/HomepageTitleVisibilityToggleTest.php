<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Episode;
use App\Models\HomepageLayout;
use App\Models\Program;
use App\Support\SiteCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageTitleVisibilityToggleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'CategorySeeder']);
    }

    public function test_program_titles_can_be_hidden_on_homepage_shelf(): void
    {
        $program = Program::create([
            'name' => 'Özel Test Programı',
            'slug' => 'ozel-test-programi',
            'is_active' => true,
            'show_on_public' => true,
            'is_featured' => true,
        ]);

        $layout = HomepageLayout::create([
            'name' => 'Varsayılan Düzen',
            'is_active' => true,
            'draft_sections' => [
                [
                    'uuid' => 'test-sec-1',
                    'block_type' => 'program_showcase',
                    'visible' => true,
                    'title' => 'Program Vitrini',
                    'show_title' => true,
                    'show_program_titles' => false,
                    'source_mode' => 'featured_programs',
                    'pinned_ids' => [$program->id],
                ],
            ],
            'published_sections' => [
                [
                    'uuid' => 'test-sec-1',
                    'block_type' => 'program_showcase',
                    'visible' => true,
                    'title' => 'Program Vitrini',
                    'show_title' => true,
                    'show_program_titles' => false,
                    'source_mode' => 'featured_programs',
                    'pinned_ids' => [$program->id],
                ],
            ],
        ]);

        SiteCache::forgetHomepage();

        $response = $this->get('/');
        $response->assertOk();
        $response->assertSee('Program Vitrini');
        $response->assertDontSee('dost-card-title mt-2.5 font-semibold text-white group-hover:opacity-85 transition-opacity truncate">Özel Test Programı', false);
        $response->assertSee('/programlar/ozel-test-programi');
    }

    public function test_program_titles_are_shown_when_show_program_titles_is_true(): void
    {
        $program = Program::create([
            'name' => 'Görünen Test Programı',
            'slug' => 'gorunen-test-programi',
            'is_active' => true,
            'show_on_public' => true,
            'is_featured' => true,
        ]);

        $layout = HomepageLayout::create([
            'name' => 'Varsayılan Düzen',
            'is_active' => true,
            'draft_sections' => [
                [
                    'uuid' => 'test-sec-2',
                    'block_type' => 'program_showcase',
                    'visible' => true,
                    'title' => 'Program Vitrini',
                    'show_title' => true,
                    'show_program_titles' => true,
                    'source_mode' => 'featured_programs',
                    'pinned_ids' => [$program->id],
                ],
            ],
            'published_sections' => [
                [
                    'uuid' => 'test-sec-2',
                    'block_type' => 'program_showcase',
                    'visible' => true,
                    'title' => 'Program Vitrini',
                    'show_title' => true,
                    'show_program_titles' => true,
                    'source_mode' => 'featured_programs',
                    'pinned_ids' => [$program->id],
                ],
            ],
        ]);

        SiteCache::forgetHomepage();

        $response = $this->get('/');
        $response->assertOk();
        $response->assertSee('Görünen Test Programı');
    }

    public function test_video_titles_can_be_hidden_on_homepage_shelf(): void
    {
        $program = Program::create([
            'name' => 'Video Ana Programı',
            'slug' => 'video-ana-programi',
            'is_active' => true,
            'show_on_public' => true,
        ]);

        $episode = Episode::create([
            'program_id' => $program->id,
            'title' => 'Gizlenecek Video Başlığı',
            'slug' => 'gizlenecek-video-basligi',
            'is_active' => true,
            'show_on_public' => true,
        ]);

        $layout = HomepageLayout::create([
            'name' => 'Varsayılan Düzen',
            'is_active' => true,
            'draft_sections' => [
                [
                    'uuid' => 'test-sec-3',
                    'block_type' => 'video_collection',
                    'visible' => true,
                    'title' => 'Video Vitrini',
                    'show_title' => true,
                    'show_video_titles' => false,
                    'source_mode' => 'hybrid_videos',
                    'pinned_ids' => [$episode->id],
                ],
            ],
            'published_sections' => [
                [
                    'uuid' => 'test-sec-3',
                    'block_type' => 'video_collection',
                    'visible' => true,
                    'title' => 'Video Vitrini',
                    'show_title' => true,
                    'show_video_titles' => false,
                    'source_mode' => 'hybrid_videos',
                    'pinned_ids' => [$episode->id],
                ],
            ],
        ]);

        SiteCache::forgetHomepage();

        $response = $this->get('/');
        $response->assertOk();
        $response->assertSee('Video Vitrini');
        $response->assertDontSee('dost-card-title line-clamp-2 text-sm font-medium text-slate-200 group-hover:text-white transition">', false);
        $response->assertSee($episode->public_url);
    }

    public function test_detail_pages_are_unaffected_by_homepage_visibility_settings(): void
    {
        $program = Program::create([
            'name' => 'Detay Sayfası Programı',
            'slug' => 'detay-sayfasi-programi',
            'is_active' => true,
            'show_on_public' => true,
        ]);

        $response = $this->get(route('programs.show', $program));
        $response->assertOk();
        $response->assertSee('Detay Sayfası Programı');
    }
}
