<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Banner;
use App\Models\Program;
use App\Models\ScheduleTemplate;
use App\Models\ScheduleTemplateItem;
use App\Models\User;
use App\Models\UserInvitation;
use App\Services\Auth\UserInvitationService;
use App\Services\Home\HomepageDataService;
use App\Services\Schedule\BroadcastScheduleResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TimezoneAndScheduleCurrentPlayingTest extends TestCase
{
    use RefreshDatabase;

    public function test_application_configured_timezone_is_europe_istanbul(): void
    {
        $this->assertEquals('Europe/Istanbul', config('app.timezone'));
        $this->assertEquals('Europe/Istanbul', now()->timezoneName);
        $this->assertEquals('Europe/Istanbul', Carbon::now()->timezoneName);
    }

    public function test_schedule_current_playing_at_17_26_marks_bir_garip_yolcu(): void
    {
        // Set fixed time to 2026-08-17 17:26 (Monday) in Europe/Istanbul
        Carbon::setTestNow(Carbon::parse('2026-08-17 17:26:00', 'Europe/Istanbul'));

        $template = ScheduleTemplate::create([
            'name' => '2026 Yaz Dönemi',
            'status' => 'published',
            'is_active' => true,
        ]);

        $prog1 = Program::create(['name' => 'Sağlık Sohbetleri', 'slug' => 'saglik-sohbetleri', 'status' => 'active']);
        $prog2 = Program::create(['name' => 'Bir Garip Yolcu', 'slug' => 'bir-garip-yolcu', 'status' => 'active']);
        $prog3 = Program::create(['name' => 'Katre', 'slug' => 'katre', 'status' => 'active']);

        // Monday is day_of_week 0
        ScheduleTemplateItem::create([
            'schedule_template_id' => $template->id,
            'day_of_week' => 0,
            'program_id' => $prog1->id,
            'start_time' => '14:10',
            'end_time' => '15:30',
            'is_active' => true,
        ]);

        ScheduleTemplateItem::create([
            'schedule_template_id' => $template->id,
            'day_of_week' => 0,
            'program_id' => $prog2->id,
            'start_time' => '16:30',
            'end_time' => '17:30',
            'is_active' => true,
        ]);

        ScheduleTemplateItem::create([
            'schedule_template_id' => $template->id,
            'day_of_week' => 0,
            'program_id' => $prog3->id,
            'start_time' => '17:30',
            'end_time' => '18:30',
            'is_active' => true,
        ]);

        $response = $this->get(route('schedule.index'));
        $response->assertSuccessful();

        $daysData = $response->viewData('daysData');
        $mondayData = $daysData[0];

        $this->assertTrue($mondayData['is_today']);
        $this->assertEquals(1, $mondayData['now_playing_index']); // Index 1 is "Bir Garip Yolcu"
        $this->assertEquals('Bir Garip Yolcu', $mondayData['broadcasts'][1]->program->name);
    }

    public function test_schedule_current_playing_at_17_31_transitions_to_katre(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-17 17:31:00', 'Europe/Istanbul'));

        $template = ScheduleTemplate::create([
            'name' => '2026 Yaz Dönemi',
            'status' => 'published',
            'is_active' => true,
        ]);

        $prog1 = Program::create(['name' => 'Bir Garip Yolcu', 'slug' => 'bir-garip-yolcu', 'status' => 'active']);
        $prog2 = Program::create(['name' => 'Katre', 'slug' => 'katre', 'status' => 'active']);

        ScheduleTemplateItem::create([
            'schedule_template_id' => $template->id,
            'day_of_week' => 0,
            'program_id' => $prog1->id,
            'start_time' => '16:30',
            'end_time' => '17:30',
            'is_active' => true,
        ]);

        ScheduleTemplateItem::create([
            'schedule_template_id' => $template->id,
            'day_of_week' => 0,
            'program_id' => $prog2->id,
            'start_time' => '17:30',
            'end_time' => '18:30',
            'is_active' => true,
        ]);

        $response = $this->get(route('schedule.index'));
        $response->assertSuccessful();

        $daysData = $response->viewData('daysData');
        $mondayData = $daysData[0];

        $this->assertEquals(1, $mondayData['now_playing_index']); // Index 1 is "Katre"
        $this->assertEquals('Katre', $mondayData['broadcasts'][1]->program->name);
    }

    public function test_schedule_current_playing_at_14_28_marks_saglik_sohbetleri(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-17 14:28:00', 'Europe/Istanbul'));

        $template = ScheduleTemplate::create([
            'name' => '2026 Yaz Dönemi',
            'status' => 'published',
            'is_active' => true,
        ]);

        $prog1 = Program::create(['name' => 'Sağlık Sohbetleri', 'slug' => 'saglik-sohbetleri', 'status' => 'active']);
        $prog2 = Program::create(['name' => 'Bir Garip Yolcu', 'slug' => 'bir-garip-yolcu', 'status' => 'active']);

        ScheduleTemplateItem::create([
            'schedule_template_id' => $template->id,
            'day_of_week' => 0,
            'program_id' => $prog1->id,
            'start_time' => '14:10',
            'end_time' => '15:30',
            'is_active' => true,
        ]);

        ScheduleTemplateItem::create([
            'schedule_template_id' => $template->id,
            'day_of_week' => 0,
            'program_id' => $prog2->id,
            'start_time' => '16:30',
            'end_time' => '17:30',
            'is_active' => true,
        ]);

        $response = $this->get(route('schedule.index'));
        $daysData = $response->viewData('daysData');
        $mondayData = $daysData[0];

        $this->assertEquals(0, $mondayData['now_playing_index']); // Index 0 is "Sağlık Sohbetleri"
        $this->assertEquals('Sağlık Sohbetleri', $mondayData['broadcasts'][0]->program->name);
    }

    public function test_midnight_crossing_broadcast_evaluated_accurately(): void
    {
        $template = ScheduleTemplate::create([
            'name' => 'Gece Akışı',
            'status' => 'published',
            'is_active' => true,
        ]);

        $prog = Program::create(['name' => 'Gece Yayını', 'slug' => 'gece-yayini', 'status' => 'active']);

        ScheduleTemplateItem::create([
            'schedule_template_id' => $template->id,
            'day_of_week' => 0,
            'program_id' => $prog->id,
            'start_time' => '23:30',
            'end_time' => '00:30',
            'is_active' => true,
        ]);

        // 1. At 23:45 Monday
        Carbon::setTestNow(Carbon::parse('2026-08-17 23:45:00', 'Europe/Istanbul'));
        $res1 = $this->get(route('schedule.index'));
        $this->assertEquals(0, $res1->viewData('daysData')[0]['now_playing_index']);

        // 2. At 00:15 (Next day early morning: Tuesday)
        // If an item on Tuesday is 23:30 - 00:30 and tested at 00:15 Tuesday:
        Carbon::setTestNow(Carbon::parse('2026-08-18 00:15:00', 'Europe/Istanbul'));
        ScheduleTemplateItem::create([
            'schedule_template_id' => $template->id,
            'day_of_week' => 1, // Tuesday
            'program_id' => $prog->id,
            'start_time' => '23:30',
            'end_time' => '00:30',
            'is_active' => true,
        ]);
        $res2 = $this->get(route('schedule.index'));
        $this->assertEquals(0, $res2->viewData('daysData')[1]['now_playing_index']);

        // 3. At 00:31 Tuesday
        Carbon::setTestNow(Carbon::parse('2026-08-18 00:31:00', 'Europe/Istanbul'));
        $res3 = $this->get(route('schedule.index'));
        $this->assertNull($res3->viewData('daysData')[1]['now_playing_index']);
    }

    public function test_date_boundary_crossing_matches_istanbul_time(): void
    {
        // When UTC is 2026-08-17 21:30 (Monday in UTC), Istanbul is 2026-08-18 00:30 (Tuesday in Istanbul)
        Carbon::setTestNow(Carbon::parse('2026-08-18 00:30:00', 'Europe/Istanbul'));

        $this->assertEquals('Tuesday', now()->format('l'));
        $this->assertEquals(1, (int) now()->dayOfWeekIso - 1); // Tuesday is index 1

        $response = $this->get(route('schedule.index'));
        $response->assertSuccessful();
        $this->assertEquals(1, $response->viewData('todayIndex'));
    }

    public function test_user_invitation_72_hours_expiry_with_timezone(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-17 12:00:00', 'Europe/Istanbul'));

        $user = User::factory()->create(['name' => 'Davetli', 'email' => 'davetli@dosttv.com']);
        $service = app(UserInvitationService::class);
        $res = $service->createInvitation($user);

        $invitation = $res['invitation'];
        $this->assertEquals('2026-08-20 12:00:00', $invitation->expires_at->format('Y-m-d H:i:s'));
        $this->assertEquals(72, (int) round(now()->diffInHours($invitation->expires_at)));
        $this->assertTrue($invitation->isValid());

        // Travel 71 hours -> still valid
        Carbon::setTestNow(Carbon::parse('2026-08-20 11:00:00', 'Europe/Istanbul'));
        $this->assertTrue($invitation->fresh()->isValid());

        // Travel 73 hours -> expired
        Carbon::setTestNow(Carbon::parse('2026-08-20 13:00:00', 'Europe/Istanbul'));
        $this->assertFalse($invitation->fresh()->isValid());
    }

    public function test_homepage_data_and_banners_resolve_with_timezone(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-17 12:00:00', 'Europe/Istanbul'));

        Banner::create([
            'title' => 'Aktif Banner',
            'image' => 'banners/aktif.jpg',
            'is_active' => true,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
        ]);

        Banner::create([
            'title' => 'Gelecek Banner',
            'image' => 'banners/gelecek.jpg',
            'is_active' => true,
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDays(2),
        ]);

        $service = app(HomepageDataService::class);
        $data = $service->getHomepageData(['hero' => ['key' => 'hero', 'visible' => true]]);

        $this->assertCount(1, $data['banners']);
        $this->assertEquals('Aktif Banner', $data['banners']->first()->title);
    }
}
