<?php

namespace App\Http\Controllers;

use App\Models\ProgramCollection;
use Illuminate\View\View;

class ProgramCollectionController extends Controller
{
    public function show(string $slug): View
    {
        $collection = ProgramCollection::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $settings = $collection->resolvePublicSettings();
        $allPrograms = $collection->resolvePrograms();
        $pageSize = $settings['page_size'] ?? 'all';

        if (is_numeric($pageSize) && (int) $pageSize > 0 && $allPrograms->count() > (int) $pageSize) {
            $page = (int) request()->query('page', 1);
            $perPage = (int) $pageSize;
            $items = $allPrograms->forPage($page, $perPage)->values();
            $programs = new \Illuminate\Pagination\LengthAwarePaginator(
                $items,
                $allPrograms->count(),
                $perPage,
                $page,
                ['path' => request()->url(), 'query' => request()->query()]
            );
        } else {
            $programs = $allPrograms;
        }

        // --- Visual Builder Layout Resolution ---
        try {
            $layout = null;
            $previewLayoutId = request()->query('preview_layout_id');
            $isPreview = filled($previewLayoutId) && auth()->check();

            if ($isPreview) {
                $layout = \App\Models\HomepageLayout::query()
                    ->where('id', $previewLayoutId)
                    ->where('page_type', 'program_collection')
                    ->first();
            }

            if (! $layout) {
                $layout = \App\Models\HomepageLayout::query()
                    ->where('page_type', 'program_collection')
                    ->where('target_id', $collection->id)
                    ->where('is_active', true)
                    ->first();
            }

            if ($layout) {
                $rawSections = $isPreview
                    ? ($layout->draft_sections ?? [])
                    : ($layout->published_sections ?? []);

                unset($rawSections['_fixed_settings']);
                $sections = array_values(array_filter($rawSections, fn ($s) => is_array($s) && ! empty($s['visible'] ?? true)));

                if ($isPreview || (! empty($sections) && $layout->is_active)) {
                    return view('program-collections.show_builder', [
                        'collection' => $collection,
                        'programs' => $programs,
                        'settings' => $settings,
                        'sections' => $sections,
                        'preview' => $isPreview,
                    ]);
                }
            }
        } catch (\Throwable $e) {
            // Graceful fallback to legacy view
        }

        return view('program-collections.show', [
            'collection' => $collection,
            'programs' => $programs,
            'settings' => $settings,
        ]);
    }
}

