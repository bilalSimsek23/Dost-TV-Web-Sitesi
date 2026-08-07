<?php

namespace App\Console\Commands;

use App\Models\YoutubeSyncLog;
use Illuminate\Console\Command;

class PruneYoutubeSyncLogsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'youtube:prune-sync-logs {--days=90 : Number of days to retain logs} {--dry-run : Simulate log pruning without deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune YouTube sync logs older than specified number of days.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $isDryRun = (bool) $this->option('dry-run');

        $cutoffDate = now()->subDays($days);
        $query = YoutubeSyncLog::where('created_at', '<', $cutoffDate);
        $count = $query->count();

        if ($isDryRun) {
            $this->info("--- SIMULATION MODE (--dry-run) ---");
            $this->info("{$days} günden eski {$count} adet log kaydı silinecekti.");
            return Command::SUCCESS;
        }

        $deleted = $query->delete();
        $this->info("{$days} günden eski {$deleted} adet senkronizasyon logu başarıyla temizlendi.");

        return Command::SUCCESS;
    }
}
