<?php

namespace Tests\Feature;

use App\Models\Program;
use App\Models\ScheduleTemplate;
use App\Models\ScheduleTemplateItem;
use App\Services\Home\HomepageDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivePeriodScheduleProgramShowcaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_period_weekly_schedule_resolves_unique_programs_across_seven_days()
    {
        // 1. Create 3 test programs
        $progMon = Program::factory()->create(['name' => 'Pazartesi Programı', 'show_on_public' => true, 'is_active' => true]);
        $progTue = Program::factory()->create(['name' => 'Salı Programı', 'show_on_public' => true, 'is_active' => true]);
        $progMulti = Program::factory()->create(['name' => 'Çoklu Gün Programı', 'show_on_public' => true, 'is_active' => true]);

        // 2. Create an active published schedule template (August period)
        $template = ScheduleTemplate::create([
            'name' => 'Ağustos Yayın Dönemi',
            'valid_from' => '2026-08-01',
            'valid_until' => '2026-08-31',
            'status' => 'published',
            'is_active' => true,
            'priority' => 10,
        ]);

        // Schedule items:
        // Mon 08:00 -> progMon
        // Mon 10:00 -> progMulti (first appearance of progMulti)
        // Tue 09:00 -> progTue
        // Wed 14:00 -> progMulti (second appearance of progMulti -> must be deduplicated!)
        ScheduleTemplateItem::create(['schedule_template_id' => $template->id, 'day_of_week' => 0, 'start_time' => '08:00:00', 'end_time' => '09:00:00', 'program_id' => $progMon->id, 'is_active' => true]);
        ScheduleTemplateItem::create(['schedule_template_id' => $template->id, 'day_of_week' => 0, 'start_time' => '10:00:00', 'end_time' => '11:00:00', 'program_id' => $progMulti->id, 'is_active' => true]);
        ScheduleTemplateItem::create(['schedule_template_id' => $template->id, 'day_of_week' => 1, 'start_time' => '09:00:00', 'end_time' => '10:00:00', 'program_id' => $progTue->id, 'is_active' => true]);
        ScheduleTemplateItem::create(['schedule_template_id' => $template->id, 'day_of_week' => 2, 'start_time' => '14:00:00', 'end_time' => '15:00:00', 'program_id' => $progMulti->id, 'is_active' => true]);

        // Travel in time to August 15th, 2026
        $this->travelTo('2026-08-15 12:00:00');

        $service = app(HomepageDataService::class);

        // 3. Resolve active period programs
        $resolved = $service->resolveActivePeriodWeeklyPrograms();

        $this->assertCount(3, $resolved);

        // Order: progMon, progMulti, progTue
        $ids = $resolved->pluck('id')->toArray();
        $this->assertEquals([$progMon->id, $progMulti->id, $progTue->id], $ids);
    }

    public function test_automatic_period_switching_when_date_changes()
    {
        $progSummer = Program::factory()->create(['name' => 'Yaz Programı', 'show_on_public' => true, 'is_active' => true]);
        $progAutumn = Program::factory()->create(['name' => 'Sonbahar Programı', 'show_on_public' => true, 'is_active' => true]);

        // Summer Template: Aug 1 to Aug 31
        $summerTemplate = ScheduleTemplate::create([
            'name' => 'Yaz Dönemi',
            'valid_from' => '2026-08-01',
            'valid_until' => '2026-08-31',
            'status' => 'published',
            'is_active' => true,
            'priority' => 10,
        ]);
        ScheduleTemplateItem::create(['schedule_template_id' => $summerTemplate->id, 'day_of_week' => 0, 'start_time' => '08:00:00', 'end_time' => '09:00:00', 'program_id' => $progSummer->id, 'is_active' => true]);

        // Autumn Template: Sept 1 to Dec 31
        $autumnTemplate = ScheduleTemplate::create([
            'name' => 'Sonbahar Dönemi',
            'valid_from' => '2026-09-01',
            'valid_until' => '2026-12-31',
            'status' => 'published',
            'is_active' => false,
            'priority' => 10,
        ]);
        ScheduleTemplateItem::create(['schedule_template_id' => $autumnTemplate->id, 'day_of_week' => 0, 'start_time' => '08:00:00', 'end_time' => '09:00:00', 'program_id' => $progAutumn->id, 'is_active' => true]);

        $service = app(HomepageDataService::class);

        // 1. On August 27 -> resolves Summer Program
        $this->travelTo('2026-08-27 12:00:00');
        $augustResolved = $service->resolveActivePeriodWeeklyPrograms();
        $this->assertCount(1, $augustResolved);
        $this->assertEquals($progSummer->id, $augustResolved->first()->id);

        // 2. On September 1 -> when Autumn template activates, resolves Autumn Program
        $autumnTemplate->update(['is_active' => true, 'status' => 'published']);
        $this->travelTo('2026-09-01 12:00:00');
        $septemberResolved = $service->resolveActivePeriodWeeklyPrograms();
        $this->assertCount(1, $septemberResolved);
        $this->assertEquals($progAutumn->id, $septemberResolved->first()->id);
    }

    public function test_program_showcase_block_uses_active_period_schedule_by_default()
    {
        $prog = Program::factory()->create(['name' => 'Özel Program', 'show_on_public' => true, 'is_active' => true]);

        $template = ScheduleTemplate::create([
            'name' => 'Genel Akış',
            'status' => 'published',
            'is_active' => true,
            'priority' => 1,
        ]);
        ScheduleTemplateItem::create(['schedule_template_id' => $template->id, 'day_of_week' => 0, 'start_time' => '10:00:00', 'end_time' => '11:00:00', 'program_id' => $prog->id, 'is_active' => true]);

        $block = [
            'uuid' => 'prog_showcase_1',
            'block_type' => 'program_showcase',
            'source_mode' => 'active_period_schedule',
            'visible' => true,
        ];

        $service = app(HomepageDataService::class);
        $resolved = $service->resolveProgramBlockData($block);

        $this->assertCount(1, $resolved);
        $this->assertEquals($prog->id, $resolved->first()->id);
    }

    public function test_empty_period_or_no_scheduled_programs_returns_empty_collection()
    {
        // No published templates exist
        $service = app(HomepageDataService::class);
        $resolved = $service->resolveActivePeriodWeeklyPrograms();

        $this->assertEmpty($resolved);
    }
}
