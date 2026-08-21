<?php

namespace Tests\Feature\Filament;

use App\Filament\Widgets\ContentSummaryWidget;
use App\Filament\Widgets\LiveBroadcastStatsWidget;
use App\Filament\Widgets\RecentAuditLogsWidget;
use App\Filament\Widgets\TodayScheduleWidget;
use App\Models\Announcement;
use App\Models\AuditLog;
use App\Models\Episode;
use App\Models\Khatm;
use App\Models\Program;
use App\Models\Role;
use App\Models\ScheduleTemplate;
use App\Models\ScheduleTemplateItem;
use App\Models\SiteSetting;
use App\Models\User;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardWidgetsTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $admin;
    private User $editor;

    protected function setUp(): void
    {
        parent::setUp();

        $superAdminRole = Role::firstOrCreate(
            ['slug' => 'super_admin'],
            ['name' => 'Süper Yönetici', 'base_role' => 'super_admin', 'description' => 'Tam yetkili', 'is_system' => true]
        );

        $adminRole = Role::firstOrCreate(
            ['slug' => 'administrator'],
            ['name' => 'Yönetici', 'base_role' => 'administrator', 'description' => 'Yönetici', 'is_system' => true]
        );

        $editorRole = Role::firstOrCreate(
            ['slug' => 'editor'],
            ['name' => 'Editör', 'base_role' => 'editor', 'description' => 'İçerik editörü', 'is_system' => true]
        );

        $this->superAdmin = User::factory()->create([
            'role_id' => $superAdminRole->id,
            'is_active' => true,
        ]);

        $this->admin = User::factory()->create([
            'role_id' => $adminRole->id,
            'is_active' => true,
        ]);

        $this->editor = User::factory()->create([
            'role_id' => $editorRole->id,
            'is_active' => true,
        ]);

        SiteSetting::current();
    }

    public function test_dashboard_opens_successfully_and_default_widgets_are_removed(): void
    {
        $response = $this->actingAs($this->superAdmin)->get('/admin');
        $response->assertSuccessful();

        // Verify default Filament demo & info widgets are not present in rendered dashboard
        $response->assertDontSee('FilamentInfoWidget');
        $response->assertDontSee('https://filamentphp.com');
        $response->assertDontSee('Documentation');
    }

    public function test_live_broadcast_stats_widget_renders_real_tv_and_radio_data(): void
    {
        SiteSetting::current()->update([
            'live_tv_is_active' => true,
            'live_tv_is_public' => true,
            'live_tv_type' => 'hls',
            'radio_is_active' => false,
            'radio_maintenance_message' => 'Radyo bakımda.',
            'radio_is_public' => false,
        ]);

        Livewire::actingAs($this->superAdmin)
            ->test(LiveBroadcastStatsWidget::class)
            ->assertSee('Dost TV Canlı Yayın')
            ->assertSee('Aktif')
            ->assertSee('HLS Stream')
            ->assertSee('Herkese Açık')
            ->assertSee('Dost FM Canlı Radyo')
            ->assertSee('Bakımda')
            ->assertSee('Gizli');
    }

    public function test_today_schedule_widget_with_broadcasts_calculates_statuses(): void
    {
        // Fix test time at 14:30 on a Wednesday (2026-08-19)
        Carbon::setTestNow(Carbon::parse('2026-08-19 14:30:00'));

        $template = ScheduleTemplate::create([
            'name' => 'Test Şablonu',
            'status' => 'published',
            'is_active' => true,
            'priority' => 10,
        ]);

        $programPast = Program::factory()->create(['name' => 'Sabah Programı', 'is_active' => true]);
        $programCurrent = Program::factory()->create(['name' => 'Öğle Canlı Yayını', 'is_active' => true]);
        $programNext = Program::factory()->create(['name' => 'Akşam Kuşağı', 'is_active' => true]);
        $programFuture = Program::factory()->create(['name' => 'Gece Niyazı', 'is_active' => true]);

        // Wednesday is dayOfWeekIso 3 -> day_of_week in ScheduleTemplateItem is 2 (0-indexed: 0=Mon, 2=Wed)
        $dayIndex = Carbon::now()->dayOfWeekIso - 1;

        // Past (08:00 - 10:00)
        ScheduleTemplateItem::create([
            'schedule_template_id' => $template->id,
            'program_id' => $programPast->id,
            'day_of_week' => $dayIndex,
            'start_time' => '08:00',
            'end_time' => '10:00',
            'is_active' => true,
        ]);

        // Current (14:00 - 16:00) - Should be 'Şu Anda'
        ScheduleTemplateItem::create([
            'schedule_template_id' => $template->id,
            'program_id' => $programCurrent->id,
            'day_of_week' => $dayIndex,
            'start_time' => '14:00',
            'end_time' => '16:00',
            'is_live' => true,
            'is_active' => true,
        ]);

        // Next (18:00 - 19:30) - Should be 'Sıradaki'
        ScheduleTemplateItem::create([
            'schedule_template_id' => $template->id,
            'program_id' => $programNext->id,
            'day_of_week' => $dayIndex,
            'start_time' => '18:00',
            'end_time' => '19:30',
            'is_repeat' => true,
            'is_active' => true,
        ]);

        // Future (21:00 - 22:30) - Should be 'Bekleyen'
        ScheduleTemplateItem::create([
            'schedule_template_id' => $template->id,
            'program_id' => $programFuture->id,
            'day_of_week' => $dayIndex,
            'start_time' => '21:00',
            'end_time' => '22:30',
            'is_active' => true,
        ]);

        // Test TodayScheduleWidget
        Livewire::actingAs($this->superAdmin)
            ->test(TodayScheduleWidget::class)
            ->assertSee('Bugünün Yayın Akışı')
            ->assertSee('Sabah Programı')
            ->assertSee('Tamamlandı')
            ->assertSee('Öğle Canlı Yayını')
            ->assertSee('Şu Anda')
            ->assertSee('Akşam Kuşağı')
            ->assertSee('Sıradaki')
            ->assertSee('Gece Niyazı')
            ->assertSee('Bekleyen');

        // Test Top Status bar reflection of the schedule
        Livewire::actingAs($this->superAdmin)
            ->test(LiveBroadcastStatsWidget::class)
            ->assertSee('4 Program')
            ->assertSee('Öğle Canlı Yayını')
            ->assertSee('18:00 · Akşam Kuşağı');

        Carbon::setTestNow();
    }

    public function test_today_schedule_widget_empty_state_when_no_broadcasts(): void
    {
        Livewire::actingAs($this->superAdmin)
            ->test(TodayScheduleWidget::class)
            ->assertSee('Bugün için yayın akışı bulunmuyor.')
            ->assertSee('Yayın Akışı Planla');
    }

    public function test_content_summary_widget_counts(): void
    {
        // 2 Active programs, 1 completed
        Program::factory()->create(['name' => 'Aktif Program 1', 'is_active' => true, 'status' => 'active']);
        Program::factory()->create(['name' => 'Aktif Program 2', 'is_active' => true, 'status' => 'active']);
        Program::factory()->create(['name' => 'Sona Ermis Program', 'is_active' => true, 'status' => 'completed']);

        // 3 Episodes (2 published, 1 draft)
        $prog = Program::first();
        Episode::factory()->create(['program_id' => $prog->id, 'title' => 'Bölüm 1', 'status' => 'published', 'is_active' => true]);
        Episode::factory()->create(['program_id' => $prog->id, 'title' => 'Bölüm 2', 'status' => 'published', 'is_active' => true]);
        Episode::factory()->create(['program_id' => $prog->id, 'title' => 'Bölüm 3', 'status' => 'draft', 'is_active' => true]);

        // 2 Announcements (1 active, 1 expired)
        Announcement::create([
            'title' => 'Geçerli Duyuru',
            'message' => 'Test',
            'is_active' => true,
            'ends_at' => now()->addDays(5),
        ]);
        Announcement::create([
            'title' => 'Süresi Dolmuş Duyuru',
            'message' => 'Test',
            'is_active' => true,
            'ends_at' => now()->subDay(),
        ]);

        // 1 Active Khatm, 1 Completed Khatm
        Khatm::create([
            'title' => 'Ramazan Hatmi',
            'slug' => 'ramazan-hatmi-test',
            'status' => 'active',
            'total_juz' => 30,
        ]);
        Khatm::create([
            'title' => 'Eski Hatim',
            'slug' => 'eski-hatim-test',
            'status' => 'completed',
            'total_juz' => 30,
        ]);

        Livewire::actingAs($this->superAdmin)
            ->test(ContentSummaryWidget::class)
            ->assertSee('Programlar')
            ->assertSee('3')
            ->assertSee('2 Aktif')
            ->assertSee('Bölümler (Video)')
            ->assertSee('3')
            ->assertSee('2 Yayında')
            ->assertSee('Aktif Duyurular')
            ->assertSee('1') // only the non-expired active one
            ->assertSee('Aktif Hatimler')
            ->assertSee('1'); // only the active khatm
    }

    public function test_recent_audit_logs_widget_filters_system_cron_and_shows_real_user_actions(): void
    {
        $prog = Program::factory()->create(['name' => 'Gönül Sohbetleri Programı']);

        // Real user action
        AuditLog::create([
            'user_id' => $this->superAdmin->id,
            'user_name_snapshot' => $this->superAdmin->name,
            'action' => 'updated',
            'subject_type' => Program::class,
            'subject_id' => $prog->id,
            'subject_label' => 'Gönül Sohbetleri Programı',
            'message' => 'Program güncellendi',
            'is_destructive' => false,
        ]);

        // Cron / System action (user_id = null)
        AuditLog::create([
            'user_id' => null,
            'user_name_snapshot' => 'Sistem / YouTube Otomatik Senkronizasyon',
            'action' => 'synced',
            'subject_label' => 'YouTube Playlist Sync Cron',
            'message' => 'Cron tamamlandı',
        ]);

        Livewire::actingAs($this->superAdmin)
            ->test(RecentAuditLogsWidget::class)
            ->assertSee($this->superAdmin->name)
            ->assertSee('Düzenlendi')
            ->assertSee('Gönül Sohbetleri Programı')
            ->assertDontSee('Sistem / YouTube Otomatik Senkronizasyon')
            ->assertDontSee('YouTube Playlist Sync Cron');
    }

    public function test_role_based_visibility_for_recent_audit_logs_widget(): void
    {
        $this->actingAs($this->superAdmin);
        $this->assertTrue(RecentAuditLogsWidget::canView(), 'Super admin should see audit logs widget');

        $this->actingAs($this->admin);
        $this->assertTrue(RecentAuditLogsWidget::canView(), 'Administrator should see audit logs widget');

        $this->actingAs($this->editor);
        $this->assertFalse(RecentAuditLogsWidget::canView(), 'Editor should NOT see audit logs widget');
    }
}
