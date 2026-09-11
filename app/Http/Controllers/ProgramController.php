<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProgramController extends Controller
{
    public function index(Request $request): View
    {
        $categorySlug = $request->string('kategori')->toString();

        $activeCategoryModel = blank($categorySlug) ? null : Category::query()
            ->where('slug', $categorySlug)
            ->where('is_active', true)
            ->first();

        $validCategorySlug = $activeCategoryModel ? $activeCategoryModel->slug : '';

        $programs = Program::query()
            ->with('categories')
            ->where('is_active', true)
            ->when(! empty($validCategorySlug), function ($query) use ($validCategorySlug) {
                $query->whereHas('categories', fn ($q) => $q->where('slug', $validCategorySlug)->where('is_active', true));
            })
            ->orderBy('sort_order')
            ->paginate(12)
            ->withQueryString();

        $categories = Category::query()
            ->where('is_active', true)
            ->where('slug', '!=', Category::ALL_CATEGORIES_SLUG)
            ->orderBy('sort_order')
            ->get();

        return view('programs.index', [
            'programs' => $programs,
            'categories' => $categories,
            'activeCategory' => $validCategorySlug,
        ]);
    }

    public function show(Request $request, Program $program): View
    {
        // Guard: Only allow public, non-archived programs to be viewed publicly
        $isPublic = (bool) $program->show_on_public && $program->status !== 'archived';

        if (! $isPublic) {
            $canPreview = auth()->check() && (
                in_array(auth()->user()->role, ['super_admin', 'administrator', 'editor', 'content_manager', 'designer'], true)
                && (bool) auth()->user()->is_active
                && ! auth()->user()->trashed()
            );

            if (! $canPreview) {
                abort(404);
            }
        }

        $program->load([
            'categories',
            'schedules' => fn ($query) => $query->orderBy('day_of_week')->orderBy('start_time'),
        ]);

        $hasSeries = $program->programSeries()->exists() || $program->episodes()->whereNotNull('program_series_id')->exists();

        $seasonItems = collect();
        $selectedItem = null;
        $episodes = collect();

        $requestedSeries = $request->query('series', $request->query('seri'));
        $requestedSeason = $request->query('season', $request->query('sezon'));
        $requestedYear = $request->query('year', $request->query('yil'));
        $requestedEpisodeId = $request->query('episode', $request->query('bolum', $request->query('v')));

        $targetEpisode = null;
        if (filled($requestedEpisodeId)) {
            $targetEpisode = $program->episodes()
                ->where('id', $requestedEpisodeId)
                ->where('is_active', true)
                ->where('show_on_public', true)
                ->where(function ($q) {
                    $q->whereNull('status')->orWhere('status', 'published');
                })
                ->first();

            if ($targetEpisode) {
                if (filled($targetEpisode->program_series_id)) {
                    $requestedSeries = $targetEpisode->program_series_id;
                }
                if (filled($targetEpisode->season_number)) {
                    $requestedSeason = $targetEpisode->season_number;
                }
                if (filled($targetEpisode->season_year)) {
                    $requestedYear = $targetEpisode->season_year;
                }
            }
        }

        if ($hasSeries) {
            // Series-based program (e.g. Beraber Okuyalım)
            $seriesList = $program->programSeries()
                ->with('programSeason')
                ->leftJoin('program_seasons', 'program_series.program_season_id', '=', 'program_seasons.id')
                ->select(
                    'program_series.*',
                    'program_seasons.season_number as season_number',
                    'program_seasons.season_year as season_year'
                )
                ->withCount(['episodes' => function ($q) {
                    $q->where('is_active', true)->where('show_on_public', true);
                }])
                ->orderByDesc('program_seasons.season_number')
                ->orderBy('program_series.sort_order')
                ->orderBy('program_series.id')
                ->get();

            foreach ($seriesList as $series) {
                if (filled($series->name)) {
                    $label = (string) $series->name;
                } elseif (filled($series->season_year)) {
                    $label = (string) $series->season_year;
                } elseif (filled($series->season_number)) {
                    $label = 'Sezon ' . $series->season_number;
                } else {
                    $label = 'Genel';
                }

                $seasonItems->push((object) [
                    'key' => 'series_' . $series->id,
                    'type' => 'series',
                    'id' => $series->id,
                    'slug' => $series->slug,
                    'name' => $series->name,
                    'season_number' => $series->season_number,
                    'season_year' => $series->season_year,
                    'label' => $label,
                    'total_episodes' => (int) $series->episodes_count,
                    'url' => route('programs.show', [
                        'program' => $program,
                        'seri' => $series->slug,
                    ]),
                ]);
            }

            $unassignedEpisodes = $program->episodes()
                ->whereNull('program_series_id')
                ->where('is_active', true)
                ->where('show_on_public', true)
                ->get(['season_number', 'season_year']);

            $unassignedSeasons = $unassignedEpisodes
                ->filter(fn ($e) => filled($e->season_number) || filled($e->season_year))
                ->groupBy(fn ($e) => $e->season_number . '|' . $e->season_year);

            foreach ($unassignedSeasons as $group) {
                $seasonNumber = $group->first()->season_number;
                $seasonYear = $group->first()->season_year;

                $seasonRecord = \App\Models\ProgramSeason::findSeason($program->id, $seasonNumber, $seasonYear);
                $label = $seasonRecord?->public_label
                    ?? (filled($seasonYear) ? (string) $seasonYear : (filled($seasonNumber) ? 'Sezon ' . $seasonNumber : 'Genel'));

                $seasonItems->push((object) [
                    'key' => 'season_' . $seasonNumber . '_' . ($seasonYear ?? ''),
                    'type' => 'season',
                    'id' => null,
                    'slug' => null,
                    'name' => null,
                    'season_number' => $seasonNumber,
                    'season_year' => $seasonYear,
                    'label' => $label,
                    'total_episodes' => $group->count(),
                    'url' => route('programs.show', array_filter([
                        'program' => $program,
                        'season' => $seasonNumber,
                        'year' => $seasonYear,
                    ])),
                ]);
            }

            $bareCount = $unassignedEpisodes
                ->filter(fn ($e) => blank($e->season_number) && blank($e->season_year))
                ->count();

            if ($bareCount > 0) {
                $seasonItems->push((object) [
                    'key' => 'unassigned',
                    'type' => 'unassigned',
                    'id' => null,
                    'slug' => 'diger-bolumler',
                    'name' => 'Diğer Bölümler',
                    'season_number' => null,
                    'season_year' => null,
                    'label' => 'Diğer Bölümler',
                    'total_episodes' => $bareCount,
                    'url' => route('programs.show', [
                        'program' => $program,
                        'seri' => 'diger-bolumler',
                    ]),
                ]);
            }

            if ($seasonItems->isNotEmpty()) {
                if (filled($requestedSeries)) {
                    $selectedItem = $seasonItems->first(function ($item) use ($requestedSeries) {
                        return (string) $item->id === (string) $requestedSeries
                            || $item->slug === (string) $requestedSeries
                            || mb_strtolower((string) $item->name) === mb_strtolower((string) $requestedSeries)
                            || mb_strtolower((string) $item->label) === mb_strtolower((string) $requestedSeries);
                    });
                }

                if (! $selectedItem && filled($requestedSeason)) {
                    $selectedItem = $seasonItems->first(function ($item) use ($requestedSeason, $requestedYear) {
                        if (filled($requestedYear)) {
                            return (string) $item->season_number === (string) $requestedSeason && (string) $item->season_year === (string) $requestedYear;
                        }
                        return (string) $item->season_number === (string) $requestedSeason;
                    });
                }

                if (! $selectedItem && filled($requestedYear)) {
                    $selectedItem = $seasonItems->first(function ($item) use ($requestedYear) {
                        return (string) $item->season_year === (string) $requestedYear;
                    });
                }

                if (! $selectedItem) {
                    $selectedItem = $seasonItems->first();
                }

                if ($selectedItem->type === 'series') {
                    $episodes = $program->episodes()
                        ->where('program_series_id', $selectedItem->id)
                        ->where('is_active', true)
                        ->where('show_on_public', true)
                        ->where(function ($q) {
                            $q->whereNull('status')->orWhere('status', 'published');
                        })
                        ->orderByRaw('CASE WHEN episode_number IS NULL THEN 1 ELSE 0 END')
                        ->orderBy('episode_number', 'asc')
                        ->orderBy('aired_at', 'asc')
                        ->get();
                } elseif ($selectedItem->type === 'season') {
                    $episodes = $program->episodes()
                        ->whereNull('program_series_id')
                        ->where('is_active', true)
                        ->where('show_on_public', true)
                        ->where(function ($q) {
                            $q->whereNull('status')->orWhere('status', 'published');
                        })
                        ->where('season_number', $selectedItem->season_number)
                        ->when($selectedItem->season_year, fn ($q) => $q->where('season_year', $selectedItem->season_year))
                        ->orderByRaw('CASE WHEN episode_number IS NULL THEN 1 ELSE 0 END')
                        ->orderBy('episode_number', 'asc')
                        ->orderBy('aired_at', 'asc')
                        ->get();
                } elseif ($selectedItem->type === 'unassigned') {
                    $episodes = $program->episodes()
                        ->whereNull('program_series_id')
                        ->whereNull('season_number')
                        ->whereNull('season_year')
                        ->where('is_active', true)
                        ->where('show_on_public', true)
                        ->where(function ($q) {
                            $q->whereNull('status')->orWhere('status', 'published');
                        })
                        ->orderByRaw('CASE WHEN episode_number IS NULL THEN 1 ELSE 0 END')
                        ->orderBy('episode_number', 'asc')
                        ->orderBy('aired_at', 'asc')
                        ->get();
                }
            }
        } else {
            // Non-series programs (e.g. Hikmet Arayışları, Akla Kapı)
            $rawSeasons = $program->episodes()
                ->whereNotNull('season_number')
                ->where('is_active', true)
                ->where('show_on_public', true)
                ->where(function ($q) {
                    $q->whereNull('status')->orWhere('status', 'published');
                })
                ->selectRaw('season_number, season_year, count(*) as total_episodes')
                ->groupBy('season_number', 'season_year')
                ->orderByDesc('season_number')
                ->get();

            // Merge empty defined seasons from programSeasons table
            $definedSeasons = $program->programSeasons()->get();
            foreach ($definedSeasons as $ds) {
                $exists = $rawSeasons->contains(fn ($s) => (string) $s->season_number === (string) $ds->season_number && (string) $s->season_year === (string) $ds->season_year);
                if (! $exists) {
                    $rawSeasons->push((object) [
                        'season_number' => $ds->season_number,
                        'season_year' => $ds->season_year,
                        'total_episodes' => 0,
                    ]);
                }
            }

            if ($rawSeasons->isNotEmpty()) {
                foreach ($rawSeasons as $s) {
                    $seasonRecord = \App\Models\ProgramSeason::findSeason($program->id, $s->season_number, $s->season_year);
                    if ($seasonRecord?->public_label) {
                        $label = $seasonRecord->public_label;
                    } elseif (filled($s->season_year)) {
                        $label = (string) $s->season_year;
                    } elseif (filled($s->season_number)) {
                        $label = 'Sezon ' . $s->season_number;
                    } else {
                        $label = 'Genel';
                    }

                    $seasonItems->push((object) [
                        'key' => 'season_' . $s->season_number . '_' . ($s->season_year ?? ''),
                        'type' => 'season',
                        'id' => null,
                        'slug' => null,
                        'name' => null,
                        'season_number' => $s->season_number,
                        'season_year' => $s->season_year,
                        'label' => $label,
                        'total_episodes' => (int) $s->total_episodes,
                        'url' => route('programs.show', array_filter([
                            'program' => $program,
                            'season' => $s->season_number,
                            'year' => $s->season_year,
                        ])),
                    ]);
                }

                if (filled($requestedSeason) && filled($requestedYear)) {
                    $selectedItem = $seasonItems->first(function ($item) use ($requestedSeason, $requestedYear) {
                        return (string) $item->season_number === (string) $requestedSeason && (string) $item->season_year === (string) $requestedYear;
                    });
                }

                if (! $selectedItem && filled($requestedYear)) {
                    $selectedItem = $seasonItems->first(function ($item) use ($requestedYear) {
                        return (string) $item->season_year === (string) $requestedYear;
                    });
                }

                if (! $selectedItem && filled($requestedSeason)) {
                    $selectedItem = $seasonItems->first(function ($item) use ($requestedSeason) {
                        return (string) $item->season_number === (string) $requestedSeason;
                    });
                }

                if (! $selectedItem) {
                    $selectedItem = $seasonItems->first();
                }

                $episodes = $program->episodes()
                    ->where('is_active', true)
                    ->where('show_on_public', true)
                    ->where(function ($q) {
                        $q->whereNull('status')->orWhere('status', 'published');
                    })
                    ->where('season_number', $selectedItem->season_number)
                    ->when($selectedItem->season_year, fn ($q) => $q->where('season_year', $selectedItem->season_year))
                    ->orderByRaw('CASE WHEN episode_number IS NULL THEN 1 ELSE 0 END')
                    ->orderBy('episode_number', 'asc')
                    ->orderBy('aired_at', 'asc')
                    ->get();
            } else {
                // Flat / Seasonless program
                $episodes = $program->episodes()
                    ->where('is_active', true)
                    ->where('show_on_public', true)
                    ->where(function ($q) {
                        $q->whereNull('status')->orWhere('status', 'published');
                    })
                    ->orderByRaw('CASE WHEN aired_at IS NULL THEN 1 ELSE 0 END')
                    ->orderByDesc('aired_at')
                    ->orderByDesc('created_at')
                    ->orderByDesc('id')
                    ->get();
            }
        }

        if ($targetEpisode) {
            $featuredEpisode = $targetEpisode;
            if ($episodes->doesntContain('id', $targetEpisode->id)) {
                $episodes = $episodes->prepend($targetEpisode);
            }
        } else {
            $featuredEpisode = $episodes->first();
        }

        // --- FAZ 1B: Program Detail Visual Builder Integration ---
        try {
            $layout = null;
            $previewLayoutId = $request->query('preview_layout_id');

            if (filled($previewLayoutId) && auth()->check()) {
                $layout = HomepageLayout::query()
                    ->where('id', $previewLayoutId)
                    ->where('page_type', 'program_detail')
                    ->first();
            }

            if (! $layout) {
                $layout = SiteCache::rememberProgramDetailLayout(function () {
                    return HomepageLayout::query()
                        ->where('page_type', 'program_detail')
                        ->where('is_active', true)
                        ->first();
                });
            }

            $sections = [];
            if ($layout) {
                $sections = (filled($previewLayoutId) && auth()->check())
                    ? ($layout->draft_sections ?? [])
                    : ($layout->published_sections ?? []);
            }

            unset($sections['_fixed_settings']);
            $sections = array_values(array_filter($sections, fn ($s) => is_array($s) && ! empty($s['visible'] ?? true)));

            if ($layout && ! empty($sections)) {
                $relatedPrograms = Program::query()
                    ->where('is_active', true)
                    ->where('show_on_public', true)
                    ->where('status', '!=', 'archived')
                    ->where('id', '!=', $program->id)
                    ->whereHas('categories', function ($q) use ($program) {
                        $catIds = $program->categories->pluck('id')->toArray();
                        $q->whereIn('categories.id', $catIds);
                    })
                    ->take(8)
                    ->get();

                if ($relatedPrograms->isEmpty()) {
                    $relatedPrograms = Program::query()
                        ->where('is_active', true)
                        ->where('show_on_public', true)
                        ->where('status', '!=', 'archived')
                        ->where('id', '!=', $program->id)
                        ->take(8)
                        ->get();
                }

                return view('programs.show_builder', [
                    'program' => $program,
                    'hasSeasons' => $seasonItems->isNotEmpty(),
                    'seasonItems' => $seasonItems,
                    'selectedSeasonItem' => $selectedItem,
                    'episodes' => $episodes,
                    'featuredEpisode' => $featuredEpisode,
                    'relatedPrograms' => $relatedPrograms,
                    'sections' => $sections,
                    'preview' => filled($previewLayoutId),
                ]);
            }
        } catch (\Throwable $e) {
            // Fallback gracefully on any render or resolution error
        }

        // Legacy Fallback View
        return view('programs.show', [
            'program' => $program,
            'hasSeasons' => $seasonItems->isNotEmpty(),
            'seasonItems' => $seasonItems,
            'selectedSeasonItem' => $selectedItem,
            'episodes' => $episodes,
            'featuredEpisode' => $featuredEpisode,
        ]);
    }
}
