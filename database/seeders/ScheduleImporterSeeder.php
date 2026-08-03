<?php

namespace Database\Seeders;

use App\Models\Program;
use App\Models\ScheduleTemplate;
use App\Models\ScheduleTemplateItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ScheduleImporterSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ensure "Genel Yayın Akışı 2026" template exists and is published
        $template = ScheduleTemplate::query()->firstOrCreate(
            ['slug' => 'genel-yayin-akisi-2026'],
            [
                'name' => 'Genel Yayın Akışı 2026',
                'description' => 'Dost TV genel haftalık televizyon yayın akış planı.',
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

        // 2. Sample default weekly broadcast grid data if missing
        $programMap = Program::pluck('id', 'name')->toArray();
        $mektubatId = $programMap['Mektubat'] ?? Program::first()?->id;

        if ($mektubatId && $template->items()->count() === 0) {
            $times = [
                ['07:00', '08:30'],
                ['08:30', '10:00'],
                ['10:00', '11:30'],
                ['11:30', '13:00'],
                ['13:00', '14:30'],
                ['14:30', '16:00'],
                ['16:00', '17:30'],
                ['17:30', '19:00'],
                ['19:00', '20:30'],
                ['20:30', '22:00'],
                ['22:00', '23:30'],
            ];

            foreach (range(0, 6) as $day) {
                foreach ($times as $t) {
                    ScheduleTemplateItem::query()->firstOrCreate([
                        'schedule_template_id' => $template->id,
                        'day_of_week' => $day,
                        'start_time' => $t[0],
                    ], [
                        'program_id' => $mektubatId,
                        'end_time' => $t[1],
                        'is_live' => false,
                        'is_repeat' => true,
                        'is_active' => true,
                    ]);
                }
            }
        }
    }
}
