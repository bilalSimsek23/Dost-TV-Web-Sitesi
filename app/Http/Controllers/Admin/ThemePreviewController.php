<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ThemePreviewController extends Controller
{
    public function toggleMode(string $mode, Request $request): RedirectResponse
    {
        if (in_array($mode, ['dark', 'light', 'system'], true)) {
            session()->put('theme_preview_mode', $mode);
        }

        return redirect()->back();
    }

    public function close(Request $request): RedirectResponse
    {
        $token = session('theme_preview_token');
        if ($token) {
            Cache::forget('theme_preview_' . $token);
        }

        session()->forget([
            'theme_preview_data',
            'theme_preview_token',
            'theme_preview_mode',
        ]);

        return redirect('/');
    }
}
