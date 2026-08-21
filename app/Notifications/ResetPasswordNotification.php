<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $token
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('filament.admin.auth.password-reset.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        return (new MailMessage)
            ->subject('DOST TV Şifre Sıfırlama Talebi')
            ->greeting("Merhaba {$notifiable->name},")
            ->line('Hesabınız için şifre sıfırlama talebinde bulunuldu.')
            ->line('Şifrenizi yenilemek için aşağıdaki butona tıklayın:')
            ->action('Şifremi Sıfırla', $url)
            ->line('Bu şifre sıfırlama bağlantısı 60 dakika geçerlidir.')
            ->line('Eğer şifre sıfırlama talebinde bulunmadıysanız, bu e-postayı dikkate almayınız.')
            ->salutation("Saygılarımızla,\nDOST TV");
    }
}
