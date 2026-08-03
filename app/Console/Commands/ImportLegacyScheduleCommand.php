<?php

namespace App\Console\Commands;

use App\Models\Program;
use App\Models\Schedule;
use App\Models\ScheduleTemplate;
use App\Models\ScheduleTemplateItem;
use Illuminate\Console\Command;

class ImportLegacyScheduleCommand extends Command
{
    protected $signature = 'schedule:import-legacy';

    protected $description = 'Import legacy schedule data into ScheduleTemplate and ScheduleTemplateItem idempotently';

    public function handle(): int
    {
        $this->info('Starting legacy broadcast schedule import...');

        // 1. Find or create default "Genel Yayın Akışı 2026" template
        $template = ScheduleTemplate::query()->firstOrCreate(
            ['slug' => 'genel-yayin-akisi-2026'],
            [
                'name' => 'Genel Yayın Akışı 2026',
                'description' => 'Dost TV genel haftalık yayın akışı.',
                'status' => 'published',
                'priority' => 10,
                'version' => 1,
                'is_active' => true,
            ]
        );

        if ($template->status !== 'published' || ! $template->is_active) {
            $template->update([
                'status' => 'published',
                'is_active' => true,
            ]);
        }

        $foundCount = 0;
        $addedCount = 0;
        $skippedDuplicateCount = 0;
        $unmatchedProgramCount = 0;

        // 2. Check legacy App\Models\Schedule records if any
        $legacySchedules = Schedule::query()->get();
        if ($legacySchedules->isNotEmpty()) {
            foreach ($legacySchedules as $legacy) {
                $foundCount++;

                $program = Program::find($legacy->program_id);
                if (! $program) {
                    $unmatchedProgramCount++;
                    continue;
                }

                $existing = ScheduleTemplateItem::where('schedule_template_id', $template->id)
                    ->where('day_of_week', $legacy->day_of_week)
                    ->where('start_time', $legacy->start_time)
                    ->first();

                if ($existing) {
                    $skippedDuplicateCount++;
                } else {
                    ScheduleTemplateItem::create([
                        'schedule_template_id' => $template->id,
                        'program_id' => $program->id,
                        'day_of_week' => (int) $legacy->day_of_week,
                        'start_time' => $legacy->start_time,
                        'end_time' => $legacy->end_time,
                        'is_live' => (bool) $legacy->is_live,
                        'is_repeat' => (bool) $legacy->is_repeat,
                        'is_active' => (bool) $legacy->is_active,
                        'custom_title' => $legacy->custom_title,
                        'note' => $legacy->note,
                    ]);
                    $addedCount++;
                }
            }
        }

        // 3. Check current schedule_template_items grid
        $existingItems = ScheduleTemplateItem::where('schedule_template_id', $template->id)->get();
        $foundCount += $existingItems->count();
        $skippedDuplicateCount += $existingItems->count();

        // Check if any items lack valid program
        foreach ($existingItems as $item) {
            if (! $item->program_id || ! Program::where('id', $item->program_id)->exists()) {
                $unmatchedProgramCount++;
            }
        }

        $totalItems = ScheduleTemplateItem::where('schedule_template_id', $template->id)->count();

        $this->table(
            ['Metrik', 'Değer'],
            [
                ['Template ID', $template->id],
                ['Template Adı', $template->name],
                ['Template Status', $template->status],
                ['Bulunan Kayıt', $foundCount],
                ['Eklenen Yeni Kayıt', $addedCount],
                ['Atlanan Duplicate Kayıt', $skippedDuplicateCount],
                ['Eşleşmeyen Program', $unmatchedProgramCount],
                ['Toplam ScheduleTemplateItem Sayısı', $totalItems],
            ]
        );

        $this->info('Legacy schedule import completed successfully.');

        return Command::SUCCESS;
    }
}
