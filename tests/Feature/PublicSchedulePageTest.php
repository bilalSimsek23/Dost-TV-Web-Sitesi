<?php

namespace Tests\Feature;

use App\Models\Program;
use App\Models\ScheduleTemplate;
use App\Models\ScheduleTemplateItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PublicSchedulePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_schedule_page_returns_ok_status_and_renders_weekly_header(): void
    {
        $response = $this->get('/yayin-akisi');

        $response->assertOk();
        $response->assertSee('Haftalık Yayın Akışı');
        $response->assertSee('Dost TV güncel televizyon yayın akış planı');
    }

    public function test_public_schedule_renders_real_turkish_day_names_and_dates(): void
    {
        // Fix time to Tuesday 18 August 2026 14:30
        Carbon::setTestNow(Carbon::parse('2026-08-18 14:30:00'));

        $template = ScheduleTemplate::create([
            'name' => '2026 Yaz Dönemi',
            'slug' => '2026-yaz-donemi',
            'status' => 'published',
            'is_active' => true,
            'valid_from' => '2026-06-01',
            'valid_until' => '2026-09-30',
        ]);

        $program = Program::create([
            'name' => 'Gönül Sohbetleri',
            'slug' => 'gonul-sohbetleri',
            'status' => 'active',
            'is_active' => true,
        ]);

        // Tuesday (day 1) broadcast 14:00 - 15:00
        ScheduleTemplateItem::create([
            'schedule_template_id' => $template->id,
            'program_id' => $program->id,
            'day_of_week' => 1,
            'start_time' => '14:00',
            'end_time' => '15:00',
            'is_active' => true,
        ]);

        $response = $this->get('/yayin-akisi');

        $response->assertOk();
        // Check days are present
        $response->assertSee('Pazartesi');
        $response->assertSee('17 Ağustos');
        $response->assertSee('Salı');
        $response->assertSee('18 Ağustos');
        $response->assertSee('Çarşamba');
        $response->assertSee('19 Ağustos');
        $response->assertSee('Perşembe');
        $response->assertSee('20 Ağustos');
        $response->assertSee('Cuma');
        $response->assertSee('21 Ağustos');
        $response->assertSee('Cumartesi');
        $response->assertSee('22 Ağustos');
        $response->assertSee('Pazar');
        $response->assertSee('23 Ağustos');

        // Check active template badge
        $response->assertSee('2026 Yaz Dönemi');

        // Check Tuesday has the program and marked as now playing at 14:30
        $response->assertSee('Gönül Sohbetleri');
        $response->assertSee('ŞİMDİ');
        $response->assertSee('id="now-playing"', false);
        $response->assertSee('data-now-playing="true"', false);
        $response->assertSee('scroll-mt-28');
        $response->assertSee('prefers-reduced-motion');
        $response->assertSee('!window.location.hash');
    }

    public function test_now_playing_badge_and_id_for_overnight_broadcast(): void
    {
        // Fix time to 23:45 on Monday 17 August 2026
        Carbon::setTestNow(Carbon::parse('2026-08-17 23:45:00'));

        $template = ScheduleTemplate::create([
            'name' => 'Gece Akışı Dönemi',
            'slug' => 'gece-akisi-donemi',
            'status' => 'published',
            'is_active' => true,
        ]);

        $program = Program::create([
            'name' => 'Gece Niyazı',
            'slug' => 'gece-niyazi',
            'status' => 'active',
            'is_active' => true,
        ]);

        // Monday (day 0) overnight broadcast 23:00 - 01:00
        ScheduleTemplateItem::create([
            'schedule_template_id' => $template->id,
            'program_id' => $program->id,
            'day_of_week' => 0,
            'start_time' => '23:00',
            'end_time' => '01:00',
            'is_active' => true,
        ]);

        $response = $this->get('/yayin-akisi');

        $response->assertOk();
        $response->assertSee('Gece Niyazı');
        $response->assertSee('ŞİMDİ');
        $response->assertSee('id="now-playing"', false);
    }

    public function test_next_upcoming_broadcast_is_flagged_when_no_active_broadcast(): void
    {
        // Fix time to morning 05:30 on Monday 17 August 2026
        Carbon::setTestNow(Carbon::parse('2026-08-17 05:30:00'));

        $template = ScheduleTemplate::create([
            'name' => 'Sabah Dönemi',
            'slug' => 'sabah-donemi',
            'status' => 'published',
            'is_active' => true,
        ]);

        $program = Program::create([
            'name' => 'Sabah Mukabelesi',
            'slug' => 'sabah-mukabelesi',
            'status' => 'active',
            'is_active' => true,
        ]);

        // Monday (day 0) broadcast 07:00 - 08:00
        ScheduleTemplateItem::create([
            'schedule_template_id' => $template->id,
            'program_id' => $program->id,
            'day_of_week' => 0,
            'start_time' => '07:00',
            'end_time' => '08:00',
            'is_active' => true,
        ]);

        $response = $this->get('/yayin-akisi');

        $response->assertOk();
        $response->assertSee('Sabah Mukabelesi');
        $response->assertSee('data-next-upcoming="true"', false);
    }

    public function test_graceful_empty_schedule_when_no_items(): void
    {
        $response = $this->get('/yayin-akisi');

        $response->assertOk();
        $response->assertSee('Bu gün için planlanmış yayın bulunmamaktadır');
    }
}
