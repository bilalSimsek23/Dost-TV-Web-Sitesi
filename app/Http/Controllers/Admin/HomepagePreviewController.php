<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomepageLayout;
use App\Services\Home\HomepageDataService;
use Illuminate\Http\Request;

class HomepagePreviewController extends Controller
{
    /**
     * Renders a real-time live preview of the draft sections of a HomepageLayout
     * inside an authenticated iframe for the Filament Editor Shell.
     */
    public function frame(Request $request, HomepageLayout $homepageLayout)
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->to('/admin/login');
        }

        if (! $user->hasAnyRole(['super_admin', 'administrator', 'designer', 'editor'])) {
            abort(403, 'Bu önizleme alanına erişim yetkiniz bulunmamaktadır.');
        }

        $draftSections = $homepageLayout->draft_sections ?? [];

        $dataService = app(HomepageDataService::class);
        $homepageData = $dataService->getHomepageData($draftSections);

        return view('filament.pages.site-layout.editor-preview-iframe', [
            'layout' => $homepageLayout,
            'homepageSections' => $draftSections,
            'settings' => $homepageData['settings'],
            'banners' => $homepageData['banners'],
            'featuredPrograms' => $homepageData['featuredPrograms'],
            'heroPrograms' => $homepageData['heroPrograms'],
            'todaySchedule' => $homepageData['todaySchedule'],
            'resolvedBlockData' => $homepageData['resolvedBlockData'] ?? [],
            'fixedSettings' => $homepageData['fixedSettings'] ?? [],
            'preview' => true,
            'editorMode' => true,
        ]);
    }
}
