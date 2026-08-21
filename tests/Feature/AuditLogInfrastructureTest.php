<?php

namespace Tests\Feature;

use App\Filament\Resources\AuditLogs\AuditLogResource;
use App\Models\AuditLog;
use App\Models\Program;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogInfrastructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_log_record_can_be_created_via_audit_logger(): void
    {
        $user = User::factory()->create([
            'name' => 'Yasemin Çelik',
            'role' => 'editor',
        ]);

        $log = AuditLogger::log(
            action: 'created',
            message: 'Yasemin Çelik, Akla Kapı 14. Bölüm\'ü ekledi.',
            subjectLabel: 'Akla Kapı 14. Bölüm',
            user: $user,
            isDestructive: false,
            metadata: ['ip' => '127.0.0.1']
        );

        $this->assertDatabaseHas('audit_logs', [
            'id' => $log->id,
            'user_id' => $user->id,
            'user_name_snapshot' => 'Yasemin Çelik',
            'action' => 'created',
            'subject_label' => 'Akla Kapı 14. Bölüm',
            'message' => 'Yasemin Çelik, Akla Kapı 14. Bölüm\'ü ekledi.',
            'is_destructive' => false,
        ]);
        $this->assertEquals('Oluşturuldu', $log->action_label);
        $this->assertEquals('success', $log->action_color);
    }

    public function test_user_name_snapshot_is_preserved_when_user_name_changes(): void
    {
        $user = User::factory()->create([
            'name' => 'Ahmet Yılmaz',
            'role' => 'editor',
        ]);

        $log = AuditLogger::log(
            action: 'updated',
            message: 'Ahmet Yılmaz, Akla Kapı programını düzenledi.',
            subjectLabel: 'Akla Kapı',
            user: $user
        );

        // User changes name
        $user->update(['name' => 'Ahmet Demir']);

        $log->refresh();
        $this->assertEquals('Ahmet Yılmaz', $log->user_name_snapshot);
    }

    public function test_audit_log_remains_when_user_is_permanently_deleted(): void
    {
        $user = User::factory()->create([
            'name' => 'Silinecek Editör',
            'role' => 'editor',
        ]);

        $log = AuditLogger::log(
            action: 'synced',
            message: 'Silinecek Editör, YouTube oynatma listesini eşitledi.',
            subjectLabel: 'Akla Kapı',
            user: $user
        );

        // Delete user
        $user->forceDelete();

        $log->refresh();
        $this->assertNull($log->user_id);
        $this->assertEquals('Silinecek Editör', $log->user_name_snapshot);
        $this->assertDatabaseHas('audit_logs', ['id' => $log->id]);
    }

    public function test_destructive_actions_are_flagged(): void
    {
        $user = User::factory()->create(['name' => 'Mehmet', 'role' => 'super_admin']);

        $log1 = AuditLogger::log(
            action: 'deleted',
            message: 'Mehmet, Akla Kapı 12. Bölüm\'ü sildi.',
            subjectLabel: 'Akla Kapı 12. Bölüm',
            user: $user
        );

        $this->assertTrue($log1->is_destructive);
        $this->assertEquals('danger', $log1->action_color);

        $log2 = AuditLogger::log(
            action: 'updated',
            message: 'Mehmet, kritik bir ayarı kaldırdı.',
            user: $user,
            isDestructive: true
        );

        $this->assertTrue($log2->is_destructive);
        $this->assertEquals('danger', $log2->action_color);
    }

    public function test_super_admin_can_access_audit_logs(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($superAdmin);

        $this->assertTrue(AuditLogResource::canAccess());
    }

    public function test_administrator_can_access_audit_logs(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);
        $this->actingAs($admin);

        $this->assertTrue(AuditLogResource::canAccess());
    }

    public function test_editor_cannot_access_audit_logs(): void
    {
        $editor = User::factory()->create(['role' => 'editor']);
        $this->actingAs($editor);

        $this->assertFalse(AuditLogResource::canAccess());
    }

    public function test_audit_prune_command_removes_records_older_than_six_months(): void
    {
        // 7 months old record (must be deleted)
        $oldLog = AuditLog::create([
            'user_name_snapshot' => 'Eski Kullanıcı',
            'action' => 'created',
            'message' => '7 ay önceki işlem.',
        ]);
        \Illuminate\Support\Facades\DB::table('audit_logs')
            ->where('id', $oldLog->id)
            ->update(['created_at' => now()->subMonths(7)]);

        // 2 months old record (must be preserved)
        $recentLog = AuditLog::create([
            'user_name_snapshot' => 'Yeni Kullanıcı',
            'action' => 'created',
            'message' => '2 ay önceki işlem.',
        ]);
        \Illuminate\Support\Facades\DB::table('audit_logs')
            ->where('id', $recentLog->id)
            ->update(['created_at' => now()->subMonths(2)]);

        // Run dry run first
        $this->artisan('audit:prune', ['--dry-run' => true])
            ->expectsOutputToContain('Dry-run: 6 aydan eski 1 adet işlem geçmişi kaydı silinecek.')
            ->assertSuccessful();

        $this->assertDatabaseHas('audit_logs', ['id' => $oldLog->id]);
        $this->assertDatabaseHas('audit_logs', ['id' => $recentLog->id]);

        // Run actual prune
        $this->artisan('audit:prune')
            ->expectsOutputToContain('6 aydan eski 1 adet işlem geçmişi kaydı başarıyla temizlendi.')
            ->assertSuccessful();

        $this->assertDatabaseMissing('audit_logs', ['id' => $oldLog->id]);
        $this->assertDatabaseHas('audit_logs', ['id' => $recentLog->id]);
    }

    public function test_audit_prune_is_scheduled_daily(): void
    {
        $schedule = app(Schedule::class);
        $events = collect($schedule->events());

        $auditPruneEvent = $events->first(function ($event) {
            return str_contains($event->command, 'audit:prune');
        });

        $this->assertNotNull($auditPruneEvent);
        $this->assertEquals('0 0 * * *', $auditPruneEvent->expression); // Daily cron
    }

    public function test_user_filter_scopes_audit_logs(): void
    {
        $user1 = User::factory()->create(['name' => 'Kullanıcı 1']);
        $user2 = User::factory()->create(['name' => 'Kullanıcı 2']);

        $log1 = AuditLogger::log(action: 'created', message: 'Log 1', user: $user1);
        $log2 = AuditLogger::log(action: 'created', message: 'Log 2', user: $user2);

        $user1Logs = AuditLog::where('user_id', $user1->id)->get();
        $this->assertCount(1, $user1Logs);
        $this->assertEquals($log1->id, $user1Logs->first()->id);
    }

    public function test_action_filter_scopes_audit_logs(): void
    {
        $user = User::factory()->create();

        $logCreate = AuditLogger::log(action: 'created', message: 'Create log', user: $user);
        $logDelete = AuditLogger::log(action: 'deleted', message: 'Delete log', user: $user);

        $deleteLogs = AuditLog::where('action', 'deleted')->get();
        $this->assertCount(1, $deleteLogs);
        $this->assertEquals($logDelete->id, $deleteLogs->first()->id);
    }

    public function test_date_range_filter_scopes_audit_logs(): void
    {
        $logOld = AuditLog::create([
            'user_name_snapshot' => 'Geçmiş',
            'action' => 'created',
            'message' => 'Geçmiş log',
        ]);
        \Illuminate\Support\Facades\DB::table('audit_logs')
            ->where('id', $logOld->id)
            ->update(['created_at' => now()->subDays(10)]);

        $logNew = AuditLog::create([
            'user_name_snapshot' => 'Bugün',
            'action' => 'created',
            'message' => 'Bugünkü log',
        ]);

        $filteredLogs = AuditLog::whereDate('created_at', '>=', now()->subDays(2)->toDateString())->get();
        $this->assertCount(1, $filteredLogs);
        $this->assertEquals($logNew->id, $filteredLogs->first()->id);
    }
}
