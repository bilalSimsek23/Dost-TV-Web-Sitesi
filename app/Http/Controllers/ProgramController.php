<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Program;
use App\Models\ProgramSeries;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProgramController extends Controller
{
    public function index(Request $request): View
    {
        $programs = Program::query()
            ->with('categories')
            ->where('is_active', true)
            ->when($request->string('kategori')->isNotEmpty(), function ($query) use ($request) {
                $query->whereHas('categories', fn ($q) => $q->where('slug', $request->string('kategori')));
            })
            ->orderBy('sort_order')
            ->paginate(12)
            ->withQueryString();

        return view('programs.index', [
            'programs' => $programs,
            'categories' => Category::orderBy('name')->get(),
            'activeCategory' => $request->string('kategori')->toString(),
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
                // Series Label: series name is the primary label for series items
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

            // Check if there are unassigned episodes
            $unassignedCount = $program->episodes()
                ->whereNull('program_series_id')
                ->where('is_active', true)
                ->where('show_on_public', true)
                ->count();

            if ($unassignedCount > 0) {
                $seasonItems->push((object) [
                    'key' => 'unassigned',
                    'type' => 'unassigned',
                    'id' => null,
                    'slug' => 'diger-bolumler',
                    'name' => 'Diğer Bölümler',
                    'season_number' => null,
                    'season_year' => null,
                    'label' => 'Diğer Bölümler',
                    'total_episodes' => $unassignedCount,
                    'url' => route('programs.show', [
                        'program' => $program,
                        'seri' => 'diger-bolumler',
                    ]),
                ]);
            }

            if ($seasonItems->isNotEmpty()) {
                $requestedSeries = $request->query('series', $request->query('seri'));
                $requestedSeason = $request->query('season', $request->query('sezon'));
                $requestedYear = $request->query('year', $request->query('yil'));

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
                        ->orderByRaw('CASE WHEN episode_number IS NULL THEN 1 ELSE 0 END')
                        ->orderBy('episode_number', 'asc')
                        ->orderBy('aired_at', 'asc')
                        ->get();
                } elseif ($selectedItem->type === 'unassigned') {
                    $episodes = $program->episodes()
                        ->whereNull('program_series_id')
                        ->where('is_active', true)
                        ->where('show_on_public', true)
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
                ->selectRaw('season_number, season_year, count(*) as total_episodes')
                ->groupBy('season_number', 'season_year')
                ->orderByDesc('season_number')
                ->get();

            if ($rawSeasons->isNotEmpty()) {
                foreach ($rawSeasons as $s) {
                    // Label Rule:
                    // A) season_year filled -> season_year
                    // B) season_year empty, series.name filled -> series.name
                    // C) both empty -> "Sezon {$season_number}"
                    if (filled($s->season_year)) {
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

                $requestedSeason = $request->query('season', $request->query('sezon'));
                $requestedYear = $request->query('year', $request->query('yil'));

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
                    ->orderBy('sort_order')
                    ->orderByDesc('aired_at')
                    ->get();
            }
        }

        return view('programs.show', [
            'program' => $program,
            'hasSeasons' => $seasonItems->isNotEmpty(),
            'seasonItems' => $seasonItems,
            'selectedSeasonItem' => $selectedItem,
            'episodes' => $episodes,
        ]);
    }
}
