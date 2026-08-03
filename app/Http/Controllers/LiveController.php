<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use Illuminate\View\View;

class LiveController extends Controller
{
    public function tv(): View
    {
        return view('live.tv', [
            'settings' => SiteSetting::current(),
        ]);
    }

    public function radio(): View
    {
        return view('live.radio', [
            'settings' => SiteSetting::current(),
        ]);
    }
}
