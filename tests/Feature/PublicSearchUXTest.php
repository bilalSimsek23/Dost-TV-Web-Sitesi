<?php

namespace Tests\Feature;

use App\Models\Episode;
use App\Models\Program;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSearchUXTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_returns_programs_first_and_prioritizes_relevance(): void
    {
        $hikmetProgram = Program::create([
            'name' => 'Hikmet Arayışları',
            'slug' => 'hikmet-arayislari',
            'is_active' => true,
            'show_on_public' => true,
        ]);

        $hikmetEpisode = Episode::create([
            'program_id' => $hikmetProgram->id,
            'title' => 'Hikmet Dersleri 1. Bölüm',
            'slug' => 'hikmet-dersleri-1-bolum',
            'status' => 'published',
            'show_on_public' => true,
            'is_active' => true,
        ]);

        $response = $this->get(route('search.index', ['q' => 'hikmet']));

        $response->assertStatus(200);
        $response->assertSee('Hikmet Arayışları');
        $response->assertSee('Hikmet Dersleri 1. Bölüm');
        $response->assertSee('Tümü');
        $response->assertSee('Programlar');
        $response->assertSee('Bölümler');
    }

    public function test_all_tab_limits_episodes_preview_to_6_and_shows_view_all_link(): void
    {
        $program = Program::create([
            'name' => 'Tarih Programı',
            'slug' => 'tarih-programi',
            'is_active' => true,
            'show_on_public' => true,
        ]);

        for ($i = 1; $i <= 8; $i++) {
            Episode::create([
                'program_id' => $program->id,
                'title' => "Tarih Bölümü {$i}",
                'slug' => "tarih-bolumu-{$i}",
                'status' => 'published',
                'show_on_public' => true,
                'is_active' => true,
            ]);
        }

        $response = $this->get(route('search.index', ['q' => 'Tarih', 'type' => 'all']));

        $response->assertStatus(200);
        $response->assertSee('Tüm Bölüm Sonuçlarını Gör (8)');
    }

    public function test_programs_tab_returns_only_programs(): void
    {
        $program = Program::create([
            'name' => 'Sözler Dersleri',
            'slug' => 'sozler-dersleri',
            'is_active' => true,
            'show_on_public' => true,
        ]);

        Episode::create([
            'program_id' => $program->id,
            'title' => 'Sözler 1. Bölüm',
            'slug' => 'sozler-1-bolum',
            'status' => 'published',
            'show_on_public' => true,
            'is_active' => true,
        ]);

        $response = $this->get(route('search.index', ['q' => 'Sözler', 'type' => 'programs']));

        $response->assertStatus(200);
        $response->assertSee('Sözler Dersleri');
        $response->assertDontSee('Sözler 1. Bölüm');
    }

    public function test_episodes_tab_returns_only_episodes(): void
    {
        $program = Program::create([
            'name' => 'Fıkıh Penceresi',
            'slug' => 'fikih-penceresi',
            'is_active' => true,
            'show_on_public' => true,
        ]);

        Episode::create([
            'program_id' => $program->id,
            'title' => 'Fıkıh Özel Bölüm',
            'slug' => 'fikih-ozel-bolum',
            'status' => 'published',
            'show_on_public' => true,
            'is_active' => true,
        ]);

        $response = $this->get(route('search.index', ['q' => 'Fıkıh', 'type' => 'episodes']));

        $response->assertStatus(200);
        $response->assertSee('Fıkıh Özel Bölüm');
    }

    public function test_non_public_content_is_excluded_from_search(): void
    {
        $privateProgram = Program::create([
            'name' => 'Gizli Program',
            'slug' => 'gizli-program',
            'is_active' => false,
            'show_on_public' => false,
        ]);

        Episode::create([
            'program_id' => $privateProgram->id,
            'title' => 'Gizli Bölüm',
            'slug' => 'gizli-bolum',
            'status' => 'draft',
            'show_on_public' => false,
            'is_active' => false,
        ]);

        $response = $this->get(route('search.index', ['q' => 'Gizli']));

        $response->assertStatus(200);
        $response->assertDontSee('Gizli Program');
        $response->assertDontSee('Gizli Bölüm');
    }
}
