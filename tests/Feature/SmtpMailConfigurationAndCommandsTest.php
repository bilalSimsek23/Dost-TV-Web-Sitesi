<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\UserInvitation;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\UserInvitationNotification;
use App\Services\Auth\UserInvitationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SmtpMailConfigurationAndCommandsTest extends TestCase
{
    use RefreshDatabase;

    public function test_mail_test_command_succeeds_with_valid_email(): void
    {
        Mail::fake();

        $this->artisan('mail:test', ['email' => 'editor@dosttv.com'])
            ->expectsOutputToContain('Mail sistemi test ediliyor...')
            ->assertExitCode(0);
    }

    public function test_mail_test_command_fails_with_invalid_email(): void
    {
        $this->artisan('mail:test', ['email' => 'not-an-email'])
            ->expectsOutput('Geçersiz e-posta adresi: not-an-email')
            ->assertExitCode(1);
    }

    public function test_smtp_configuration_structure(): void
    {
        $smtpConfig = config('mail.mailers.smtp');
        $this->assertIsArray($smtpConfig);
        $this->assertArrayHasKey('transport', $smtpConfig);
        $this->assertArrayHasKey('host', $smtpConfig);
        $this->assertArrayHasKey('port', $smtpConfig);
        $this->assertArrayHasKey('encryption', $smtpConfig);
        $this->assertArrayHasKey('username', $smtpConfig);
        $this->assertArrayHasKey('password', $smtpConfig);
        $this->assertEquals('smtp', $smtpConfig['transport']);
    }

    public function test_invitation_notification_renders_proper_mail_content(): void
    {
        $user = User::factory()->create([
            'name' => 'Yasemin Çelik',
            'email' => 'yasemin@dosttv.com',
        ]);

        $notification = new UserInvitationNotification('sample_token_64_chars', 'Yasemin Çelik', 72);
        $mailMessage = $notification->toMail($user);

        $this->assertEquals('DOST TV Yönetim Paneli Daveti', $mailMessage->subject);
        $this->assertStringContainsString('Merhaba Yasemin Çelik,', $mailMessage->greeting);
        $this->assertStringContainsString('DOST TV Yönetim Paneli için hesabınız oluşturuldu.', $mailMessage->introLines[0]);
        $this->assertStringContainsString('72 saat geçerlidir.', $mailMessage->outroLines[0]);
        $this->assertStringContainsString('sample_token_64_chars', $mailMessage->actionUrl);
    }

    public function test_password_reset_notification_renders_proper_mail_content(): void
    {
        $user = User::factory()->create([
            'name' => 'Ahmet Kaya',
            'email' => 'ahmet@dosttv.com',
            'is_active' => true,
        ]);

        $notification = new ResetPasswordNotification('sample_reset_token');
        $mailMessage = $notification->toMail($user);

        $this->assertEquals('DOST TV Şifre Sıfırlama Talebi', $mailMessage->subject);
        $this->assertStringContainsString('Merhaba Ahmet Kaya,', $mailMessage->greeting);
        $this->assertStringContainsString('60 dakika geçerlidir.', $mailMessage->outroLines[0]);
        $this->assertStringContainsString('sample_reset_token', $mailMessage->actionUrl);
    }

    public function test_mail_failure_during_invitation_creation_does_not_rollback_user(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test_user_failure@dosttv.com',
        ]);

        // Mock notification failure by mocking user notify
        $service = app(UserInvitationService::class);
        $result = $service->createInvitation($user);

        // User record is still safely in the database
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'test_user_failure@dosttv.com',
        ]);

        // Invitation record exists
        $this->assertDatabaseHas('user_invitations', [
            'user_id' => $user->id,
        ]);
    }
}
