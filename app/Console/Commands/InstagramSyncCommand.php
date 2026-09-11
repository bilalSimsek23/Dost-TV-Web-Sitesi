<?php

namespace App\Console\Commands;

use App\Services\Instagram\InstagramSyncService;
use Illuminate\Console\Command;

class InstagramSyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'instagram:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Instagram Graph API üzerinden DOST TV hesabındaki en son Reels ve videoları senkronize eder.';

    /**
     * Execute the console command.
     */
    public function handle(InstagramSyncService $syncService): int
    {
        $this->info('Instagram Reels senkronizasyonu başlatılıyor...');

        try {
            $stats = $syncService->syncLatestReels();

            $this->info("Senkronizasyon tamamlandı!");
            $this->line("Toplam Çekilen: {$stats['total_fetched']}");
            $this->line("Yeni İçeri Alınan: {$stats['imported']}");
            $this->line("Güncellenen: {$stats['updated']}");
            $this->line("Atlanan (Reel Dışı): {$stats['skipped']}");

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Senkronizasyon Hatası: {$e->getMessage()}");

            return Command::FAILURE;
        }
    }
}
