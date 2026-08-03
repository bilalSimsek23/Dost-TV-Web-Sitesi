<?php

namespace Tests\Feature;

use App\Models\Program;
use App\Models\ScheduleException;
use App\Models\ScheduleTemplate;
use App\Models\ScheduleTemplateItem;
use App\Models\User;
use App\Services\Schedule\BroadcastScheduleResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleAutomationTest extends TestCase
{
    use RefreshDatabase;

    public function test_template_creation_and_version_publishing(): void
    {
        $template = ScheduleTemplate::create([
            'name' => 'Yaz Akışı 2026',
            'slug' => 'yaz-akisi-2026',
            'valid_from' => '2026-06-01',
            'valid_until' => '2026-09-15',
            'priority' => 5,
            'status' => 'draft',
        ]);

        $program = Program::create(['name' => 'Haberler', 'slug' => 'haberler', 'is_active' => true]);

        ScheduleTemplateItem::create([
            'schedule_template_id' => $template->id,
            'program_id' => $program->id,
            'day_of_week' => 0,
            'start_time' => '19:00',
            'end_time' => '20:00',
            'is_live' => true,
        ]);

        $this->assertEquals('draft', $template->status);
        $this->assertEquals(1, $template->version);

        // Publish template
        $history = $template->publish(null, 'İlk yayınlama v1');

        $this->assertEquals('published', $template->fresh()->status);
        $this->assertEquals(2, $template->fresh()->version);
        $this->assertCount(1, $template->versionHistories);
        $this->assertEquals(2, $history->version_number);
    }

    public function test_template_duplication_for_next_year(): void
    {
        $template = ScheduleTemplate::create([
            'name' => 'Yaz Akışı 2026',
            'slug' => 'yaz-akisi-2026',
            'valid_from' => '2026-06-01',
            'valid_until' => '2026-09-15',
            'priority' => 5,
            'status' => 'published',
        ]);

        $program = Program::create(['name' => 'Kuran Dersi', 'slug' => 'kuran-dersi', 'is_active' => true]);

        ScheduleTemplateItem::create([
            'schedule_template_id' => $template->id,
            'program_id' => $program->id,
            'day_of_week' => 1,
            'start_time' => '10:00',
            'end_time' => '11:00',
        ]);

        $nextYearTemplate = $template->duplicateForNextYear('Yaz Akışı 2027');

        $this->assertEquals('Yaz Akışı 2027', $nextYearTemplate->name);
        $this->assertEquals('draft', $nextYearTemplate->status);
        $this->assertEquals(1, $nextYearTemplate->version);
        $this->assertEquals('2027-06-01', $nextYearTemplate->valid_from->format('Y-m-d'));
        $this->assertEquals('2027-09-15', $nextYearTemplate->valid_until->format('Y-m-d'));
        $this->assertCount(1, $nextYearTemplate->items);
    }

    public function test_exception_day_overrides_template_schedule(): void
    {
        $template = ScheduleTemplate::create([
            'name' => 'Genel Akış',
            'slug' => 'genel-akis',
            'status' => 'published',
            'priority' => 1,
        ]);

        $progNormal = Program::create(['name' => 'Normal Program', 'slug' => 'normal-program', 'is_active' => true]);
        $progSpecial = Program::create(['name' => 'Bayram Özel', 'slug' => 'bayram-ozel', 'is_active' => true]);

        ScheduleTemplateItem::create([
            'schedule_template_id' => $template->id,
            'program_id' => $progNormal->id,
            'day_of_week' => 2, // Çarşamba
            'start_time' => '14:00',
            'end_time' => '15:00',
        ]);

        // Create Exception for a specific date
        $exceptionDate = '2026-07-15';
        $exception = ScheduleException::create([
            'exception_date' => $exceptionDate,
            'name' => '15 Temmuz Özel Yayını',
            'override_type' => 'replace_all',
            'status' => 'published',
        ]);

        $exception->items()->create([
            'program_id' => $progSpecial->id,
            'start_time' => '14:00',
            'end_time' => '18:00',
            'custom_title' => '15 Temmuz Özel Belgesel',
            'is_live' => true,
        ]);

        $resolver = new BroadcastScheduleResolver();
        $resolvedItems = $resolver->getScheduleForDate(now()->parse($exceptionDate));

        $this->assertCount(1, $resolvedItems);
        $this->assertEquals('15 Temmuz Özel Belgesel', $resolvedItems->first()->display_title);
    }

    public function test_public_schedule_page_and_admin_calendar_page(): void
    {
        $user = User::factory()->create(['role' => 'administrator']);

        // Public page
        $this->get('/yayin-akisi')->assertOk();

        // Admin page
        $this->actingAs($user)->get('/admin/schedule-calendar')->assertOk();
    }
}
