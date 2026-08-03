<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\ScheduleCalendarPage;
use App\Models\Program;
use App\Models\Schedule;
use App\Models\ScheduleTemplate;
use App\Models\ScheduleTemplateItem;
use App\Models\User;
use App\Services\Schedule\ScheduleCalendarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ScheduleCalendarTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_access_schedule_calendar_page(): void
    {
        $user = User::factory()->create(['role' => 'administrator']);

        $this->actingAs($user)
            ->get('/admin/schedule-calendar')
            ->assertOk();
    }

    public function test_day_tabs_switch_and_counts(): void
    {
        $user = User::factory()->create(['role' => 'administrator']);
        $template = ScheduleTemplate::create(['name' => 'Kış Akışı 2026', 'slug' => 'kis-akisi-2026', 'status' => 'published', 'priority' => 10]);

        $prog1 = Program::create(['name' => 'Program 1', 'slug' => 'p1', 'is_active' => true]);

        ScheduleTemplateItem::create([
            'schedule_template_id' => $template->id,
            'program_id' => $prog1->id,
            'day_of_week' => 0,
            'start_time' => '08:00',
            'end_time' => '09:00',
        ]);

        Livewire::actingAs($user)
            ->test(ScheduleCalendarPage::class)
            ->call('selectDayTab', '0')
            ->assertSet('activeDayTab', '0');
    }

    public function test_new_broadcast_item_creation_header_action(): void
    {
        $user = User::factory()->create(['role' => 'administrator']);
        $template = ScheduleTemplate::create(['name' => 'Bahar Akışı', 'slug' => 'bahar-akisi', 'status' => 'published']);
        $prog = Program::create(['name' => 'Tefsir Saati', 'slug' => 'tefsir-saati', 'is_active' => true]);

        Livewire::actingAs($user)
            ->test(ScheduleCalendarPage::class)
            ->callAction('create_item', [
                'program_id' => $prog->id,
                'day_of_week' => 1,
                'start_time' => '10:00',
                'end_time' => '11:00',
                'item_type' => 'live',
                'is_live' => true,
                'is_repeat' => false,
                'is_active' => true,
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('schedule_template_items', [
            'schedule_template_id' => $template->id,
            'program_id' => $prog->id,
            'day_of_week' => 1,
            'start_time' => '10:00',
            'is_live' => true,
        ]);
    }

    public function test_broadcast_item_can_be_copied_moved_and_deleted(): void
    {
        $user = User::factory()->create(['role' => 'administrator']);
        $template = ScheduleTemplate::create(['name' => 'Test Sezon', 'slug' => 'test-sezon', 'status' => 'draft']);
        $prog = Program::create(['name' => 'Ana Haber', 'slug' => 'ana-haber', 'is_active' => true]);

        $item = ScheduleTemplateItem::create([
            'schedule_template_id' => $template->id,
            'program_id' => $prog->id,
            'day_of_week' => 0,
            'start_time' => '19:00',
            'end_time' => '20:00',
        ]);

        $service = new ScheduleCalendarService();
        $service->copyDay($template, 0, 1);

        $this->assertDatabaseHas('schedule_template_items', [
            'schedule_template_id' => $template->id,
            'day_of_week' => 1,
            'start_time' => '19:00',
        ]);

        $item->delete();

        $this->assertDatabaseMissing('schedule_template_items', [
            'id' => $item->id,
        ]);
    }

    public function test_shift_times_action(): void
    {
        $template = ScheduleTemplate::create(['name' => 'Test Shift', 'slug' => 'test-shift', 'status' => 'draft']);
        $prog = Program::create(['name' => 'P1', 'slug' => 'p1', 'is_active' => true]);

        $item = ScheduleTemplateItem::create([
            'schedule_template_id' => $template->id,
            'program_id' => $prog->id,
            'day_of_week' => 0,
            'start_time' => '10:00',
            'end_time' => '11:00',
        ]);

        $service = new ScheduleCalendarService();
        $service->shiftTimes($template, 30, 0);

        $this->assertEquals('10:30', $item->fresh()->start_time);
        $this->assertEquals('11:30', $item->fresh()->end_time);
    }
}
