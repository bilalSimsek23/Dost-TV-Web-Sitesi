<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendTestMailCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:test {email : Hedef e-posta adresi}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'SMTP/Mail yapılandırmasını test etmek için belirtilen adrese test e-postası gönderir';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error("Geçersiz e-posta adresi: {$email}");
            return self::FAILURE;
        }

        $mailer = config('mail.default');
        $fromAddress = config('mail.from.address');
        $fromName = config('mail.from.name');

        $this->info("Mail sistemi test ediliyor...");
        $this->line("• Mailer: <comment>{$mailer}</comment>");
        if ($mailer === 'smtp') {
            $host = config('mail.mailers.smtp.host');
            $port = config('mail.mailers.smtp.port');
            $encryption = config('mail.mailers.smtp.encryption') ?: 'none';
            $this->line("• SMTP Sunucu: <comment>{$host}:{$port} ({$encryption})</comment>");
        }
        $this->line("• Gönderen: <comment>{$fromName} <{$fromAddress}></comment>");
        $this->line("• Alıcı: <comment>{$email}</comment>");

        try {
            Mail::raw("DOST TV CMS mail sistemi başarıyla çalışıyor.\n\nBu e-posta sistem test komutu (mail:test) tarafından gönderilmiştir.", function ($message) use ($email, $fromAddress, $fromName) {
                $message->to($email)
                    ->subject('DOST TV CMS Test E-postası')
                    ->from($fromAddress, $fromName);
            });

            if ($mailer === 'log') {
                $this->info("✓ Test e-postası 'log' mailer ile storage/logs/laravel.log dosyasına başarıyla yazıldı.");
            } else {
                $this->info("✓ Test e-postası '{$email}' adresine başarıyla gönderildi.");
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("✗ E-posta gönderimi başarısız oldu!");
            $this->error("Hata Detayı: " . $e->getMessage());

            if ($mailer === 'smtp') {
                $this->line("\n<comment>Öneri: .env dosyasındaki MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD ve MAIL_ENCRYPTION değerlerini kontrol edin.</comment>");
            }

            return self::FAILURE;
        }
    }
}
