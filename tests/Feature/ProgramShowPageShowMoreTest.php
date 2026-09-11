<?php

namespace Tests\Feature;

use App\Models\Episode;
use App\Models\Program;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgramShowPageShowMoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_program_with_24_or_fewer_episodes_renders_all_episodes_without_show_more(): void
    {
        $program = Program::create([
            'name' => 'Akla Kapı',
            'slug' => 'akla-kapi',
            'is_active' => true,
            'show_on_public' => true,
        ]);

        for ($i = 1; $i <= 10; $i++) {
            Episode::create([
                'program_id' => $program->id,
                'title' => "Akla Kapı {$i}. Bölüm",
                'slug' => "akla-kapi-{$i}-bolum",
                'episode_number' => $i,
                'status' => 'published',
                'show_on_public' => true,
                'is_active' => true,
            ]);
        }

        $response = $this->get(route('programs.show', $program));

        $response->assertStatus(200);
        $response->assertSee('Akla Kapı 1. Bölüm');
        $response->assertSee('Akla Kapı 10. Bölüm');
        $response->assertDontSee('limit < total');
    }

    public function test_program_with_more_than_24_episodes_renders_alpine_show_more_button(): void
    {
        $program = Program::create([
            'name' => 'Kuran Dersleri',
            'slug' => 'kuran-dersleri',
            'is_active' => true,
            'show_on_public' => true,
        ]);

        for ($i = 1; $i <= 30; $i++) {
            Episode::create([
                'program_id' => $program->id,
                'title' => "Ders {$i}",
                'slug' => "ders-{$i}",
                'episode_number' => $i,
                'status' => 'published',
                'show_on_public' => true,
                'is_active' => true,
            ]);
        }

        $response = $this->get(route('programs.show', $program));

        $response->assertStatus(200);
        $response->assertSee('x-data="{ limit: 24, total: 30 }"', false);
        $response->assertSee('Daha Fazla Göster');
    }

    public function test_public_guard_prevents_draft_or_inactive_episodes_from_rendering(): void
    {
        $program = Program::create([
            'name' => 'Fıkıh Penceresi',
            'slug' => 'fikih-penceresi',
            'is_active' => true,
            'show_on_public' => true,
        ]);

        Episode::create([
            'program_id' => $program->id,
            'title' => 'Yayındaki Bölüm',
            'slug' => 'yayindaki-bolum',
            'episode_number' => 1,
            'status' => 'published',
            'show_on_public' => true,
            'is_active' => true,
        ]);

        Episode::create([
            'program_id' => $program->id,
            'title' => 'Taslak Bölüm',
            'slug' => 'taslak-bolum',
            'episode_number' => 2,
            'status' => 'draft',
            'show_on_public' => false,
            'is_active' => false,
        ]);

        $response = $this->get(route('programs.show', $program));

        $response->assertStatus(200);
        $response->assertSee('Yayındaki Bölüm');
        $response->assertDontSee('Taslak Bölüm');
    }
}
