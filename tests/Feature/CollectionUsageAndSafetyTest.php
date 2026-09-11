<?php

namespace Tests\Feature;

use App\Models\HomepageLayout;
use App\Models\ProgramCollection;
use App\Models\User;
use App\Models\VideoCollection;
use App\Services\Home\CollectionUsageDetector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CollectionUsageAndSafetyTest extends TestCase
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

    public function test_program_collection_published_and_draft_usages_are_correctly_detected(): void
    {
        $publishedCollection = ProgramCollection::create([
            'name' => 'Canlı Koleksiyon',
            'slug' => 'canli-koleksiyon',
            'source_type' => 'active_period_schedule',
            'is_active' => true,
        ]);

        $draftCollection = ProgramCollection::create([
            'name' => 'Taslak Koleksiyon',
            'slug' => 'taslak-koleksiyon',
            'source_type' => 'all_programs',
            'is_active' => true,
        ]);

        $unusedCollection = ProgramCollection::create([
            'name' => 'Boşta Koleksiyon',
            'slug' => 'bosta-koleksiyon',
            'source_type' => 'manual',
            'is_active' => true,
        ]);

        HomepageLayout::create([
            'name' => 'Ana Düzen',
            'is_active' => true,
            'published_sections' => [
                [
                    'block_type' => 'program_showcase',
                    'program_collection_id' => $publishedCollection->id,
                ],
            ],
            'draft_sections' => [
                [
                    'block_type' => 'program_showcase',
                    'program_collection_id' => $draftCollection->id,
                ],
            ],
        ]);

        $detector = app(CollectionUsageDetector::class);

        $pubUsage = $detector->getProgramCollectionUsage($publishedCollection);
        $this->assertTrue($pubUsage['isPublished']);
        $this->assertFalse($pubUsage['isDraft']);
        $this->assertEquals(['Ana Sayfa'], $pubUsage['labels']);

        $draftUsage = $detector->getProgramCollectionUsage($draftCollection);
        $this->assertFalse($draftUsage['isPublished']);
        $this->assertTrue($draftUsage['isDraft']);
        $this->assertEquals(['Taslak'], $draftUsage['labels']);

        $unusedUsage = $detector->getProgramCollectionUsage($unusedCollection);
        $this->assertFalse($unusedUsage['isPublished']);
        $this->assertFalse($unusedUsage['isDraft']);
        $this->assertEquals(['Kullanılmıyor'], $unusedUsage['labels']);
    }

    public function test_video_collection_homepage_and_video_center_usages_are_correctly_detected(): void
    {
        $publishedVideoCol = VideoCollection::create([
            'name' => 'Canlı Video Koleksiyonu',
            'slug' => 'canli-video-koleksiyonu',
            'source_type' => 'active_period_program_videos',
            'is_active' => true,
        ]);

        $videoCenterOnlyCol = VideoCollection::create([
            'name' => 'Yalnız Video Merkezi Koleksiyonu',
            'slug' => 'video-merkezi-koleksiyonu',
            'source_type' => 'manual',
            'is_active' => true,
        ]);

        $unusedVideoCol = VideoCollection::create([
            'name' => 'Boşta Video Koleksiyonu',
            'slug' => 'bosta-video-koleksiyonu',
            'source_type' => 'manual',
            'is_active' => false,
        ]);

        HomepageLayout::create([
            'name' => 'Ana Düzen',
            'is_active' => true,
            'published_sections' => [
                [
                    'block_type' => 'video_collection',
                    'collection_id' => $publishedVideoCol->id,
                ],
            ],
            'draft_sections' => [],
        ]);

        $detector = app(CollectionUsageDetector::class);

        $pubUsage = $detector->getVideoCollectionUsage($publishedVideoCol);
        $this->assertTrue($pubUsage['isPublished']);
        $this->assertTrue($pubUsage['isVideoCenter']);
        $this->assertContains('Ana Sayfa', $pubUsage['labels']);
        $this->assertContains('Video Merkezi', $pubUsage['labels']);

        $vcOnlyUsage = $detector->getVideoCollectionUsage($videoCenterOnlyCol);
        $this->assertFalse($vcOnlyUsage['isPublished']);
        $this->assertTrue($vcOnlyUsage['isVideoCenter']);
        $this->assertEquals(['Video Merkezi'], $vcOnlyUsage['labels']);

        $unusedUsage = $detector->getVideoCollectionUsage($unusedVideoCol);
        $this->assertFalse($unusedUsage['isPublished']);
        $this->assertFalse($unusedUsage['isVideoCenter']);
        $this->assertEquals(['Kullanılmıyor'], $unusedUsage['labels']);
    }

    public function test_deleted_or_inactive_collection_does_not_cause_500_on_public_home(): void
    {
        $collection = ProgramCollection::create([
            'name' => 'Silinecek Koleksiyon',
            'slug' => 'silinecek-koleksiyon',
            'source_type' => 'all_programs',
            'is_active' => false,
        ]);

        HomepageLayout::create([
            'name' => 'Test Düzeni',
            'is_active' => true,
            'published_sections' => [
                [
                    'block_type' => 'program_showcase',
                    'program_collection_id' => $collection->id,
                ],
            ],
        ]);

        $response = $this->get('/');
        $response->assertStatus(200);

        // Delete collection permanently and verify home still returns 200
        $collection->delete();

        $responseAfterDelete = $this->get('/');
        $responseAfterDelete->assertStatus(200);
    }
}
