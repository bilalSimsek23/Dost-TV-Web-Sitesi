<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $token,
        public string $userName,
        public int $expiresInHours = 72
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('invitation.accept', ['token' => $this->token]);

        return (new MailMessage)
            ->subject('DOST TV Yönetim Paneli Daveti')
            ->greeting("Merhaba {$this->userName},")
            ->line('DOST TV Yönetim Paneli için hesabınız oluşturuldu.')
            ->line('Hesabınızı etkinleştirmek ve şifrenizi belirlemek için aşağıdaki bağlantıyı kullanabilirsiniz:')
            ->action('Şifremi Belirle', $url)
            ->line("Bu bağlantı {$this->expiresInHours} saat geçerlidir.")
            ->line('Bu daveti siz beklemiyorsanız herhangi bir işlem yapmanız gerekmez.')
            ->salutation("Saygılarımızla,\nDOST TV");
    }
}
