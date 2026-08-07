<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Episode;
use App\Models\Program;
use App\Models\Schedule;
use App\Models\ScheduleTemplateItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class DeduplicateProgramsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_dry_run_outputs_duplicate_groups_without_modifying_database(): void
    {
        $mainProg = Program::create(['name' => 'Bab-ı Reyyan', 'slug' => 'bab-i-reyyan-2']);
        $dupProg = Program::create(['name' => 'Bab-ı Reyyan', 'slug' => 'bab-i-reyyan']);

        Artisan::call('programs:deduplicate', ['--dry-run' => true]);
        $output = Artisan::output();

        $this->assertStringContainsString('DUPLICATE PROGRAM TEMİZLEME (SIMULATION MODE --dry-run)', $output);
        $this->assertStringContainsString('Bab-ı Reyyan', $output);
        $this->assertDatabaseHas('programs', ['id' => $mainProg->id]);
        $this->assertDatabaseHas('programs', ['id' => $dupProg->id]);
    }

    public function test_real_deduplication_transfers_relations_fixes_slug_and_creates_snapshot(): void
    {
        $category = Category::create(['name' => 'Dini']);

        // Main program with template items
        $mainProg = Program::create([
            'name' => 'Bab-ı Reyyan',
            'slug' => 'bab-i-reyyan-2',
            'status' => 'active',
        ]);
        $template = \App\Models\ScheduleTemplate::create(['name' => 'Varsayılan Şablon', 'status' => 'active']);
        $templateItem = ScheduleTemplateItem::create([
            'schedule_template_id' => $template->id,
            'program_id' => $mainProg->id,
            'day_of_week' => 1,
            'start_time' => '10:00',
            'end_time' => '11:00',
        ]);
        $mainProg->categories()->attach($category);

        // Duplicate program with episode
        $dupProg = Program::create([
            'name' => 'Bab-ı Reyyan',
            'slug' => 'bab-i-reyyan',
            'status' => 'active',
        ]);
        $ep = Episode::create([
            'program_id' => $dupProg->id,
            'title' => 'Taşınacak Bölüm',
            'episode_number' => 1,
            'status' => 'published',
        ]);

        Artisan::call('programs:deduplicate');
        $output = Artisan::output();

        $this->assertStringContainsString('başarıyla tamamlandı', $output);

        // Program with episode (dupProg) becomes canonical main record because episode priority is #1
        $this->assertDatabaseMissing('programs', ['id' => $mainProg->id]);
        $this->assertDatabaseHas('programs', ['id' => $dupProg->id]);

        // ScheduleTemplateItem program_id is transferred to canonical program
        $this->assertEquals($dupProg->id, $templateItem->fresh()->program_id);

        // Slug is updated to clean slug bab-i-reyyan
        $this->assertEquals('bab-i-reyyan', $dupProg->fresh()->slug);

        // Snapshot JSON file is created in storage/logs
        $files = File::glob(storage_path('logs/program_deduplicate_snapshot_*.json'));
        $this->assertNotEmpty($files);
    }
}
