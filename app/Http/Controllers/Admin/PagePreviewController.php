<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PagePreviewController extends Controller
{
    public function close(string $slug, Request $request): RedirectResponse
    {
        $token = $request->query('page_preview_token') ?? session('page_preview_token');
        if ($token) {
            Cache::forget('page_preview_' . $token);
        }

        session()->forget([
            'page_preview_data',
            'page_preview_token',
        ]);

        return redirect("/{$slug}");
    }
}
