<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CollectionPreviewController extends Controller
{
    public function closeVideoCollection(string $slug, Request $request): RedirectResponse
    {
        $token = $request->query('collection_preview_token') ?? session('collection_preview_token');
        if ($token) {
            Cache::forget('collection_preview_' . $token);
        }

        session()->forget([
            'collection_preview_data',
            'collection_preview_token',
        ]);

        return redirect("/koleksiyonlar/{$slug}");
    }

    public function closeProgramCollection(string $slug, Request $request): RedirectResponse
    {
        $token = $request->query('collection_preview_token') ?? session('collection_preview_token');
        if ($token) {
            Cache::forget('collection_preview_' . $token);
        }

        session()->forget([
            'collection_preview_data',
            'collection_preview_token',
        ]);

        return redirect("/program-koleksiyonlari/{$slug}");
    }
}
