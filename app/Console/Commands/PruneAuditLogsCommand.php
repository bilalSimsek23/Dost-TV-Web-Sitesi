<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneAuditLogsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audit:prune {--dry-run : Sadece silinecek kayıt sayısını gösterir}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '6 aydan eski işlem geçmişi (audit log) kayıtlarını temizler.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $cutoff = now()->subMonths(6);
        $query = AuditLog::where('created_at', '<', $cutoff);
        $count = $query->count();

        if ($this->option('dry-run')) {
            $this->info("Dry-run: 6 aydan eski {$count} adet işlem geçmişi kaydı silinecek.");
            return self::SUCCESS;
        }

        if ($count === 0) {
            $this->info('Temizlenecek 6 aydan eski işlem geçmişi kaydı bulunamadı.');
            return self::SUCCESS;
        }

        $deleted = DB::transaction(function () use ($query) {
            return $query->delete();
        });

        $this->info("6 aydan eski {$deleted} adet işlem geçmişi kaydı başarıyla temizlendi.");

        return self::SUCCESS;
    }
}
