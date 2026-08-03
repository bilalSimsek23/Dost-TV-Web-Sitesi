<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Program;
use App\Models\SiteSetting;
use App\Services\Schedule\BroadcastScheduleResolver;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(BroadcastScheduleResolver $resolver): View
    {
        $banners = Banner::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $featuredPrograms = Program::query()
            ->with('categories')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->limit(8)
            ->get();

        $todaySchedule = $resolver->getScheduleForDate(now());

        return view('home', [
            'settings' => SiteSetting::current(),
            'banners' => $banners,
            'featuredPrograms' => $featuredPrograms,
            'todaySchedule' => $todaySchedule,
        ]);
    }
}
