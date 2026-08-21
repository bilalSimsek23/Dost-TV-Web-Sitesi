<?php

namespace Tests\Feature;

use App\Filament\Pages\MyProfilePage;
use App\Filament\Pages\SiteSettings;
use App\Filament\Resources\Episodes\RelationManagers\EpisodesRelationManager;
use App\Filament\Resources\Programs\Pages\CreateProgram;
use App\Filament\Resources\Programs\Pages\EditProgram;
use App\Filament\Resources\Roles\Pages\CreateRole;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\AuditLog;
use App\Models\Episode;
use App\Models\Program;
use App\Models\Role;
use App\Models\ScheduleTemplate;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\YouTube\YouTubePlaylistSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class SecurityAndAuthorizationHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_direct_request_cannot_assign_super_admin_role_on_create_or_edit(): void
    {
        $admin = User::factory()->create(['name' => 'Admin', 'role' => 'administrator']);
        $superAdminRole = Role::where('slug', 'super-admin')->first();
        $editorRole = Role::where('slug', 'editor')->first();

        $this->actingAs($admin);

        // 1. On Create with invalid super_admin role selection -> form rejects it
        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Hacker User',
                'email' => 'hacker@dosttv.com',
                'password' => 'secret123',
                'password_confirmation' => 'secret123',
                'role_id' => $superAdminRole?->id,
            ])
            ->call('create')
            ->assertHasFormErrors(['role_id']);

        // 2. On Edit with invalid super_admin role selection -> form rejects it
        $editorUser = User::factory()->create(['name' => 'Editor', 'role' => 'editor', 'role_id' => $editorRole?->id]);

        Livewire::test(EditUser::class, ['record' => $editorUser->getKey()])
            ->fillForm([
                'name' => 'Editor Renamed',
                'role_id' => $superAdminRole?->id,
            ])
            ->call('save')
            ->assertHasFormErrors(['role_id']);

        $editorUser->refresh();
        $this->assertNotEquals('super_admin', $editorUser->role);
    }

    public function test_administrator_cannot_access_or_create_roles(): void
    {
        $admin = User::factory()->create(['name' => 'Admin', 'role' => 'administrator']);
        $this->actingAs($admin);

        // Administrator is completely forbidden from accessing /admin/roles and creating roles
        $this->get('/admin/roles')->assertForbidden();
        $this->get('/admin/roles/create')->assertForbidden();
    }

    public function test_editor_access_to_restricted_admin_routes_is_forbidden(): void
    {
        $editor = User::factory()->create(['name' => 'Editor', 'role' => 'editor']);
        $this->actingAs($editor);

        // 1. Users
        $this->get('/admin/users')->assertForbidden();

        // 2. Roles
        $this->get('/admin/roles')->assertForbidden();

        // 3. Global Audit Logs
        $this->get('/admin/audit-logs')->assertForbidden();

        // 4. Site Settings
        $this->get('/admin/site-settings')->assertForbidden();
    }

    public function test_editor_can_perform_full_content_crud_and_episode_delete(): void
    {
        $editor = User::factory()->create(['name' => 'Editor', 'role' => 'editor']);
        $this->actingAs($editor);

        // 1. Create Program
        Livewire::test(CreateProgram::class)
            ->fillForm([
                'name' => 'Editor Program',
                'slug' => 'editor-program',
                'status' => 'active',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $program = Program::where('slug', 'editor-program')->first();
        $this->assertNotNull($program);

        // 2. Edit Program
        Livewire::test(EditProgram::class, ['record' => $program->getKey()])
            ->fillForm([
                'name' => 'Editor Program Updated',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $program->refresh();
        $this->assertEquals('Editor Program Updated', $program->name);

        // 3. Create Episode
        $episode = Episode::create([
            'program_id' => $program->id,
            'episode_number' => 1,
            'title' => 'Test Episode',
            'status' => 'published',
            'show_on_public' => true,
            'is_active' => true,
        ]);

        // 4. Delete Episode
        $this->assertTrue($episode->delete());
        $this->assertDatabaseMissing('episodes', ['id' => $episode->id]);

        // 5. Delete Program
        $this->assertTrue($program->delete());
        $this->assertDatabaseMissing('programs', ['id' => $program->id]);
    }

    public function test_inactive_user_cannot_access_panel_and_sessions_are_cleared(): void
    {
        $user = User::factory()->create([
            'email' => 'active@dosttv.com',
            'is_active' => true,
            'role' => 'editor',
        ]);

        DB::table('sessions')->insert([
            'id' => 'test_session_hardening_123',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => 'payload',
            'last_activity' => time(),
        ]);

        $this->assertEquals(1, DB::table('sessions')->where('user_id', $user->id)->count());

        // Deactivate user
        $user->update(['is_active' => false]);

        // Assert session deleted
        $this->assertEquals(0, DB::table('sessions')->where('user_id', $user->id)->count());

        // Assert cannot access panel
        $this->actingAs($user);
        $this->assertFalse($user->canAccessPanel(\Filament\Facades\Filament::getPanel('admin')));
        $this->get('/admin')->assertForbidden();
    }

    public function test_last_active_super_admin_protections_at_model_level(): void
    {
        $superAdmin = User::factory()->create([
            'email' => 'super@dosttv.com',
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        // 1. Attempt role demotion
        $superAdmin->role = 'editor';
        $superAdmin->role_id = Role::where('slug', 'editor')->value('id');
        $superAdmin->save();

        $superAdmin->refresh();
        $this->assertEquals('super_admin', $superAdmin->role);

        // 2. Attempt deactivation
        $superAdmin->is_active = false;
        $superAdmin->save();

        $superAdmin->refresh();
        $this->assertTrue($superAdmin->is_active);

        // 3. Attempt deletion
        $this->assertFalse($superAdmin->delete());
        $this->assertDatabaseHas('users', ['id' => $superAdmin->id]);
    }

    public function test_profile_mass_assignment_protection_and_audit_privacy(): void
    {
        $user = User::factory()->create([
            'name' => 'Profile User',
            'email' => 'profile@dosttv.com',
            'password' => 'secret123',
            'role' => 'editor',
            'is_active' => true,
        ]);

        $this->actingAs($user);

        Livewire::test(MyProfilePage::class)
            ->set('accountData.name', 'Profile User Updated')
            ->set('accountData.email', 'tampered@dosttv.com')
            ->set('accountData.role', 'super_admin')
            ->set('accountData.is_active', false)
            ->call('updateProfile');

        $user->refresh();
        $this->assertEquals('Profile User Updated', $user->name);
        $this->assertEquals('profile@dosttv.com', $user->email);
        $this->assertEquals('editor', $user->role);
        $this->assertTrue($user->is_active);

        // Check password change audit privacy
        Livewire::test(MyProfilePage::class)
            ->set('passwordData.current_password', 'secret123')
            ->set('passwordData.new_password', 'super_safe_new_pass_999')
            ->set('passwordData.new_password_confirmation', 'super_safe_new_pass_999')
            ->call('updatePassword');

        $auditLog = AuditLog::where('user_id', $user->id)
            ->where('action', 'updated')
            ->where('message', 'Profile User Updated, şifresini değiştirdi.')
            ->first();

        $this->assertNotNull($auditLog);
        $this->assertNull($auditLog->metadata);
        $json = json_encode($auditLog->toArray());
        $this->assertStringNotContainsString('super_safe_new_pass_999', $json);
        $this->assertStringNotContainsString('secret123', $json);
    }

    public function test_system_roles_immutability_at_model_level(): void
    {
        $systemRole = Role::where('slug', 'super-admin')->first();
        $this->assertNotNull($systemRole);
        $this->assertTrue($systemRole->isSystem());

        // 1. Rename attempt
        $systemRole->name = 'Hacked Name';
        $systemRole->base_role = 'editor';
        $systemRole->is_active = false;
        $systemRole->save();

        $systemRole->refresh();
        $this->assertEquals('Süper Admin', $systemRole->name);
        $this->assertEquals('super_admin', $systemRole->base_role);
        $this->assertTrue($systemRole->is_active);

        // 2. Delete attempt
        $this->assertFalse($systemRole->delete());
        $this->assertDatabaseHas('roles', ['slug' => 'super-admin']);
    }

    public function test_automatic_youtube_sync_produces_zero_user_audit_logs(): void
    {
        Program::create([
            'name' => 'Akla Kapı Test',
            'slug' => 'akla-kapi-test',
            'status' => 'active',
            'youtube_playlist_url' => 'https://www.youtube.com/playlist?list=PL123',
        ]);

        $initialAuditCount = AuditLog::count();

        auth()->logout();
        $service = app(YouTubePlaylistSyncService::class);
        $service->syncAllPlaylists();

        $this->assertEquals($initialAuditCount, AuditLog::count());
    }
}
