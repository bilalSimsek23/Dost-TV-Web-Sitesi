<?php

namespace Tests\Feature;

use App\Models\Episode;
use App\Models\Program;
use App\Models\ProgramSeason;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileSeasonSelectorTest extends TestCase
{
    use RefreshDatabase;

    public function test_initial_page_load_without_url_params_auto_selects_newest_season(): void
    {
        $program = Program::create([
            'name' => 'Hikmet Arayışları',
            'slug' => 'hikmet-arayislari',
            'is_active' => true,
            'show_on_public' => true,
        ]);

        ProgramSeason::create([
            'program_id' => $program->id,
            'season_number' => 1,
            'season_year' => '2022',
        ]);

        ProgramSeason::create([
            'program_id' => $program->id,
            'season_number' => 2,
            'season_year' => '2023',
        ]);

        ProgramSeason::create([
            'program_id' => $program->id,
            'season_number' => 3,
            'season_year' => '2026 Yaz Dönemi',
        ]);

        // Episodes for 2022
        Episode::create([
            'program_id' => $program->id,
            'season_number' => 1,
            'season_year' => '2022',
            'title' => '2022 Bölüm 1',
            'slug' => '2022-bolum-1',
            'video_source' => 'youtube',
            'youtube_url' => 'https://www.youtube.com/watch?v=VIDEO2022',
            'status' => 'published',
            'show_on_public' => true,
            'is_active' => true,
        ]);

        // Episodes for 2026
        Episode::create([
            'program_id' => $program->id,
            'season_number' => 3,
            'season_year' => '2026 Yaz Dönemi',
            'title' => '2026 Bölüm 1',
            'slug' => '2026-bolum-1',
            'video_source' => 'youtube',
            'youtube_url' => 'https://www.youtube.com/watch?v=VIDEO2026',
            'status' => 'published',
            'show_on_public' => true,
            'is_active' => true,
        ]);

        // Initial request with no season params
        $response = $this->get(route('programs.show', $program));

        $response->assertStatus(200);
        // Default selected season should be newest (2026 Yaz Dönemi)
        $response->assertSee('2026 Yaz Dönemi');
        $response->assertSee('2026 Bölüm 1');
        $response->assertSee('VIDEO2026');
        $response->assertDontSee('VIDEO2022');

        // Verify mobile selector markup is present
        $response->assertSee('id="mobile-season-dropdown"', false);
        $response->assertSee('aria-controls="mobile-season-dropdown"', false);
        $response->assertSee('Değiştir');
    }

    public function test_explicit_url_season_param_overrides_default_selection(): void
    {
        $program = Program::create([
            'name' => 'Rüyaların Dili',
            'slug' => 'ruyalarin-dili',
            'is_active' => true,
            'show_on_public' => true,
        ]);

        ProgramSeason::create([
            'program_id' => $program->id,
            'season_number' => 1,
            'season_year' => '2022',
        ]);

        ProgramSeason::create([
            'program_id' => $program->id,
            'season_number' => 2,
            'season_year' => '2023',
        ]);

        Episode::create([
            'program_id' => $program->id,
            'season_number' => 1,
            'season_year' => '2022',
            'title' => '2022 Özel Yayın',
            'slug' => '2022-ozel-yayin',
            'video_source' => 'youtube',
            'youtube_url' => 'https://www.youtube.com/watch?v=R2022',
            'status' => 'published',
            'show_on_public' => true,
            'is_active' => true,
        ]);

        Episode::create([
            'program_id' => $program->id,
            'season_number' => 2,
            'season_year' => '2023',
            'title' => '2023 Yeni Yayın',
            'slug' => '2023-yeni-yayin',
            'video_source' => 'youtube',
            'youtube_url' => 'https://www.youtube.com/watch?v=R2023',
            'status' => 'published',
            'show_on_public' => true,
            'is_active' => true,
        ]);

        // Explicitly request season 1 (2022)
        $response = $this->get(route('programs.show', [
            'program' => $program,
            'season' => 1,
            'year' => '2022',
        ]));

        $response->assertStatus(200);
        $response->assertSee('2022 Özel Yayın');
        $response->assertSee('R2022');
        $response->assertDontSee('R2023');
    }

    public function test_dropdown_menu_contains_all_seasons_with_scrollable_panel(): void
    {
        $program = Program::create([
            'name' => 'Beraber Okuyalım',
            'slug' => 'beraber-okuyalim',
            'is_active' => true,
            'show_on_public' => true,
        ]);

        for ($i = 1; $i <= 5; $i++) {
            ProgramSeason::create([
                'program_id' => $program->id,
                'season_number' => $i,
                'season_year' => (2020 + $i) . ' Sezonu',
            ]);

            Episode::create([
                'program_id' => $program->id,
                'season_number' => $i,
                'season_year' => (2020 + $i) . ' Sezonu',
                'title' => (2020 + $i) . ' Ders 1',
                'slug' => (2020 + $i) . '-ders-1',
                'video_source' => 'youtube',
                'youtube_url' => 'https://www.youtube.com/watch?v=VIDEO' . $i,
                'status' => 'published',
                'show_on_public' => true,
                'is_active' => true,
            ]);
        }

        $response = $this->get(route('programs.show', $program));

        $response->assertStatus(200);
        // All 5 seasons present in dropdown
        for ($i = 1; $i <= 5; $i++) {
            $response->assertSee((2020 + $i) . ' Sezonu');
        }
        $response->assertSee('max-h-72 overflow-y-auto', false);
    }
}
