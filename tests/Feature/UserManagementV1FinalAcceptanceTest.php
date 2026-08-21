<?php

namespace Tests\Feature;

use App\Filament\Pages\MyProfilePage;
use App\Filament\Resources\Episodes\RelationManagers\EpisodesRelationManager;
use App\Filament\Resources\Programs\Pages\CreateProgram;
use App\Filament\Resources\Programs\Pages\EditProgram;
use App\Filament\Resources\ScheduleTemplates\Pages\CreateScheduleTemplate;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\AuditLog;
use App\Models\Episode;
use App\Models\Program;
use App\Models\Role;
use App\Models\ScheduleTemplate;
use App\Models\User;
use App\Models\UserInvitation;
use App\Notifications\UserInvitationNotification;
use App\Services\Audit\AuditLogger;
use App\Services\Auth\UserInvitationService;
use App\Services\YouTube\YouTubePlaylistSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class UserManagementV1FinalAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_full_lifecycle_and_protections(): void
    {
        $superAdmin = User::factory()->create([
            'name' => 'Ana Süper Admin',
            'email' => 'admin@dosttv.com',
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->actingAs($superAdmin);

        // 1. Panel & resource access
        $this->get('/admin/users')->assertSuccessful();
        $this->get('/admin/roles')->assertSuccessful();
        $this->get('/admin/audit-logs')->assertSuccessful();
        $this->get('/admin/site-settings')->assertSuccessful();

        // 2. Protections against self demotion, deactivation, and deletion
        $superAdmin->role = 'editor';
        $superAdmin->is_active = false;
        $superAdmin->save();

        $superAdmin->refresh();
        $this->assertEquals('super_admin', $superAdmin->role);
        $this->assertTrue($superAdmin->is_active);
        $this->assertFalse($superAdmin->delete());
        $this->assertDatabaseHas('users', ['id' => $superAdmin->id]);
    }

    public function test_administrator_capabilities_and_strict_boundaries(): void
    {
        $admin = User::factory()->create(['name' => 'Yönetici', 'role' => 'administrator']);
        $this->actingAs($admin);

        // Permitted routes
        $this->get('/admin/users')->assertSuccessful();
        $this->get('/admin/audit-logs')->assertSuccessful();
        $this->get('/admin/programs')->assertSuccessful();
        $this->get('/admin/schedule-templates')->assertSuccessful();

        // Forbidden routes
        $this->get('/admin/roles')->assertForbidden();
        $this->get('/admin/roles/create')->assertForbidden();
        $this->get('/admin/site-settings')->assertForbidden();

        // Cannot force delete users
        $editor = User::factory()->create(['name' => 'Editor', 'role' => 'editor']);
        $editor->delete();
        $this->assertFalse($admin->can('forceDelete', $editor));
    }

    public function test_editor_full_content_crud_and_forbidden_administrative_boundaries(): void
    {
        $editor = User::factory()->create(['name' => 'Editör Kullanıcı', 'role' => 'editor']);
        $this->actingAs($editor);

        // Forbidden administrative routes
        $this->get('/admin/users')->assertForbidden();
        $this->get('/admin/roles')->assertForbidden();
        $this->get('/admin/audit-logs')->assertForbidden();
        $this->get('/admin/site-settings')->assertForbidden();

        // Permitted profile routes
        $this->get('/admin/hesabim')->assertSuccessful();

        // Permitted content CRUD
        $program = Program::create([
            'name' => 'Test Programı',
            'slug' => 'test-programi',
            'status' => 'active',
            'is_active' => true,
        ]);

        $episode = Episode::create([
            'program_id' => $program->id,
            'episode_number' => 1,
            'title' => 'Bölüm 1',
            'status' => 'published',
            'show_on_public' => true,
            'is_active' => true,
        ]);

        $this->assertTrue($episode->delete());
        $this->assertTrue($program->delete());
    }

    public function test_end_to_end_invitation_flow_without_password_and_acceptance(): void
    {
        Notification::fake();

        $superAdmin = User::factory()->create(['name' => 'Admin', 'role' => 'super_admin']);
        $editorRole = Role::where('slug', 'editor')->first();

        $this->actingAs($superAdmin);

        // 1. Create user without password
        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Yeni Editör',
                'email' => 'yeni.editor@dosttv.com',
                'phone' => '5321234567',
                'role_id' => $editorRole?->id,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $createdUser = User::where('email', 'yeni.editor@dosttv.com')->first();
        $this->assertNotNull($createdUser);
        $this->assertEquals('+905321234567', $createdUser->phone);
        $this->assertEquals('editor', $createdUser->role);

        // 2. Invitation token inspection
        $invitation = UserInvitation::where('user_id', $createdUser->id)->first();
        $this->assertNotNull($invitation);
        $this->assertEquals(64, strlen($invitation->token_hash));
        $this->assertEquals(72, (int) round(now()->diffInHours($invitation->expires_at)));

        // User cannot log in before setting password
        $this->assertFalse(auth()->attempt(['email' => 'yeni.editor@dosttv.com', 'password' => 'secret123']));

        // 3. User accepts invitation via token
        $service = app(UserInvitationService::class);
        $res = $service->createInvitation($createdUser, $superAdmin);
        $token = $res['token'];

        $this->get(route('invitation.accept', ['token' => $token]))->assertSuccessful();

        $this->post(route('invitation.accept.post', ['token' => $token]), [
            'password' => 'editor_secure_pass_99',
            'password_confirmation' => 'editor_secure_pass_99',
        ])->assertRedirect('/admin/login');

        // 4. Token cannot be reused
        $this->get(route('invitation.accept', ['token' => $token]))
            ->assertSee('Bu davet bağlantısı geçersiz veya süresi dolmuş.');

        // 5. User logs in successfully with new password
        $this->assertTrue(auth()->attempt([
            'email' => 'yeni.editor@dosttv.com',
            'password' => 'editor_secure_pass_99',
        ]));
    }

    public function test_inactive_to_active_lifecycle_e2e(): void
    {
        $user = User::factory()->create([
            'name' => 'Pasif Kullanıcı',
            'email' => 'pasif@dosttv.com',
            'password' => Hash::make('known_password_123'),
            'is_active' => true,
            'role' => 'editor',
        ]);

        // 1. Deactivate
        $user->update(['is_active' => false]);
        $this->assertFalse($user->fresh()->is_active);

        // 2. Reactivate -> triggers new password invitation and randomizes password
        $user->update(['is_active' => true]);
        $user->refresh();

        // Old password no longer works
        $this->assertFalse(Hash::check('known_password_123', $user->password));

        // Invitation exists
        $invitation = UserInvitation::where('user_id', $user->id)->latest()->first();
        $this->assertNotNull($invitation);
        $this->assertTrue($invitation->isValid());
    }

    public function test_profile_and_personal_audit_isolation(): void
    {
        $editor = User::factory()->create([
            'name' => 'Editör Profil',
            'email' => 'editor_profil@dosttv.com',
            'phone' => '+905321112233',
            'role' => 'editor',
            'is_active' => true,
        ]);

        $otherUser = User::factory()->create(['name' => 'Başka Kullanıcı']);

        AuditLog::create([
            'user_id' => $otherUser->id,
            'user_name_snapshot' => 'Başka Kullanıcı',
            'action' => 'created',
            'message' => 'Gizli işlem',
        ]);

        AuditLog::create([
            'user_id' => $editor->id,
            'user_name_snapshot' => 'Editör Profil',
            'action' => 'created',
            'message' => 'Editörün kendi işlemi',
        ]);

        $this->actingAs($editor);

        // Profile view
        Livewire::test(MyProfilePage::class)
            ->set('accountData.name', 'Editör Profil Güncellendi')
            ->set('accountData.phone', '5551234567')
            ->set('accountData.email', 'hacker@dosttv.com')
            ->call('updateProfile');

        $editor->refresh();
        $this->assertEquals('Editör Profil Güncellendi', $editor->name);
        $this->assertEquals('+905551234567', $editor->phone);
        $this->assertEquals('editor_profil@dosttv.com', $editor->email);

        // Personal audit logs isolation
        Livewire::test(MyProfilePage::class)
            ->set('activeTab', 'audit_logs')
            ->assertSee('Editörün kendi işlemi')
            ->assertDontSee('Gizli işlem');
    }

    public function test_audit_retention_prune_command(): void
    {
        // Record older than 6 months
        AuditLog::create([
            'user_name_snapshot' => 'Eski Kullanıcı',
            'action' => 'created',
            'message' => 'Çok eski log',
            'created_at' => now()->subMonths(7),
        ]);

        // Fresh record
        AuditLog::create([
            'user_name_snapshot' => 'Yeni Kullanıcı',
            'action' => 'created',
            'message' => 'Yeni log',
            'created_at' => now()->subDays(2),
        ]);

        $this->artisan('audit:prune', ['--dry-run' => true])->assertExitCode(0);
        $this->assertEquals(2, AuditLog::count());

        $this->artisan('audit:prune')->assertExitCode(0);
        $this->assertEquals(1, AuditLog::count());
        $this->assertEquals('Yeni log', AuditLog::first()->message);
    }
}
