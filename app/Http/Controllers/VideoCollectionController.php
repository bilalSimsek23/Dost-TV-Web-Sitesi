<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Program;
use App\Models\VideoCollection;
use Illuminate\View\View;

class VideoCollectionController extends Controller
{
    public function show(VideoCollection $collection): View
    {
        if (! $collection->is_active) {
            abort(404);
        }

        $settings = $collection->resolvePublicSettings();
        $baseQuery = $collection->resolveEpisodesQuery();

        // Query categories and programs attached to episodes of this collection
        $categories = Category::query()
            ->whereHas('programs.episodes', fn ($q) => $q->whereIn('episodes.id', (clone $baseQuery)->select('episodes.id')))
            ->orderBy('name')
            ->get();

        $programs = Program::query()
            ->whereHas('episodes', fn ($q) => $q->whereIn('episodes.id', (clone $baseQuery)->select('episodes.id')))
            ->orderBy('name')
            ->get();

        $totalEpisodesCount = (clone $baseQuery)->count();

        foreach ($categories as $cat) {
            $cat->filtered_count = (clone $baseQuery)
                ->whereHas('program.categories', fn ($q) => $q->where('categories.id', $cat->id))
                ->count();
        }

        foreach ($programs as $prog) {
            $prog->filtered_count = (clone $baseQuery)
                ->where('program_id', $prog->id)
                ->count();
        }

        // Apply active category or program filter
        $filteredQuery = clone $baseQuery;
        $selectedCatSlug = request()->query('category');
        $selectedProgSlug = request()->query('program');

        if ($selectedCatSlug) {
            $filteredQuery->whereHas('program.categories', fn ($q) => $q->where('categories.slug', $selectedCatSlug));
        } elseif ($selectedProgSlug) {
            $filteredQuery->whereHas('program', fn ($q) => $q->where('programs.slug', $selectedProgSlug));
        }

        // Resolve page size (default 24)
        $pageSizeSetting = $settings['page_size'] ?? 24;
        $pageSize = (is_numeric($pageSizeSetting) && (int) $pageSizeSetting > 0) ? (int) $pageSizeSetting : 24;

        // Perform real server-side DB pagination
        $episodes = $filteredQuery->paginate($pageSize)->withQueryString();

        // --- Visual Builder Layout Resolution ---
        try {
            $layout = null;
            $previewLayoutId = request()->query('preview_layout_id');
            $isPreview = filled($previewLayoutId) && auth()->check();

            if ($isPreview) {
                $layout = \App\Models\HomepageLayout::query()
                    ->where('id', $previewLayoutId)
                    ->where('page_type', 'video_collection')
                    ->first();
            }

            if (! $layout) {
                $layout = \App\Models\HomepageLayout::query()
                    ->where('page_type', 'video_collection')
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
                    return view('video-collections.show_builder', [
                        'collection' => $collection,
                        'episodes' => $episodes,
                        'totalCount' => $totalEpisodesCount,
                        'settings' => $settings,
                        'categories' => $categories,
                        'programs' => $programs,
                        'selectedCategory' => $selectedCatSlug,
                        'selectedProgram' => $selectedProgSlug,
                        'sections' => $sections,
                        'preview' => $isPreview,
                    ]);
                }
            }
        } catch (\Throwable $e) {
            // Graceful fallback to legacy view
        }

        return view('video-collections.show', [
            'collection' => $collection,
            'episodes' => $episodes,
            'totalCount' => $totalEpisodesCount,
            'settings' => $settings,
            'categories' => $categories,
            'programs' => $programs,
            'selectedCategory' => $selectedCatSlug,
            'selectedProgram' => $selectedProgSlug,
        ]);
    }
}

