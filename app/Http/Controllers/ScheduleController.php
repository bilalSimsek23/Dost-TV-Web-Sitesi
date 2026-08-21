<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Services\Schedule\BroadcastScheduleResolver;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    public function index(BroadcastScheduleResolver $resolver): View
    {
        $now = now();
        $scheduleByDay = $resolver->getWeeklySchedule($now);
        $activeTemplate = $resolver->getActivePublishedTemplateForDate($now);

        $startOfWeek = $now->copy()->startOfWeek(); // Pazartesi
        $todayIso = (int) $now->dayOfWeekIso - 1; // 0 = Pazartesi, 6 = Pazar
        if ($todayIso < 0 || $todayIso > 6) {
            $todayIso = 0;
        }

        $currentTime = $now->format('H:i');

        $turkishMonths = [
            1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan',
            5 => 'Mayıs', 6 => 'Haziran', 7 => 'Temmuz', 8 => 'Ağustos',
            9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık',
        ];

        $daysData = [];
        for ($i = 0; $i < 7; $i++) {
            $dayDate = $startOfWeek->copy()->addDays($i);
            $dayName = Schedule::DAYS[$i] ?? match ($i) {
                0 => 'Pazartesi', 1 => 'Salı', 2 => 'Çarşamba',
                3 => 'Perşembe', 4 => 'Cuma', 5 => 'Cumartesi', 6 => 'Pazar'
            };
            $dateLabel = $dayDate->day . ' ' . ($turkishMonths[$dayDate->month] ?? '');
            $isToday = ($i === $todayIso);

            $broadcasts = $scheduleByDay->get($i, collect());

            // Process now-playing & next-upcoming for today
            $nowPlayingIndex = null;
            $nextUpcomingIndex = null;

            if ($isToday && $broadcasts->isNotEmpty()) {
                foreach ($broadcasts as $bIndex => $item) {
                    $startTime = Carbon::parse($item->start_time)->format('H:i');
                    $endTime = $item->end_time ? Carbon::parse($item->end_time)->format('H:i') : null;

                    // Check if currently playing
                    $isCurrentlyPlaying = false;
                    if ($endTime === null || $endTime === '00:00') {
                        // Ends at end of day / next day
                        $isCurrentlyPlaying = ($currentTime >= $startTime);
                    } elseif ($endTime > $startTime) {
                        $isCurrentlyPlaying = ($currentTime >= $startTime && $currentTime < $endTime);
                    } else {
                        // Overnight broadcast (e.g. 23:00 - 02:00)
                        $isCurrentlyPlaying = ($currentTime >= $startTime || $currentTime < $endTime);
                    }

                    if ($isCurrentlyPlaying) {
                        $nowPlayingIndex = $bIndex;
                        break;
                    }

                    if ($startTime > $currentTime && $nextUpcomingIndex === null) {
                        $nextUpcomingIndex = $bIndex;
                    }
                }
            }

            $daysData[$i] = [
                'index' => $i,
                'day_name' => $dayName,
                'date_label' => $dateLabel,
                'full_date' => $dayDate->format('Y-m-d'),
                'is_today' => $isToday,
                'broadcasts' => $broadcasts,
                'now_playing_index' => $nowPlayingIndex,
                'next_upcoming_index' => $nextUpcomingIndex,
            ];
        }

        $defaultSelectedDay = $todayIso;

        return view('schedule.index', [
            'daysData' => $daysData,
            'defaultSelectedDay' => $defaultSelectedDay,
            'todayIndex' => $todayIso,
            'activeTemplate' => $activeTemplate,
            'scheduleByDay' => $scheduleByDay,
            'days' => Schedule::DAYS,
        ]);
    }
}

