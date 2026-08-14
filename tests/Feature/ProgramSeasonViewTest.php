<?php

namespace Tests\Feature;

use App\Models\Episode;
use App\Models\Program;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgramSeasonViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_program_with_multiple_seasons_orders_seasons_desc_and_defaults_to_highest_season(): void
    {
        $program = Program::create([
            'name' => 'Hikmet Arayışları',
            'slug' => 'hikmet-arayislari',
            'status' => 'active',
            'is_active' => true,
            'show_on_public' => true,
        ]);

        // Create 2 episodes for Season 1 (2017)
        Episode::create([
            'program_id' => $program->id,
            'title' => 'S1 Bölüm 1',
            'episode_number' => 1,
            'season_number' => 1,
            'season_year' => 2017,
            'status' => 'published',
        ]);
        Episode::create([
            'program_id' => $program->id,
            'title' => 'S1 Bölüm 2',
            'episode_number' => 2,
            'season_number' => 1,
            'season_year' => 2017,
            'status' => 'published',
        ]);

        // Create 3 episodes for Season 2 (2018)
        for ($i = 1; $i <= 3; $i++) {
            Episode::create([
                'program_id' => $program->id,
                'title' => "S2 Bölüm {$i}",
                'episode_number' => $i,
                'season_number' => 2,
                'season_year' => 2018,
                'status' => 'published',
            ]);
        }

        // Create 1 episode for Season 4 (2020)
        Episode::create([
            'program_id' => $program->id,
            'title' => 'S4 Bölüm 1',
            'episode_number' => 1,
            'season_number' => 4,
            'season_year' => 2020,
            'status' => 'published',
        ]);

        $response = $this->get('/programlar/hikmet-arayislari');
        $response->assertOk();

        // 1. "Sezonlar" header is always fixed and present
        $response->assertSee('Sezonlar');
        $response->assertSee('İzlemek istediğiniz sezonu seçin.');

        // 2. Year exists -> only year is shown, season numbers are hidden
        $response->assertSee('2020');
        $response->assertSee('2018');
        $response->assertSee('2017');
        $response->assertDontSee('Sezon 4');
        $response->assertDontSee('Sezon 2');
        $response->assertDontSee('Sezon 1');

        // 3. Selected status text uses clean dynamic label
        $response->assertSee('Seçili: 2020 · 1 Bölüm');

        // 4. Seasons are displayed in DESC order: 2020 before 2018 before 2017
        $content = $response->getContent();
        $pos2020 = strpos($content, '2020');
        $pos2018 = strpos($content, '2018');
        $pos2017 = strpos($content, '2017');

        $this->assertNotFalse($pos2020);
        $this->assertNotFalse($pos2018);
        $this->assertNotFalse($pos2017);
        $this->assertTrue($pos2020 < $pos2018);
        $this->assertTrue($pos2018 < $pos2017);

        // Default active season is 2020 (1 episode shown)
        $response->assertSee('S4 Bölüm 1');
        $response->assertDontSee('S1 Bölüm 1');
        $response->assertDontSee('S2 Bölüm 1');
    }

    public function test_season_query_parameter_filters_episodes_and_orders_episode_number_asc(): void
    {
        $program = Program::create([
            'name' => 'Hikmet Arayışları',
            'slug' => 'hikmet-arayislari',
            'status' => 'active',
            'is_active' => true,
            'show_on_public' => true,
        ]);

        // Season 1: B2 created first, B1 created second
        Episode::create([
            'program_id' => $program->id,
            'title' => 'S1 Bölüm 2',
            'episode_number' => 2,
            'season_number' => 1,
            'season_year' => 2017,
            'status' => 'published',
        ]);
        Episode::create([
            'program_id' => $program->id,
            'title' => 'S1 Bölüm 1',
            'episode_number' => 1,
            'season_number' => 1,
            'season_year' => 2017,
            'status' => 'published',
        ]);

        // Season 2
        Episode::create([
            'program_id' => $program->id,
            'title' => 'S2 Bölüm 1',
            'episode_number' => 1,
            'season_number' => 2,
            'season_year' => 2018,
            'status' => 'published',
        ]);

        // Request Season 1 via query params
        $response = $this->get('/programlar/hikmet-arayislari?season=1&year=2017');
        $response->assertOk();

        $response->assertSee('S1 Bölüm 1');
        $response->assertSee('S1 Bölüm 2');
        $response->assertDontSee('S2 Bölüm 1');

        // Verify natural ascending order: B1 before B2
        $content = $response->getContent();
        $posB1 = strpos($content, 'S1 Bölüm 1');
        $posB2 = strpos($content, 'S1 Bölüm 2');
        $this->assertTrue($posB1 < $posB2);
    }

    public function test_season_year_is_displayed_when_present_and_omitted_when_null(): void
    {
        $program = Program::create([
            'name' => 'Sezonlu Program',
            'slug' => 'sezonlu-program',
            'status' => 'active',
            'is_active' => true,
            'show_on_public' => true,
        ]);

        Episode::create([
            'program_id' => $program->id,
            'title' => 'Bölüm 1',
            'episode_number' => 1,
            'season_number' => 3,
            'season_year' => null,
            'status' => 'published',
        ]);

        Episode::create([
            'program_id' => $program->id,
            'title' => 'Bölüm 2',
            'episode_number' => 2,
            'season_number' => 2,
            'season_year' => 2024,
            'status' => 'published',
        ]);

        $response = $this->get('/programlar/sezonlu-program');
        $response->assertOk();

        // Season 3 has no year -> falls back to "Sezon 3"
        $response->assertSee('Sezon 3');
        // Season 2 has year 2024 -> only "2024" is displayed
        $response->assertSee('2024');
        $response->assertDontSee('Sezon 2');
    }

    public function test_seasonless_program_displays_all_episodes_without_season_selector(): void
    {
        $program = Program::create([
            'name' => 'Sezonsuz Program',
            'slug' => 'sezonsuz-program',
            'status' => 'active',
            'is_active' => true,
            'show_on_public' => true,
        ]);

        Episode::create([
            'program_id' => $program->id,
            'title' => 'Düz Bölüm 1',
            'episode_number' => 1,
            'season_number' => null,
            'status' => 'published',
        ]);

        Episode::create([
            'program_id' => $program->id,
            'title' => 'Düz Bölüm 2',
            'episode_number' => 2,
            'season_number' => null,
            'status' => 'published',
        ]);

        $response = $this->get('/programlar/sezonsuz-program');
        $response->assertOk();

        $response->assertDontSee('İzlemek istediğiniz sezonu seçin.');
        $response->assertSee('Düz Bölüm 1');
        $response->assertSee('Düz Bölüm 2');
    }

    public function test_program_with_year_range_season_displays_and_filters_correctly(): void
    {
        $program = Program::create([
            'name' => 'Dönemli Program',
            'slug' => 'donemli-program',
            'status' => 'active',
            'is_active' => true,
            'show_on_public' => true,
        ]);

        Episode::create([
            'program_id' => $program->id,
            'title' => 'S6 B1 Açılış',
            'episode_number' => 1,
            'season_number' => 6,
            'season_year' => '2022-2023',
            'status' => 'published',
        ]);

        Episode::create([
            'program_id' => $program->id,
            'title' => 'S5 B1 Geçmiş',
            'episode_number' => 1,
            'season_number' => 5,
            'season_year' => '2021-2022',
            'status' => 'published',
        ]);

        $response = $this->get('/programlar/donemli-program');
        $response->assertOk();
        $response->assertSee('2022-2023');
        $response->assertSee('2021-2022');
        $response->assertDontSee('Sezon 6');
        $response->assertDontSee('Sezon 5');
        $response->assertSee('S6 B1 Açılış');

        $filteredResponse = $this->get('/programlar/donemli-program?season=5&year=2021-2022');
        $filteredResponse->assertOk();
        $filteredResponse->assertSee('S5 B1 Geçmiş');
        $filteredResponse->assertDontSee('S6 B1 Açılış');
    }
}

