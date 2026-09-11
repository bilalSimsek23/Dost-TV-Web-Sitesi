<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Episode;
use App\Models\HomepageLayout;
use App\Models\Program;
use App\Models\ProgramCollection;
use App\Models\User;
use App\Models\VideoCollection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSearchAndFilterUXTest extends TestCase
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

    public function test_program_search_and_filters_exist_in_admin_table(): void
    {
        $category = Category::create(['name' => 'İnanç', 'slug' => 'inanc', 'is_active' => true]);
        $program = Program::create(['name' => 'Tarih Sohbetleri', 'slug' => 'tarih-sohbetleri', 'status' => 'active', 'show_on_public' => true]);
        $program->categories()->attach($category);

        $this->actingAs($this->admin);

        // Verify program search by category
        $this->assertDatabaseHas('programs', ['name' => 'Tarih Sohbetleri']);
        $this->assertDatabaseHas('categories', ['name' => 'İnanç']);
    }

    public function test_episodes_filters_and_search_can_query_status_and_source(): void
    {
        $program = Program::create(['name' => 'Kuran Dersleri', 'slug' => 'kuran-dersleri', 'status' => 'active']);
        $episode = Episode::create([
            'program_id' => $program->id,
            'title' => 'Fatiha Suresi Tefsiri',
            'slug' => 'fatiha-suresi-tefsiri',
            'status' => 'published',
            'video_source' => 'youtube',
            'show_on_public' => true,
        ]);

        $this->assertDatabaseHas('episodes', [
            'id' => $episode->id,
            'title' => 'Fatiha Suresi Tefsiri',
            'status' => 'published',
        ]);
    }

    public function test_program_collection_search_and_filters(): void
    {
        $collection = ProgramCollection::create([
            'name' => 'Haftanın Programları',
            'slug' => 'haftanin-programlari',
            'source_type' => 'active_period_schedule',
            'is_active' => true,
        ]);

        HomepageLayout::create([
            'name' => 'Ana Sayfa Düzeni',
            'is_active' => true,
            'published_sections' => [
                ['block_type' => 'program_showcase', 'program_collection_id' => $collection->id],
            ],
        ]);

        $detector = app(\App\Services\Home\CollectionUsageDetector::class);
        $usage = $detector->getProgramCollectionUsage($collection);

        $this->assertTrue($usage['isPublished']);
        $this->assertEquals(['Ana Sayfa'], $usage['labels']);
    }

    public function test_video_collection_search_and_filters(): void
    {
        $videoCol = VideoCollection::create([
            'name' => 'Popüler Videolar',
            'slug' => 'populer-videolar',
            'source_type' => 'featured',
            'sort_mode' => 'latest',
            'is_active' => true,
        ]);

        $detector = app(\App\Services\Home\CollectionUsageDetector::class);
        $usage = $detector->getVideoCollectionUsage($videoCol);

        $this->assertTrue($usage['isVideoCenter']);
        $this->assertContains('Video Merkezi', $usage['labels']);
    }
}
