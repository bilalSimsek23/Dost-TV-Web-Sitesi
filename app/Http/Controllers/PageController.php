<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\View\View;

class PageController extends Controller
{
    public function show(Page $page): View
    {
        $isPreviewActive = false;
        $previewToken = request('page_preview_token') ?? session('page_preview_token');

        if ($previewToken && auth()->check() && auth()->user()?->hasAnyRole(['super_admin', 'administrator', 'designer', 'editor'])) {
            $previewData = \Illuminate\Support\Facades\Cache::get('page_preview_' . $previewToken) ?? session('page_preview_data');
            if ($previewData && is_array($previewData)) {
                $previewPageId = $previewData['page_id'] ?? null;
                $previewSlug = $previewData['page_slug'] ?? null;

                if ($previewPageId === $page->id || $previewSlug === $page->slug) {
                    $isPreviewActive = true;
                    session()->put('page_preview_token', $previewToken);
                    session()->put('page_preview_data', $previewData);
                }
            }
        }

        return view('pages.show', [
            'page' => $page,
            'isPreviewActive' => $isPreviewActive,
        ]);
    }
}
