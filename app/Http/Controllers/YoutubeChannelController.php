<?php

namespace App\Http\Controllers;

use App\Services\YouTube\YoutubeCenterDataService;

class YoutubeChannelController extends Controller
{
    public function index(YoutubeCenterDataService $service)
    {
        $data = $service->getCenterData();

        return view('site.youtube-channels.index', [
            'settings' => $data['settings'],
            'channels' => $data['channels'],
            'categoryShelves' => $data['category_shelves'],
        ]);
    }
}
