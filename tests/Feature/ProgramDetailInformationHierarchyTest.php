<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Episode;
use App\Models\Program;
use App\Models\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgramDetailInformationHierarchyTest extends TestCase
{
    use RefreshDatabase;

    public function test_program_with_name_description_and_categories_layout(): void
    {
        $category = Category::create([
            'name' => 'Tefekkür',
            'slug' => 'tefekkur',
            'is_active' => true,
        ]);

        $program = Program::create([
            'name' => 'Yad-ı Cemil',
            'slug' => 'yad-i-cemil',
            'description' => 'Yad-ı Cemil programı köklü medeniyet mirasımızı konu ediniyor.',
            'is_active' => true,
            'show_on_public' => true,
        ]);
        $program->categories()->attach($category->id);

        Schedule::create([
            'program_id' => $program->id,
            'day_of_week' => 1,
            'start_time' => '20:00:00',
            'end_time' => '21:00:00',
            'is_active' => true,
        ]);

        Episode::create([
            'program_id' => $program->id,
            'title' => 'Yad-ı Cemil 1. Bölüm',
            'slug' => 'yad-i-cemil-1-bolum',
            'video_source' => 'youtube',
            'youtube_url' => 'https://www.youtube.com/watch?v=TESTYAD1',
            'status' => 'published',
            'show_on_public' => true,
            'is_active' => true,
        ]);

        $response = $this->get(route('programs.show', $program));

        $response->assertStatus(200);

        // 1. Broadcast Schedule box should NOT be rendered in program detail upper area
        $response->assertDontSee('Yayın Saatleri');
        $response->assertDontSee('Yayın akışı henüz planlanmadı');

        // 2. Program Name and Description rendered
        $response->assertSee('Yad-ı Cemil');
        $response->assertSee('Yad-ı Cemil programı köklü medeniyet mirasımızı konu ediniyor.');

        // 3. Category rendered
        $response->assertSee('Tefekkür');
    }

    public function test_program_without_description_has_no_placeholder_text(): void
    {
        $category = Category::create([
            'name' => 'Dua',
            'slug' => 'dua',
            'is_active' => true,
        ]);

        $program = Program::create([
            'name' => 'Dualar ve Niyazlar',
            'slug' => 'dualar-ve-niyazlar',
            'description' => null,
            'is_active' => true,
            'show_on_public' => true,
        ]);
        $program->categories()->attach($category->id);

        Episode::create([
            'program_id' => $program->id,
            'title' => 'Dualar 1. Bölüm',
            'slug' => 'dualar-1-bolum',
            'video_source' => 'youtube',
            'youtube_url' => 'https://www.youtube.com/watch?v=TESTDUA1',
            'status' => 'published',
            'show_on_public' => true,
            'is_active' => true,
        ]);

        $response = $this->get(route('programs.show', $program));

        $response->assertStatus(200);
        $response->assertSee('Dualar ve Niyazlar');
        $response->assertSee('Dua');
        $response->assertDontSee('Dost TV program içeriği.');
    }

    public function test_program_with_multiple_categories_renders_all_badges(): void
    {
        $cat1 = Category::create(['name' => 'Tefekkür', 'slug' => 'tefekkur', 'is_active' => true]);
        $cat2 = Category::create(['name' => 'Dua', 'slug' => 'dua', 'is_active' => true]);

        $program = Program::create([
            'name' => 'Çok Kategorili Program',
            'slug' => 'cok-kategorili-program',
            'description' => 'Açıklama metni',
            'is_active' => true,
            'show_on_public' => true,
        ]);
        $program->categories()->attach([$cat1->id, $cat2->id]);

        Episode::create([
            'program_id' => $program->id,
            'title' => 'Bölüm 1',
            'slug' => 'bolum-1',
            'video_source' => 'youtube',
            'youtube_url' => 'https://www.youtube.com/watch?v=TESTCAT1',
            'status' => 'published',
            'show_on_public' => true,
            'is_active' => true,
        ]);

        $response = $this->get(route('programs.show', $program));

        $response->assertStatus(200);
        $response->assertSee('Tefekkür');
        $response->assertSee('Dua');
    }
}
