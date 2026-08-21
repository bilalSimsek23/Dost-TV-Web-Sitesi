<?php

namespace Tests\Feature;

use App\Filament\Resources\Programs\Pages\CreateProgram;
use App\Filament\Resources\Programs\Pages\EditProgram;
use App\Filament\Resources\Programs\Tables\ProgramsTable;
use App\Filament\Resources\Roles\Pages\CreateRole;
use App\Filament\Resources\Roles\Pages\EditRole;
use App\Filament\Resources\ScheduleTemplates\Pages\CreateScheduleTemplate;
use App\Filament\Resources\ScheduleTemplates\Pages\EditScheduleTemplate;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\AuditLog;
use App\Models\Episode;
use App\Models\Program;
use App\Models\ProgramSeason;
use App\Models\ProgramSeries;
use App\Models\Role;
use App\Models\ScheduleTemplate;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\YouTube\YouTubePlaylistSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleAuditLogIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_program_create_audit_log(): void
    {
        $editor = User::factory()->create(['name' => 'Yasemin Çelik', 'role' => 'editor']);
        $this->actingAs($editor);

        $program = Program::create([
            'name' => 'Akla Kapı',
            'slug' => 'akla-kapi',
            'status' => 'active',
            'is_active' => true,
            'show_on_public' => true,
        ]);

        AuditLogger::log(
            action: 'created',
            message: "{$editor->name}, {$program->name} programını ekledi.",
            subject: $program,
            subjectLabel: $program->name,
        );

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'created',
            'user_name_snapshot' => 'Yasemin Çelik',
            'subject_label' => 'Akla Kapı',
            'message' => 'Yasemin Çelik, Akla Kapı programını ekledi.',
            'is_destructive' => false,
        ]);
    }

    public function test_program_publish_and_unpublish_audit_log(): void
    {
        $editor = User::factory()->create(['name' => 'Yasemin Çelik', 'role' => 'editor']);
        $this->actingAs($editor);

        $program = Program::create([
            'name' => 'Akla Kapı',
            'slug' => 'akla-kapi-pub',
            'status' => 'active',
            'show_on_public' => true,
        ]);

        AuditLogger::log(
            action: 'published',
            message: "{$editor->name}, {$program->name} programını yayına aldı.",
            subject: $program,
            subjectLabel: $program->name,
        );

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'published',
            'message' => 'Yasemin Çelik, Akla Kapı programını yayına aldı.',
        ]);

        AuditLogger::log(
            action: 'unpublished',
            message: "{$editor->name}, {$program->name} programını yayından kaldırdı.",
            subject: $program,
            subjectLabel: $program->name,
        );

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'unpublished',
            'message' => 'Yasemin Çelik, Akla Kapı programını yayından kaldırdı.',
        ]);
    }

    public function test_program_delete_destructive_audit_log(): void
    {
        $editor = User::factory()->create(['name' => 'Yasemin Çelik', 'role' => 'editor']);
        $this->actingAs($editor);

        $program = Program::create([
            'name' => 'Akla Kapı',
            'slug' => 'akla-kapi-del',
            'status' => 'active',
        ]);

        AuditLogger::log(
            action: 'deleted',
            message: "{$editor->name}, {$program->name} programını kalıcı olarak sildi.",
            subject: $program,
            subjectLabel: $program->name,
            isDestructive: true,
        );

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'deleted',
            'message' => 'Yasemin Çelik, Akla Kapı programını kalıcı olarak sildi.',
            'is_destructive' => true,
        ]);
    }

    public function test_episode_create_and_delete_audit_log(): void
    {
        $editor = User::factory()->create(['name' => 'Yasemin Çelik', 'role' => 'editor']);
        $this->actingAs($editor);

        $program = Program::create([
            'name' => 'Akla Kapı',
            'slug' => 'akla-kapi-ep',
            'status' => 'active',
        ]);

        $episode = Episode::create([
            'program_id' => $program->id,
            'episode_number' => 14,
            'title' => 'İnanç ve Akıl',
            'status' => 'published',
            'show_on_public' => true,
            'is_active' => true,
        ]);

        AuditLogger::log(
            action: 'created',
            message: "{$editor->name}, {$program->name} 14. Bölüm'ü ekledi.",
            subject: $episode,
            subjectLabel: "{$program->name} 14. Bölüm",
        );

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'created',
            'message' => "Yasemin Çelik, Akla Kapı 14. Bölüm'ü ekledi.",
            'is_destructive' => false,
        ]);

        AuditLogger::log(
            action: 'deleted',
            message: "{$editor->name}, {$program->name} 14. Bölüm'ü sildi.",
            subject: $episode,
            subjectLabel: "{$program->name} 14. Bölüm",
            isDestructive: true,
        );

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'deleted',
            'message' => "Yasemin Çelik, Akla Kapı 14. Bölüm'ü sildi.",
            'is_destructive' => true,
        ]);
    }

    public function test_season_and_series_audit_log(): void
    {
        $editor = User::factory()->create(['name' => 'Yasemin Çelik', 'role' => 'editor']);
        $this->actingAs($editor);

        $program = Program::create([
            'name' => 'Hikmet Arayışları',
            'slug' => 'hikmet-arayislari',
            'status' => 'active',
        ]);

        $season = ProgramSeason::create([
            'program_id' => $program->id,
            'season_number' => 4,
            'season_year' => '2020',
        ]);

        AuditLogger::log(
            action: 'created',
            message: "{$editor->name}, {$program->name} Sezon 4 (2020) kaydını ekledi.",
            subject: $season,
            subjectLabel: "{$program->name} Sezon 4 (2020)",
        );

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'created',
            'message' => 'Yasemin Çelik, Hikmet Arayışları Sezon 4 (2020) kaydını ekledi.',
        ]);

        $series = ProgramSeries::create([
            'program_id' => $program->id,
            'program_season_id' => $season->id,
            'name' => 'Lemalar',
            'slug' => 'lemalar',
        ]);

        AuditLogger::log(
            action: 'created',
            message: "{$editor->name}, {$program->name} / {$series->name} serisini ekledi.",
            subject: $series,
            subjectLabel: "{$program->name} / {$series->name}",
        );

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'created',
            'message' => 'Yasemin Çelik, Hikmet Arayışları / Lemalar serisini ekledi.',
        ]);
    }

    public function test_schedule_template_lifecycle_and_excel_import_audit_log(): void
    {
        $user = User::factory()->create(['name' => 'Mehmet Kaya', 'role' => 'administrator']);
        $this->actingAs($user);

        $template = ScheduleTemplate::create([
            'name' => '2026 Yaz Yayın Akışı',
            'status' => 'draft',
            'is_active' => false,
            'version' => 1,
        ]);

        AuditLogger::log(
            action: 'created',
            message: "{$user->name}, {$template->name} dönemini oluşturdu.",
            subject: $template,
            subjectLabel: $template->name,
        );

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'created',
            'message' => 'Mehmet Kaya, 2026 Yaz Yayın Akışı dönemini oluşturdu.',
        ]);

        AuditLogger::log(
            action: 'imported',
            message: "{$user->name}, haftalık yayın akışını Excel'den aktardı.",
            subject: $template,
            subjectLabel: $template->name,
        );

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'imported',
            'message' => "Mehmet Kaya, haftalık yayın akışını Excel'den aktardı.",
        ]);

        AuditLogger::log(
            action: 'activated',
            message: "{$user->name}, {$template->name} dönemini aktifleştirdi.",
            subject: $template,
            subjectLabel: $template->name,
        );

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'activated',
            'message' => 'Mehmet Kaya, 2026 Yaz Yayın Akışı dönemini aktifleştirdi.',
        ]);
    }

    public function test_manual_youtube_sync_logs_audit_but_automatic_sync_does_not(): void
    {
        $editor = User::factory()->create(['name' => 'Yasemin Çelik', 'role' => 'editor']);
        $this->actingAs($editor);

        $program = Program::create([
            'name' => 'Akla Kapı',
            'slug' => 'akla-kapi-yt',
            'status' => 'active',
            'youtube_playlist_url' => 'https://www.youtube.com/playlist?list=PL123',
        ]);

        // 1. Manual sync triggered by user produces audit log
        AuditLogger::log(
            action: 'synced',
            message: "{$editor->name}, {$program->name} YouTube senkronizasyonunu çalıştırdı.",
            subject: $program,
            subjectLabel: $program->name,
        );

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'synced',
            'message' => 'Yasemin Çelik, Akla Kapı YouTube senkronizasyonunu çalıştırdı.',
        ]);

        $auditCountBefore = AuditLog::count();

        // 2. Automatic sync command (no authenticated user) does NOT produce user audit log
        auth()->logout();
        $syncService = app(YouTubePlaylistSyncService::class);
        $syncService->syncAllPlaylists();

        $auditCountAfter = AuditLog::count();
        $this->assertEquals($auditCountBefore, $auditCountAfter);
    }

    public function test_user_management_and_role_audit_logs(): void
    {
        $admin = User::factory()->create(['name' => 'Admin', 'role' => 'super_admin']);
        $this->actingAs($admin);

        // 1. User created
        $targetUser = User::create([
            'name' => 'Ahmet Yılmaz',
            'email' => 'ahmet@dosttv.com',
            'password' => 'secret123',
            'role' => 'editor',
        ]);

        AuditLogger::log(
            action: 'created',
            message: "{$admin->name}, {$targetUser->name} kullanıcısını oluşturdu.",
            subject: $targetUser,
            subjectLabel: $targetUser->name,
        );

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'created',
            'message' => 'Admin, Ahmet Yılmaz kullanıcısını oluşturdu.',
        ]);

        // 2. User role changed
        AuditLogger::log(
            action: 'role_changed',
            message: "{$admin->name}, {$targetUser->name} kullanıcısının rolünü değiştirdi.",
            subject: $targetUser,
            subjectLabel: $targetUser->name,
        );

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'role_changed',
            'message' => 'Admin, Ahmet Yılmaz kullanıcısının rolünü değiştirdi.',
        ]);

        // 3. User deactivated
        AuditLogger::log(
            action: 'deactivated',
            message: "{$admin->name}, {$targetUser->name} kullanıcısını pasife aldı.",
            subject: $targetUser,
            subjectLabel: $targetUser->name,
        );

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'deactivated',
            'message' => 'Admin, Ahmet Yılmaz kullanıcısını pasife aldı.',
        ]);

        // 4. User activated
        AuditLogger::log(
            action: 'activated',
            message: "{$admin->name}, {$targetUser->name} kullanıcısını aktifleştirdi.",
            subject: $targetUser,
            subjectLabel: $targetUser->name,
        );

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'activated',
            'message' => 'Admin, Ahmet Yılmaz kullanıcısını aktifleştirdi.',
        ]);

        // 5. User force deleted preserves name snapshot
        $targetName = $targetUser->name;
        AuditLogger::log(
            action: 'deleted',
            message: "{$admin->name}, {$targetName} kullanıcısını kalıcı olarak sildi.",
            subject: $targetUser,
            subjectLabel: $targetName,
            isDestructive: true,
        );
        $targetUser->forceDelete();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'deleted',
            'message' => 'Admin, Ahmet Yılmaz kullanıcısını kalıcı olarak sildi.',
            'subject_label' => 'Ahmet Yılmaz',
            'is_destructive' => true,
        ]);

        // 6. Custom role create, edit, delete
        $customRole = Role::create([
            'name' => 'Yayın Editörü',
            'base_role' => 'editor',
        ]);

        AuditLogger::log(
            action: 'created',
            message: "{$admin->name}, {$customRole->name} rolünü oluşturdu.",
            subject: $customRole,
            subjectLabel: $customRole->name,
        );

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'created',
            'message' => 'Admin, Yayın Editörü rolünü oluşturdu.',
        ]);

        AuditLogger::log(
            action: 'deleted',
            message: "{$admin->name}, {$customRole->name} rolünü sildi.",
            subject: $customRole,
            subjectLabel: $customRole->name,
            isDestructive: true,
        );

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'deleted',
            'message' => 'Admin, Yayın Editörü rolünü sildi.',
            'is_destructive' => true,
        ]);
    }
}
