<?php

namespace Tests\Feature;

use App\Models\Program;
use App\Models\ProgramSeason;
use App\Models\ProgramSeries;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgramSeasonSeriesUniqueConstraintsTest extends TestCase
{
    use RefreshDatabase;

    public function test_same_program_and_season_number_and_same_year_is_blocked(): void
    {
        $program = Program::create(['name' => 'Program A', 'slug' => 'program-a']);

        ProgramSeason::create([
            'program_id' => $program->id,
            'season_number' => 1,
            'season_year' => '2026',
        ]);

        $this->expectException(QueryException::class);

        ProgramSeason::create([
            'program_id' => $program->id,
            'season_number' => 1,
            'season_year' => '2026',
        ]);
    }

    public function test_same_program_and_season_number_with_different_year_is_allowed(): void
    {
        $program = Program::create(['name' => 'Program A', 'slug' => 'program-a']);

        $s1 = ProgramSeason::create([
            'program_id' => $program->id,
            'season_number' => 1,
            'season_year' => '2025',
        ]);

        $s2 = ProgramSeason::create([
            'program_id' => $program->id,
            'season_number' => 1,
            'season_year' => '2026',
        ]);

        $this->assertNotEquals($s1->id, $s2->id);
        $this->assertEquals(2, ProgramSeason::where('program_id', $program->id)->count());
    }

    public function test_same_program_and_season_number_with_null_year_is_blocked_on_second_null_record(): void
    {
        $program = Program::create(['name' => 'Program A', 'slug' => 'program-a']);

        ProgramSeason::create([
            'program_id' => $program->id,
            'season_number' => 1,
            'season_year' => null,
        ]);

        $this->expectException(QueryException::class);

        ProgramSeason::create([
            'program_id' => $program->id,
            'season_number' => 1,
            'season_year' => null,
        ]);
    }

    public function test_different_programs_can_have_identical_season_number_and_year(): void
    {
        $prog1 = Program::create(['name' => 'Program 1', 'slug' => 'program-1']);
        $prog2 = Program::create(['name' => 'Program 2', 'slug' => 'program-2']);

        $s1 = ProgramSeason::create([
            'program_id' => $prog1->id,
            'season_number' => 1,
            'season_year' => '2026',
        ]);

        $s2 = ProgramSeason::create([
            'program_id' => $prog2->id,
            'season_number' => 1,
            'season_year' => '2026',
        ]);

        $this->assertDatabaseHas('program_seasons', ['id' => $s1->id, 'program_id' => $prog1->id]);
        $this->assertDatabaseHas('program_seasons', ['id' => $s2->id, 'program_id' => $prog2->id]);
    }

    public function test_different_programs_can_have_null_season_year(): void
    {
        $prog1 = Program::create(['name' => 'Program 1', 'slug' => 'program-1']);
        $prog2 = Program::create(['name' => 'Program 2', 'slug' => 'program-2']);

        $s1 = ProgramSeason::create([
            'program_id' => $prog1->id,
            'season_number' => 1,
            'season_year' => null,
        ]);

        $s2 = ProgramSeason::create([
            'program_id' => $prog2->id,
            'season_number' => 1,
            'season_year' => null,
        ]);

        $this->assertDatabaseHas('program_seasons', ['id' => $s1->id, 'program_id' => $prog1->id]);
        $this->assertDatabaseHas('program_seasons', ['id' => $s2->id, 'program_id' => $prog2->id]);
    }

    public function test_same_program_with_duplicate_series_name_is_blocked(): void
    {
        $program = Program::create(['name' => 'Beraber Okuyalım', 'slug' => 'beraber-okuyalim']);

        ProgramSeries::create([
            'program_id' => $program->id,
            'name' => 'Lemalar',
            'slug' => 'lemalar',
        ]);

        $this->expectException(QueryException::class);

        ProgramSeries::create([
            'program_id' => $program->id,
            'name' => 'Lemalar',
            'slug' => 'lemalar-2',
        ]);
    }

    public function test_different_programs_can_have_same_series_name(): void
    {
        $prog1 = Program::create(['name' => 'Program 1', 'slug' => 'prog-1']);
        $prog2 = Program::create(['name' => 'Program 2', 'slug' => 'prog-2']);

        $sr1 = ProgramSeries::create([
            'program_id' => $prog1->id,
            'name' => 'Sözler',
            'slug' => 'sozler-1',
        ]);

        $sr2 = ProgramSeries::create([
            'program_id' => $prog2->id,
            'name' => 'Sözler',
            'slug' => 'sozler-2',
        ]);

        $this->assertDatabaseHas('program_series', ['id' => $sr1->id, 'program_id' => $prog1->id, 'name' => 'Sözler']);
        $this->assertDatabaseHas('program_series', ['id' => $sr2->id, 'program_id' => $prog2->id, 'name' => 'Sözler']);
    }

    public function test_same_program_with_duplicate_series_slug_is_blocked(): void
    {
        $program = Program::create(['name' => 'Program A', 'slug' => 'program-a']);

        ProgramSeries::create([
            'program_id' => $program->id,
            'name' => 'Özel Seri 1',
            'slug' => 'ozel-seri',
        ]);

        $this->expectException(QueryException::class);

        ProgramSeries::create([
            'program_id' => $program->id,
            'name' => 'Özel Seri 2',
            'slug' => 'ozel-seri',
        ]);
    }
}
