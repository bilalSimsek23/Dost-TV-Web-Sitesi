<?php

namespace Tests\Feature;

use App\Models\Episode;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class RenumberProgramEpisodesTest extends TestCase
{
    use RefreshDatabase;

    protected Program $programAklaKapi;

    protected Program $programOther;

    protected function setUp(): void
    {
        parent::setUp();

        $this->programAklaKapi = Program::create([
            'name' => 'Akla Kapı',
            'slug' => 'akla-kapi',
            'status' => 'active',
        ]);

        $this->programOther = Program::create([
            'name' => 'Başka Program',
            'slug' => 'baska-program',
            'status' => 'active',
        ]);
    }

    public function test_dry_run_outputs_table_without_modifying_database(): void
    {
        Episode::create([
            'program_id' => $this->programAklaKapi->id,
            'title' => 'Bölüm B',
            'episode_number' => 27,
            'aired_at' => '2024-02-01',
            'youtube_url' => 'https://www.youtube.com/watch?v=TEST_VID_B02',
        ]);

        Episode::create([
            'program_id' => $this->programAklaKapi->id,
            'title' => 'Bölüm A',
            'episode_number' => 1,
            'aired_at' => '2024-01-01',
            'youtube_url' => 'https://www.youtube.com/watch?v=TEST_VID_A01',
        ]);

        Artisan::call('episodes:renumber-program', [
            '--program' => $this->programAklaKapi->id,
            '--dry-run' => true,
        ]);

        $output = Artisan::output();

        $this->assertStringContainsString('SIMULATION MODE', $output);
        $this->assertStringContainsString('Akla Kapı', $output);
        $this->assertStringContainsString('Veritabanında hiçbir değişiklik yapılmadı', $output);

        // Episode numbers remain untouched in dry run
        $this->assertEquals(27, Episode::where('title', 'Bölüm B')->first()->episode_number);
        $this->assertEquals(1, Episode::where('title', 'Bölüm A')->first()->episode_number);
    }

    public function test_renumber_orders_episodes_chronologically_by_aired_at_asc(): void
    {
        $epC = Episode::create([
            'program_id' => $this->programAklaKapi->id,
            'title' => 'Bölüm Mart',
            'episode_number' => 1,
            'aired_at' => '2024-03-01',
            'youtube_url' => 'https://www.youtube.com/watch?v=TEST_VID_C03',
        ]);

        $epA = Episode::create([
            'program_id' => $this->programAklaKapi->id,
            'title' => 'Bölüm Ocak',
            'episode_number' => 27,
            'aired_at' => '2024-01-01',
            'youtube_url' => 'https://www.youtube.com/watch?v=TEST_VID_A01',
        ]);

        $epB = Episode::create([
            'program_id' => $this->programAklaKapi->id,
            'title' => 'Bölüm Şubat',
            'episode_number' => 26,
            'aired_at' => '2024-02-01',
            'youtube_url' => 'https://www.youtube.com/watch?v=TEST_VID_B02',
        ]);

        Artisan::call('episodes:renumber-program', [
            '--program' => $this->programAklaKapi->id,
        ]);

        $output = Artisan::output();
        $this->assertStringContainsString('3 bölüm güncellendi', $output);

        // Assert chronological assignment
        $this->assertEquals(1, $epA->fresh()->episode_number);
        $this->assertEquals(2, $epB->fresh()->episode_number);
        $this->assertEquals(3, $epC->fresh()->episode_number);

        // Assert titles, youtube_urls and metadata are unchanged
        $this->assertEquals('Bölüm Ocak', $epA->fresh()->title);
        $this->assertEquals('https://www.youtube.com/watch?v=TEST_VID_A01', $epA->fresh()->youtube_url);
    }

    public function test_other_programs_are_not_affected(): void
    {
        $otherEp = Episode::create([
            'program_id' => $this->programOther->id,
            'title' => 'Diğer Program Bölümü',
            'episode_number' => 99,
            'aired_at' => '2024-01-01',
        ]);

        Episode::create([
            'program_id' => $this->programAklaKapi->id,
            'title' => 'Akla Kapı Bölümü',
            'episode_number' => 5,
            'aired_at' => '2024-01-01',
        ]);

        Artisan::call('episodes:renumber-program', [
            '--program' => $this->programAklaKapi->id,
        ]);

        $this->assertEquals(99, $otherEp->fresh()->episode_number);
    }

    public function test_null_aired_at_episodes_are_placed_last(): void
    {
        $epNull = Episode::create([
            'program_id' => $this->programAklaKapi->id,
            'title' => 'Tarihsiz Bölüm',
            'episode_number' => 1,
            'aired_at' => null,
        ]);

        $epDated = Episode::create([
            'program_id' => $this->programAklaKapi->id,
            'title' => 'Tarihli Bölüm',
            'episode_number' => 5,
            'aired_at' => '2024-01-01',
        ]);

        Artisan::call('episodes:renumber-program', [
            '--program' => $this->programAklaKapi->id,
        ]);

        $this->assertEquals(1, $epDated->fresh()->episode_number);
        $this->assertEquals(2, $epNull->fresh()->episode_number);
    }
}
