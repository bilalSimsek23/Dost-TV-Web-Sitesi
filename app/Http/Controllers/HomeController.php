<?php

namespace App\Http\Controllers;

use App\Services\Home\HomepageDataService;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(HomepageDataService $dataService): View
    {
        $data = $dataService->getHomepageData();

        return view('home', [
            'settings' => $data['settings'],
            'homepageSections' => $data['sections'],
            'banners' => $data['banners'],
            'featuredPrograms' => $data['featuredPrograms'],
            'todaySchedule' => $data['todaySchedule'],
        ]);
    }
}
