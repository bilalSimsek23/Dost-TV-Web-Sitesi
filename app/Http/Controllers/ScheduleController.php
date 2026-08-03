<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Services\Schedule\BroadcastScheduleResolver;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    public function index(BroadcastScheduleResolver $resolver): View
    {
        $scheduleByDay = $resolver->getWeeklySchedule();
        $activeTemplate = $resolver->getActivePublishedTemplateForDate(now());

        return view('schedule.index', [
            'scheduleByDay' => $scheduleByDay,
            'days' => Schedule::DAYS,
            'activeTemplate' => $activeTemplate,
        ]);
    }
}
