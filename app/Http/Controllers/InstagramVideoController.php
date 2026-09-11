<?php

namespace App\Http\Controllers;

use App\Models\InstagramVideo;
use Illuminate\View\View;

class InstagramVideoController extends Controller
{
    /**
     * Renders the public single-stream Instagram Videos / Reels page.
     */
    public function index(): View
    {
        $videos = InstagramVideo::query()
            ->active()
            ->orderBy('posted_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(24);

        return view('site.instagram-videos.index', [
            'videos' => $videos,
        ]);
    }
}
