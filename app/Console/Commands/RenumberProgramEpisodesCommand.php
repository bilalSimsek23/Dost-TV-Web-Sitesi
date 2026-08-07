<?php

namespace App\Console\Commands;

use App\Models\Episode;
use App\Models\Program;
use App\Support\Youtube;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
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
    protected $description = 'Safely renumber program episodes chronologically by YouTube publishedAt date.';

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
        $episodes = Episode::where('program_id', $program->id)->get();
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

        // Fetch authentic YouTube publishedAt from YouTube Data API v3
        $apiKey = config('services.youtube.key') ?: env('YOUTUBE_API_KEY');
        $videoIds = [];

        foreach ($episodes as $episode) {
            $vId = Youtube::extractVideoId($episode->youtube_url);
            if ($vId) {
                $videoIds[$vId] = $episode->id;
            }
        }

        $ytPublishedDates = [];
        if (! empty($videoIds) && filled($apiKey)) {
            $chunks = array_chunk(array_keys($videoIds), 50);
            foreach ($chunks as $chunk) {
                try {
                    $response = Http::timeout(10)->get('https://www.googleapis.com/youtube/v3/videos', [
                        'part' => 'snippet',
                        'id' => implode(',', $chunk),
                        'key' => $apiKey,
                    ]);

                    if ($response->successful()) {
                        foreach ($response->json('items') ?? [] as $item) {
                            $vId = $item['id'];
                            $pubAt = $item['snippet']['publishedAt'] ?? null;
                            if ($pubAt) {
                                $ytPublishedDates[$vId] = $pubAt;
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning("YouTube publishedAt fetch error: {$e->getMessage()}");
                }
            }
        }

        // Attach YouTube publishedAt and prepare sort array
        $episodeItems = [];
        foreach ($episodes as $episode) {
            $vId = Youtube::extractVideoId($episode->youtube_url);
            $ytDate = $ytPublishedDates[$vId] ?? null;

            $sortTime = PHP_INT_MAX;
            if ($ytDate) {
                $sortTime = strtotime($ytDate);
            } elseif ($episode->aired_at) {
                $sortTime = strtotime($episode->aired_at->toDateTimeString());
            }

            $episodeItems[] = [
                'episode' => $episode,
                'yt_date_raw' => $ytDate,
                'yt_date_formatted' => $ytDate ? date('d.m.Y H:i', strtotime($ytDate)) : ($episode->aired_at ? $episode->aired_at->format('d.m.Y') : 'Yok'),
                'sort_time' => $sortTime,
            ];
        }

        // Sort chronologically by YouTube publishedAt ASC (Oldest -> Newest)
        usort($episodeItems, function ($a, $b) {
            if ($a['sort_time'] === $b['sort_time']) {
                return $a['episode']->id <=> $b['episode']->id;
            }
            return $a['sort_time'] <=> $b['sort_time'];
        });

        // Generate mapping
        $mapping = [];
        $snapshot = [];
        $newNumber = 1;

        foreach ($episodeItems as $item) {
            $ep = $item['episode'];

            $mapping[] = [
                'id' => $ep->id,
                'old_num' => $ep->episode_number,
                'new_num' => $newNumber,
                'yt_date' => $item['yt_date_formatted'],
                'yt_date_raw' => $item['yt_date_raw'],
                'title' => $ep->title,
                'youtube_url' => $ep->youtube_url,
            ];

            $snapshot[$ep->id] = [
                'old_episode_number' => $ep->episode_number,
                'new_episode_number' => $newNumber,
                'youtube_published_at' => $item['yt_date_raw'],
                'title' => $ep->title,
                'youtube_url' => $ep->youtube_url,
            ];

            $newNumber++;
        }

        // Output table
        $this->newLine();
        $this->info("==========================================================");
        $this->info("SIRA DÜZELTME PLANž (YOUTUBE PUBLISHEDAT ASC: ESKİ -> YENİ)");
        $this->info("==========================================================");

        $headers = ['Episode ID', 'Eski No', 'Yeni No', 'YouTube PublishedAt', 'Başlık', 'YouTube URL'];
        $rows = array_map(fn ($m) => [
            'Episode ID' => $m['id'],
            'Eski No' => $m['old_num'] ?? '-',
            'Yeni No' => $m['new_num'],
            'YouTube PublishedAt' => $m['yt_date'],
            'Başlık' => mb_strimwidth($m['title'], 0, 40, '...'),
            'YouTube URL' => $m['youtube_url'],
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

                // Phase 2: Assign actual target episode numbers (ONLY episode_number is updated)
                foreach ($mapping as $item) {
                    DB::table('episodes')
                        ->where('id', $item['id'])
                        ->update(['episode_number' => $item['new_num']]);
                }
            });

            $this->info("BAŞARILI: {$totalCount} bölüm güncellendi. 0 hata.");
            Log::info("Program ID {$program->id} ({$program->name}) için {$totalCount} bölümün numaraları YouTube publishedAt tarihine göre yeniden sıralandı.");

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("HATA: Güncelleme sırasında bir hata oluştu. İşlem GERİ ALINDI (Rollback).");
            $this->error($e->getMessage());
            Log::error("Episode Renumber Error for Program {$program->id}: {$e->getMessage()}", ['exception' => $e]);

            return Command::FAILURE;
        }
    }
}
