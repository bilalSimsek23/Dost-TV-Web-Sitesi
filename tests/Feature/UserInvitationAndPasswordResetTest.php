<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use App\Models\UserInvitation;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\UserInvitationNotification;
use App\Services\Auth\UserInvitationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class UserInvitationAndPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_user_is_created_without_password_and_invitation_created(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['name' => 'Admin', 'role' => 'super_admin']);
        $editorRole = Role::where('slug', 'editor')->first();

        $this->actingAs($admin);

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Yasemin Çelik',
                'email' => 'yasemin@dosttv.com',
                'phone' => '5321112233',
                'role_id' => $editorRole?->id,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::where('email', 'yasemin@dosttv.com')->first();
        $this->assertNotNull($user);

        // Assert invitation was created
        $invitation = UserInvitation::where('user_id', $user->id)->first();
        $this->assertNotNull($invitation);
        $this->assertEquals('yasemin@dosttv.com', $invitation->email);
        $this->assertEquals(64, strlen($invitation->token_hash)); // sha256 hash length

        // Assert expires_at is roughly 72 hours
        $this->assertTrue($invitation->expires_at->isFuture());
        $this->assertEquals(72, (int) round(now()->diffInHours($invitation->expires_at)));

        // Notification sent
        Notification::assertSentTo($user, UserInvitationNotification::class);

        // Audit log created
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'invited',
            'user_name_snapshot' => 'Admin',
            'message' => 'Admin, Yasemin Çelik kullanıcısına davet gönderdi.',
        ]);
    }

    public function test_valid_token_allows_password_setting_and_marks_accepted(): void
    {
        $user = User::factory()->create([
            'name' => 'Yasemin Çelik',
            'email' => 'yasemin@dosttv.com',
            'password' => Hash::make('unguessable_temp_pass'),
            'role' => 'editor',
            'is_active' => true,
        ]);

        $service = app(UserInvitationService::class);
        $result = $service->createInvitation($user);
        $token = $result['token'];

        // Visit accept page
        $response = $this->get(route('invitation.accept', ['token' => $token]));
        $response->assertSuccessful();
        $response->assertSee('Yasemin Çelik');

        // Submit new password
        $postResponse = $this->post(route('invitation.accept.post', ['token' => $token]), [
            'password' => 'new_secure_pass_123',
            'password_confirmation' => 'new_secure_pass_123',
        ]);

        $postResponse->assertRedirect('/admin/login');

        $user->refresh();
        $this->assertTrue(Hash::check('new_secure_pass_123', $user->password));

        $invitation = UserInvitation::where('user_id', $user->id)->latest()->first();
        $this->assertTrue($invitation->isAccepted());
        $this->assertNotNull($invitation->accepted_at);

        // User can now log in
        $this->assertTrue(auth()->attempt([
            'email' => 'yasemin@dosttv.com',
            'password' => 'new_secure_pass_123',
        ]));
    }

    public function test_token_cannot_be_reused_after_acceptance(): void
    {
        $user = User::factory()->create(['name' => 'Yasemin Çelik', 'email' => 'yasemin@dosttv.com']);
        $service = app(UserInvitationService::class);
        $result = $service->createInvitation($user);
        $token = $result['token'];

        // Accept once
        $this->post(route('invitation.accept.post', ['token' => $token]), [
            'password' => 'new_pass_123',
            'password_confirmation' => 'new_pass_123',
        ]);

        // Attempting second time fails
        $secondResponse = $this->get(route('invitation.accept', ['token' => $token]));
        $secondResponse->assertSee('Bu davet bağlantısı geçersiz veya süresi dolmuş.');
    }

    public function test_expired_token_is_rejected(): void
    {
        $user = User::factory()->create(['name' => 'Yasemin Çelik', 'email' => 'yasemin@dosttv.com']);
        $service = app(UserInvitationService::class);
        $result = $service->createInvitation($user);
        $token = $result['token'];

        // Travel 73 hours into the future
        $this->travel(73)->hours();

        $response = $this->get(route('invitation.accept', ['token' => $token]));
        $response->assertSee('Bu davet bağlantısı geçersiz veya süresi dolmuş.');
    }

    public function test_cancelled_token_is_rejected(): void
    {
        $user = User::factory()->create(['name' => 'Yasemin Çelik', 'email' => 'yasemin@dosttv.com']);
        $service = app(UserInvitationService::class);
        $result = $service->createInvitation($user);
        $token = $result['token'];

        // Cancel invitation
        $service->cancelInvitation($user);

        $response = $this->get(route('invitation.accept', ['token' => $token]));
        $response->assertSee('Bu davet bağlantısı geçersiz veya süresi dolmuş.');
    }

    public function test_resending_invitation_invalidates_old_token_and_creates_fresh_72_hours(): void
    {
        Notification::fake();

        $user = User::factory()->create(['name' => 'Yasemin Çelik', 'email' => 'yasemin@dosttv.com']);
        $service = app(UserInvitationService::class);
        $firstResult = $service->createInvitation($user);
        $firstToken = $firstResult['token'];

        // Resend
        $secondResult = $service->resendInvitation($user);
        $secondToken = $secondResult['token'];

        $this->assertNotEquals($firstToken, $secondToken);

        // First token is now cancelled
        $response1 = $this->get(route('invitation.accept', ['token' => $firstToken]));
        $response1->assertSee('Bu davet bağlantısı geçersiz veya süresi dolmuş.');

        // Second token is valid
        $response2 = $this->get(route('invitation.accept', ['token' => $secondToken]));
        $response2->assertSuccessful();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'invitation_resent',
            'message' => 'Sistem, Yasemin Çelik kullanıcısının davetini tekrar gönderdi.',
        ]);
    }

    public function test_inactive_to_active_transition_triggers_password_reset_invitation(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'name' => 'Yasemin Çelik',
            'email' => 'yasemin@dosttv.com',
            'password' => Hash::make('known_password_123'),
            'is_active' => false,
            'role' => 'editor',
        ]);

        $admin = User::factory()->create(['name' => 'Admin', 'role' => 'super_admin']);
        $this->actingAs($admin);

        // Reactivate user
        $user->update(['is_active' => true]);

        // Old password no longer matches because it was randomized upon reactivation
        $user->refresh();
        $this->assertFalse(Hash::check('known_password_123', $user->password));

        // Fresh invitation was sent
        $invitation = UserInvitation::where('user_id', $user->id)->latest()->first();
        $this->assertNotNull($invitation);
        $this->assertTrue($invitation->isValid());

        Notification::assertSentTo($user, UserInvitationNotification::class);
    }

    public function test_forgot_password_notification_is_sent_only_to_active_users(): void
    {
        Notification::fake();

        $activeUser = User::factory()->create([
            'email' => 'active@dosttv.com',
            'is_active' => true,
        ]);

        $inactiveUser = User::factory()->create([
            'email' => 'inactive@dosttv.com',
            'is_active' => false,
        ]);

        // Active user receives notification
        $activeUser->sendPasswordResetNotification('sample_token_123');
        Notification::assertSentTo($activeUser, ResetPasswordNotification::class);

        // Inactive user does NOT receive notification
        $inactiveUser->sendPasswordResetNotification('sample_token_456');
        Notification::assertNotSentTo($inactiveUser, ResetPasswordNotification::class);
    }

    public function test_legacy_existing_users_and_admin_continue_logging_in(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@dosttv.com',
            'password' => Hash::make('admin_pass_123'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $editor = User::factory()->create([
            'email' => 'editor@dosttv.com',
            'password' => Hash::make('editor_pass_123'),
            'role' => 'editor',
            'is_active' => true,
        ]);

        $this->assertTrue(auth()->attempt(['email' => 'admin@dosttv.com', 'password' => 'admin_pass_123']));
        auth()->logout();

        $this->assertTrue(auth()->attempt(['email' => 'editor@dosttv.com', 'password' => 'editor_pass_123']));
        auth()->logout();
    }

    public function test_invitation_and_password_tokens_never_leak_to_audit_logs(): void
    {
        $user = User::factory()->create(['name' => 'Yasemin Çelik', 'email' => 'yasemin@dosttv.com']);
        $service = app(UserInvitationService::class);
        $result = $service->createInvitation($user);
        $token = $result['token'];

        $service->acceptInvitation($token, 'brand_new_secret_pass');

        $auditLogs = AuditLog::where('user_id', $user->id)->get();
        foreach ($auditLogs as $log) {
            $json = json_encode($log->toArray());
            $this->assertStringNotContainsString($token, $json);
            $this->assertStringNotContainsString('brand_new_secret_pass', $json);
        }
    }
}
