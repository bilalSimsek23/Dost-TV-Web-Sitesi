<?php

namespace Tests\Feature;

use App\Models\Episode;
use App\Models\Program;
use App\Models\ProgramSeason;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgramSeasonPlayerSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_player_video_synchronizes_with_selected_season(): void
    {
        $program = Program::create([
            'name' => 'Seherler ve Sahurlar',
            'slug' => 'seherler-ve-sahurlar',
            'is_active' => true,
            'show_on_public' => true,
        ]);

        $season2025 = ProgramSeason::create([
            'program_id' => $program->id,
            'season_number' => 1,
            'season_year' => '2025',
        ]);

        $season2026 = ProgramSeason::create([
            'program_id' => $program->id,
            'season_number' => 2,
            'season_year' => '2026',
        ]);

        $ep2025 = Episode::create([
            'program_id' => $program->id,
            'season_number' => 1,
            'season_year' => '2025',
            'title' => '2025 Ramazan 1. Gün',
            'slug' => '2025-ramazan-1-gun',
            'youtube_url' => 'https://www.youtube.com/watch?v=VIDEO2025',
            'video_source' => 'youtube',
            'status' => 'published',
            'show_on_public' => true,
            'is_active' => true,
        ]);

        $ep2026 = Episode::create([
            'program_id' => $program->id,
            'season_number' => 2,
            'season_year' => '2026',
            'title' => '2026 Ramazan 1. Gün',
            'slug' => '2026-ramazan-1-gun',
            'youtube_url' => 'https://www.youtube.com/watch?v=VIDEO2026',
            'video_source' => 'youtube',
            'status' => 'published',
            'show_on_public' => true,
            'is_active' => true,
        ]);

        // Request 2025 season
        $response2025 = $this->get(route('programs.show', [
            'program' => $program,
            'season' => 1,
            'year' => '2025',
        ]));

        $response2025->assertStatus(200);
        $response2025->assertSee('2025 Ramazan 1. Gün');
        $response2025->assertDontSee('2026 Ramazan 1. Gün');
        $response2025->assertSee('VIDEO2025');
        $response2025->assertDontSee('VIDEO2026');

        // Request 2026 season
        $response2026 = $this->get(route('programs.show', [
            'program' => $program,
            'season' => 2,
            'year' => '2026',
        ]));

        $response2026->assertStatus(200);
        $response2026->assertSee('2026 Ramazan 1. Gün');
        $response2026->assertDontSee('2025 Ramazan 1. Gün');
        $response2026->assertSee('VIDEO2026');
        $response2026->assertDontSee('VIDEO2025');
    }

    public function test_empty_season_does_not_leak_episodes_from_other_seasons(): void
    {
        $program = Program::create([
            'name' => 'Akla Kapı',
            'slug' => 'akla-kapi',
            'trailer_url' => 'https://www.youtube.com/watch?v=TRAILER1234',
            'is_active' => true,
            'show_on_public' => true,
        ]);

        ProgramSeason::create([
            'program_id' => $program->id,
            'season_number' => 1,
            'season_year' => '2024',
        ]);

        ProgramSeason::create([
            'program_id' => $program->id,
            'season_number' => 2,
            'season_year' => '2025',
        ]);

        Episode::create([
            'program_id' => $program->id,
            'season_number' => 1,
            'season_year' => '2024',
            'title' => '2024 Eski Bölüm',
            'slug' => '2024-eski-bolum',
            'youtube_url' => 'https://www.youtube.com/watch?v=ESKIVIDEO11',
            'video_source' => 'youtube',
            'status' => 'published',
            'show_on_public' => true,
            'is_active' => true,
        ]);

        // 2025 season has no episodes
        $response = $this->get(route('programs.show', [
            'program' => $program,
            'season' => 2,
            'year' => '2025',
        ]));

        $response->assertStatus(200);
        $response->assertDontSee('ESKIVIDEO11');
        $response->assertSee('TRAILER1234');
        $response->assertSee('Bu sezon için video bulunamadı.');
    }

    public function test_seasonless_program_player_remains_intact(): void
    {
        $program = Program::create([
            'name' => 'Düz Program',
            'slug' => 'duz-program',
            'is_active' => true,
            'show_on_public' => true,
        ]);

        Episode::create([
            'program_id' => $program->id,
            'title' => 'Düz Bölüm 1',
            'slug' => 'duz-bolum-1',
            'youtube_url' => 'https://www.youtube.com/watch?v=DUZVIDEO1',
            'video_source' => 'youtube',
            'status' => 'published',
            'show_on_public' => true,
            'is_active' => true,
        ]);

        $response = $this->get(route('programs.show', $program));

        $response->assertStatus(200);
        $response->assertSee('Düz Bölüm 1');
        $response->assertSee('DUZVIDEO1');
    }
}
