<?php

namespace App\Console\Commands;

use App\Models\Episode;
use App\Models\Program;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RenumberProgramEpisodesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'episodes:renumber-program {--program= : Program ID, name, or slug} {--dry-run : Run in simulation mode without modifying database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Safely renumber program episodes chronologically by aired_at date.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $programInput = $this->option('program') ?: 'Akla Kapı';
        $isDryRun = (bool) $this->option('dry-run');

        // 1. Program Identification
        $query = Program::query();
        if (is_numeric($programInput)) {
            $query->where('id', (int) $programInput);
        } else {
            $query->where('name', $programInput)
                ->orWhere('slug', $programInput)
                ->orWhere('name', 'like', "%{$programInput}%");
        }

        $programs = $query->get();

        if ($programs->count() === 0) {
            $this->error("HATA: '{$programInput}' adında bir program bulunamadı.");
            return Command::FAILURE;
        }

        if ($programs->count() > 1) {
            $this->error("HATA: '{$programInput}' aramasıyla birden fazla program eşleşti:");
            foreach ($programs as $p) {
                $this->line(" - ID: {$p->id} | Adı: {$p->name} | Slug: {$p->slug}");
            }
            $this->warn("Lütfen işlemi daraltmak için '--program=ID' parametresini kullanın.");
            return Command::FAILURE;
        }

        $program = $programs->first();
        $episodes = Episode::where('program_id', $program->id)
            ->orderByRaw('CASE WHEN aired_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('aired_at', 'asc')
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $totalCount = $episodes->count();

        $this->info("==========================================================");
        $this->info("PROGRAM DETAYLARI");
        $this->info("==========================================================");
        $this->line("Program ID          : {$program->id}");
        $this->line("Program Adı         : {$program->name}");
        $this->line("Program Slug        : {$program->slug}");
        $this->line("Toplam Bölüm Sayısı : {$totalCount}");
        $this->info("==========================================================");

        if ($totalCount === 0) {
            $this->warn("Bu programa ait yenilenecek bölüm bulunamadı.");
            return Command::SUCCESS;
        }

        // Check for NULL aired_at records
        $nullDateEpisodes = $episodes->filter(fn ($ep) => blank($ep->aired_at));
        if ($nullDateEpisodes->isNotEmpty()) {
            $this->warn("DİKKAT: {$nullDateEpisodes->count()} adet bölümün yayın tarihi (aired_at) NULL! Bu kayıtlar listenin sonuna alındı:");
            foreach ($nullDateEpisodes as $nEp) {
                $this->line(" - ID: {$nEp->id} | Mevcut No: {$nEp->episode_number} | Başlık: {$nEp->title}");
            }
            $this->newLine();
        }

        // Generate mapping
        $mapping = [];
        $snapshot = [];
        $newNumber = 1;

        foreach ($episodes as $episode) {
            $mapping[] = [
                'id' => $episode->id,
                'old_num' => $episode->episode_number,
                'new_num' => $newNumber,
                'aired_at' => $episode->aired_at ? $episode->aired_at->format('d.m.Y') : 'Yayın Tarihi Yok',
                'title' => $episode->title,
            ];

            $snapshot[$episode->id] = [
                'old_episode_number' => $episode->episode_number,
                'new_episode_number' => $newNumber,
                'aired_at' => $episode->aired_at ? $episode->aired_at->format('Y-m-d') : null,
                'title' => $episode->title,
            ];

            $newNumber++;
        }

        // Output table
        $this->newLine();
        $this->info("==========================================================");
        $this->info("SIRA DÜZELTME PLANž (ESKİ -> YENİ KRONOLOJİK)");
        $this->info("==========================================================");

        $headers = ['Eski No', 'Yeni No', 'Yayın Tarihi', 'Başlık'];
        $rows = array_map(fn ($m) => [
            'Eski No' => $m['old_num'] ?? '-',
            'Yeni No' => $m['new_num'],
            'Yayın Tarihi' => $m['aired_at'],
            'Başlık' => mb_strimwidth($m['title'], 0, 45, '...'),
        ], $mapping);

        $this->table($headers, $rows);

        // Snapshot Save
        $snapshotPath = storage_path('logs/renumber_snapshot_program_' . $program->id . '_' . date('Ymd_His') . '.json');
        @file_put_contents($snapshotPath, json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $this->info("Snapshot kaydedildi: {$snapshotPath}");

        if ($isDryRun) {
            $this->newLine();
            $this->info("--- SIMULATION MODE (--dry-run) ---");
            $this->info("Veritabanında hiçbir değişiklik yapılmadı.");
            $this->info("Gerçek güncellemeyi çalıştırmak için '--dry-run' bayrağını kaldırarak çalıştırın.");
            return Command::SUCCESS;
        }

        // 2. Real Database Update inside Transaction
        $this->newLine();
        $this->warn("VERİTABANI GÜNCELLEMESİ BAŞLATILIYOR...");

        try {
            DB::transaction(function () use ($mapping) {
                // Phase 1: Temporary negative values to avoid unique collisions
                foreach ($mapping as $index => $item) {
                    DB::table('episodes')
                        ->where('id', $item['id'])
                        ->update(['episode_number' => -10000 - $index]);
                }

                // Phase 2: Assign actual target episode numbers
                foreach ($mapping as $item) {
                    DB::table('episodes')
                        ->where('id', $item['id'])
                        ->update(['episode_number' => $item['new_num']]);
                }
            });

            $this->info("BAŞARILI: {$totalCount} bölüm güncellendi. 0 hata.");
            Log::info("Program ID {$program->id} ({$program->name}) için {$totalCount} bölümün numaraları yeniden sıralandı.");

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("HATA: Güncelleme sırasında bir hata oluştu. İşlem GERİ ALINDI (Rollback).");
            $this->error($e->getMessage());
            Log::error("Episode Renumber Error for Program {$program->id}: {$e->getMessage()}", ['exception' => $e]);

            return Command::FAILURE;
        }
    }
}
