<?php

namespace Tests\Feature;

use App\Models\Episode;
use App\Models\Program;
use App\Models\VideoCollection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VideoCollectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_video_collection_and_attach_episodes(): void
    {
        $program = Program::factory()->create(['name' => 'Test Program 1']);
        $ep1 = Episode::factory()->create(['program_id' => $program->id, 'title' => 'Bölüm 1']);
        $ep2 = Episode::factory()->create(['program_id' => $program->id, 'title' => 'Bölüm 2']);

        $collection = VideoCollection::create([
            'name' => 'Editörün Seçtikleri',
            'is_active' => true,
        ]);

        $collection->episodes()->attach([
            $ep1->id => ['sort_order' => 1],
            $ep2->id => ['sort_order' => 2],
        ]);

        $this->assertCount(2, $collection->episodes);
        $this->assertEquals('Bölüm 1', $collection->episodes->first()->title);
    }

    public function test_removing_episode_from_collection_does_not_delete_episode(): void
    {
        $program = Program::factory()->create(['name' => 'Test Program 2']);
        $ep = Episode::factory()->create(['program_id' => $program->id, 'title' => 'Test Episode']);

        $collection = VideoCollection::create(['name' => 'Koleksiyon 1']);
        $collection->episodes()->attach($ep->id);

        $collection->episodes()->detach($ep->id);

        $this->assertCount(0, $collection->fresh()->episodes);
        $this->assertDatabaseHas('episodes', ['id' => $ep->id]);
    }

    public function test_public_video_collection_page_renders_episodes_in_order(): void
    {
        $program = Program::factory()->create(['name' => 'Test Program 3']);
        $ep1 = Episode::factory()->create(['program_id' => $program->id, 'title' => 'İlk Video']);
        $ep2 = Episode::factory()->create(['program_id' => $program->id, 'title' => 'İkinci Video']);

        $collection = VideoCollection::create([
            'name' => 'Ramazan Özel',
            'is_active' => true,
        ]);

        $collection->episodes()->attach([
            $ep2->id => ['sort_order' => 1],
            $ep1->id => ['sort_order' => 2],
        ]);

        $response = $this->get(route('collections.show', $collection->slug));

        $response->assertStatus(200);
        $response->assertSee('Ramazan Özel');
        $response->assertSee('İkinci Video');
        $response->assertSee('İlk Video');
    }

    public function test_admin_video_collections_list_page_renders_new_simplified_table(): void
    {
        $admin = \App\Models\User::factory()->create(['email' => 'admin@dosttv.com', 'is_active' => true]);
        $this->actingAs($admin);

        VideoCollection::create([
            'name' => 'Öne Çıkan Sağlık Videoları',
            'source_type' => 'category',
            'is_active' => true,
        ]);

        $response = $this->get('/admin/video-collections');

        $response->assertStatus(200);
        $response->assertSee('Koleksiyon Adı');
        $response->assertSee('Kaynak Türü');
        $response->assertSee('Durum');
        $response->assertSee('Öne Çıkan Sağlık Videoları');
        $response->assertSee('Kategori');
        $response->assertSee('Aktif');
        $response->assertDontSee('episodes_count');
    }
}
