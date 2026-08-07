<?php

namespace App\Console\Commands;

use App\Models\Episode;
use App\Models\Program;
use App\Models\Schedule;
use App\Models\ScheduleExceptionItem;
use App\Models\ScheduleTemplateItem;
use App\Models\YoutubeSyncLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class DeduplicateProgramsCommand extends Command
{
    protected $signature = 'programs:deduplicate {--dry-run : Run in simulation mode without modifying database}';

    protected $description = 'Find and safely merge duplicate program records while transferring all relations and preserving data integrity.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->info('==========================================================');
        $this->info($dryRun ? 'DUPLICATE PROGRAM TEMİZLEME (SIMULATION MODE --dry-run)' : 'DUPLICATE PROGRAM TEMİZLEME (GERÇEK İŞLEM)');
        $this->info('==========================================================');

        $allPrograms = Program::all();
        $grouped = [];

        foreach ($allPrograms as $program) {
            $key = $this->normalizeName($program->name);
            $grouped[$key][] = $program;
        }

        // Filter groups with > 1 program
        $duplicateGroups = array_filter($grouped, fn ($list) => count($list) > 1);

        if (empty($duplicateGroups)) {
            $this->info('Tekrar eden (duplicate) program kaydı bulunamadı. Veritabanı temiz!');
            return Command::SUCCESS;
        }

        $this->warn('Toplam ' . count($duplicateGroups) . ' duplicate program grubu tespit edildi.');

        $snapshotData = [
            'timestamp' => now()->toIso8601String(),
            'dry_run' => $dryRun,
            'total_duplicate_groups' => count($duplicateGroups),
            'groups' => [],
        ];

        $totalDeletedCount = 0;
        $totalEpisodesMoved = 0;
        $totalSchedulesMoved = 0;
        $totalTemplatesMoved = 0;
        $totalExceptionsMoved = 0;
        $totalPivotsMoved = 0;
        $slugUpdates = [];

        foreach ($duplicateGroups as $normName => $programs) {
            // Collect relation stats for each program in group
            $stats = [];
            foreach ($programs as $prog) {
                $epCount = Episode::where('program_id', $prog->id)->count();
                $templateCount = ScheduleTemplateItem::where('program_id', $prog->id)->count();
                $exceptionCount = ScheduleExceptionItem::where('program_id', $prog->id)->count();
                $schCount = Schedule::where('program_id', $prog->id)->count();
                $catCount = DB::table('category_program')->where('program_id', $prog->id)->count();
                $ytCount = YoutubeSyncLog::where('program_id', $prog->id)->count();

                $stats[] = [
                    'program' => $prog,
                    'episode_count' => $epCount,
                    'template_count' => $templateCount,
                    'exception_count' => $exceptionCount,
                    'schedule_count' => $schCount,
                    'category_count' => $catCount,
                    'youtube_log_count' => $ytCount,
                    'total_relations' => $epCount + $templateCount + $exceptionCount + $schCount + $catCount + $ytCount,
                ];
            }

            // Sort using canonical selection rule:
            // 1. Most Episode count DESC
            // 2. Most ScheduleTemplateItem count DESC
            // 3. Most Schedule & Exception count DESC
            // 4. Most Category count DESC
            // 5. Oldest ID ASC
            usort($stats, function ($a, $b) {
                if ($a['episode_count'] !== $b['episode_count']) {
                    return $b['episode_count'] <=> $a['episode_count'];
                }
                if ($a['template_count'] !== $b['template_count']) {
                    return $b['template_count'] <=> $a['template_count'];
                }
                $otherA = $a['schedule_count'] + $a['exception_count'];
                $otherB = $b['schedule_count'] + $b['exception_count'];
                if ($otherA !== $otherB) {
                    return $otherB <=> $otherA;
                }
                if ($a['category_count'] !== $b['category_count']) {
                    return $b['category_count'] <=> $a['category_count'];
                }
                return $a['program']->id <=> $b['program']->id;
            });

            $mainStat = $stats[0];
            /** @var Program $mainProg */
            $mainProg = $mainStat['program'];
            $duplicates = array_slice($stats, 1);

            $this->newLine();
            $this->info("----------------------------------------------------------");
            $this->info("Grup: \"{$mainProg->name}\" (" . count($programs) . " Kayıt)");
            $this->info("ASIL KAYIT : ID {$mainProg->id} | Slug: {$mainProg->slug} | Bölüm: {$mainStat['episode_count']} | Şablon: {$mainStat['template_count']} | Kategori: {$mainStat['category_count']}");

            $groupSnapshot = [
                'name' => $mainProg->name,
                'main_program_id' => $mainProg->id,
                'main_slug' => $mainProg->slug,
                'duplicates_to_delete' => [],
            ];

            // Candidate clean slug if main slug is awkward (e.g. bab-i-reyyan-2)
            $possibleCleanSlugs = [];
            if (! Str::endsWith($mainProg->slug, ['-1', '-2', '-3', '-4', '-5', '-6', '-7', '-8', '-9', '-0'])) {
                $possibleCleanSlugs[] = $mainProg->slug;
            }

            foreach ($duplicates as $dupStat) {
                /** @var Program $dupProg */
                $dupProg = $dupStat['program'];
                $this->warn("SILINECEK : ID {$dupProg->id} | Slug: {$dupProg->slug} | Bölüm: {$dupStat['episode_count']} | Şablon: {$dupStat['template_count']} | Kategori: {$dupStat['category_count']}");

                if (! Str::contains($dupProg->slug, ['-1', '-2', '-3', '-4', '-5', '-6', '-7', '-8', '-9', '-0'])) {
                    $possibleCleanSlugs[] = $dupProg->slug;
                }

                $groupSnapshot['duplicates_to_delete'][] = [
                    'id' => $dupProg->id,
                    'slug' => $dupProg->slug,
                    'episodes' => $dupStat['episode_count'],
                    'templates' => $dupStat['template_count'],
                    'exceptions' => $dupStat['exception_count'],
                    'schedules' => $dupStat['schedule_count'],
                    'categories' => $dupStat['category_count'],
                ];

                $totalDeletedCount++;
                $totalEpisodesMoved += $dupStat['episode_count'];
                $totalTemplatesMoved += $dupStat['template_count'];
                $totalExceptionsMoved += $dupStat['exception_count'];
                $totalSchedulesMoved += $dupStat['schedule_count'];
            }

            // Determine if a cleaner slug exists for main program
            $cleanSlug = Str::slug($mainProg->name);
            if (! in_array($cleanSlug, $possibleCleanSlugs, true) && ! empty($possibleCleanSlugs)) {
                $cleanSlug = $possibleCleanSlugs[0];
            }

            $groupSnapshot['target_slug'] = $cleanSlug;
            $snapshotData['groups'][] = $groupSnapshot;
        }

        $this->newLine();
        $this->info('==========================================================');
        $this->info('DEDUPLICATION RAPORU');
        $this->info('==========================================================');
        $this->info("Toplam Duplicate Grup     : " . count($duplicateGroups));
        $this->info("Silinecek Program Kaydı  : " . $totalDeletedCount);
        $this->info("Taşınacak Episode        : " . $totalEpisodesMoved);
        $this->info("Taşınacak Şablon İletimi : " . $totalTemplatesMoved);
        $this->info("Taşınacak Özel Yayın Kaydı: " . $totalExceptionsMoved);
        $this->info("Taşınacak Takvim Yayın   : " . $totalSchedulesMoved);
        $this->info('==========================================================');

        if ($dryRun) {
            $this->warn('SIMULATION MODE (--dry-run). Veritabanında değişiklik yapılmadı.');
            return Command::SUCCESS;
        }

        // Write Snapshot
        $logDir = storage_path('logs');
        if (! File::exists($logDir)) {
            File::makeDirectory($logDir, 0755, true);
        }
        $snapshotPath = $logDir . '/program_deduplicate_snapshot_' . now()->format('Ymd_His') . '.json';
        File::put($snapshotPath, json_encode($snapshotData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $this->info("Yedek snapshot kaydedildi: {$snapshotPath}");

        // Perform actual database updates inside transaction
        DB::transaction(function () use ($duplicateGroups) {
            foreach ($duplicateGroups as $normName => $programs) {
                $stats = [];
                foreach ($programs as $prog) {
                    $epCount = Episode::where('program_id', $prog->id)->count();
                    $templateCount = ScheduleTemplateItem::where('program_id', $prog->id)->count();
                    $exceptionCount = ScheduleExceptionItem::where('program_id', $prog->id)->count();
                    $schCount = Schedule::where('program_id', $prog->id)->count();
                    $catCount = DB::table('category_program')->where('program_id', $prog->id)->count();

                    $stats[] = [
                        'program' => $prog,
                        'episode_count' => $epCount,
                        'template_count' => $templateCount,
                        'exception_count' => $exceptionCount,
                        'schedule_count' => $schCount,
                        'category_count' => $catCount,
                    ];
                }

                usort($stats, function ($a, $b) {
                    if ($a['episode_count'] !== $b['episode_count']) {
                        return $b['episode_count'] <=> $a['episode_count'];
                    }
                    if ($a['template_count'] !== $b['template_count']) {
                        return $b['template_count'] <=> $a['template_count'];
                    }
                    $otherA = $a['schedule_count'] + $a['exception_count'];
                    $otherB = $b['schedule_count'] + $b['exception_count'];
                    if ($otherA !== $otherB) {
                        return $otherB <=> $otherA;
                    }
                    if ($a['category_count'] !== $b['category_count']) {
                        return $b['category_count'] <=> $a['category_count'];
                    }
                    return $a['program']->id <=> $b['program']->id;
                });

                /** @var Program $mainProg */
                $mainProg = $stats[0]['program'];
                $duplicates = array_slice($stats, 1);

                $existingCategories = DB::table('category_program')
                    ->where('program_id', $mainProg->id)
                    ->pluck('category_id')
                    ->toArray();

                foreach ($duplicates as $dupStat) {
                    /** @var Program $dupProg */
                    $dupProg = $dupStat['program'];

                    // 1. Move Episodes
                    Episode::where('program_id', $dupProg->id)->update(['program_id' => $mainProg->id]);

                    // 2. Move ScheduleTemplateItems
                    ScheduleTemplateItem::where('program_id', $dupProg->id)->update(['program_id' => $mainProg->id]);

                    // 3. Move ScheduleExceptionItems
                    ScheduleExceptionItem::where('program_id', $dupProg->id)->update(['program_id' => $mainProg->id]);

                    // 4. Move Schedules
                    Schedule::where('program_id', $dupProg->id)->update(['program_id' => $mainProg->id]);

                    // 5. Move YoutubeSyncLogs
                    YoutubeSyncLog::where('program_id', $dupProg->id)->update(['program_id' => $mainProg->id]);

                    // 6. Merge Categories Pivot
                    $dupCategories = DB::table('category_program')
                        ->where('program_id', $dupProg->id)
                        ->pluck('category_id')
                        ->toArray();

                    foreach ($dupCategories as $catId) {
                        if (! in_array($catId, $existingCategories, true)) {
                            DB::table('category_program')->insert([
                                'program_id' => $mainProg->id,
                                'category_id' => $catId,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                            $existingCategories[] = $catId;
                        }
                    }
                    DB::table('category_program')->where('program_id', $dupProg->id)->delete();

                    // 7. Delete duplicate program record safely
                    $dupProg->delete();
                }

                // 8. Fix main program slug if cleaner slug available
                $cleanSlug = Str::slug($mainProg->name);
                if ($mainProg->slug !== $cleanSlug) {
                    $slugTaken = Program::where('slug', $cleanSlug)->where('id', '!=', $mainProg->id)->exists();
                    if (! $slugTaken) {
                        $mainProg->update(['slug' => $cleanSlug]);
                    }
                }
            }
        });

        $this->info('Duplicate program temizleme işlemi başarıyla tamamlandı!');
        return Command::SUCCESS;
    }

    protected function normalizeName(string $name): string
    {
        $name = trim($name);
        $name = mb_strtolower($name, 'UTF-8');
        return preg_replace('/\s+/', ' ', $name);
    }
}
