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
        $program->load([
            'categories',
            'schedules' => fn ($query) => $query->orderBy('day_of_week')->orderBy('start_time'),
        ]);

        $hasSeries = $program->programSeries()->exists() || $program->episodes()->whereNotNull('program_series_id')->exists();

        if ($hasSeries) {
            // Load series ordered by season_number DESC, sort_order ASC, id ASC
            $seriesGroups = $program->programSeries()
                ->leftJoin('program_seasons', 'program_series.program_season_id', '=', 'program_seasons.id')
                ->select('program_series.*', 'program_seasons.season_number as calculated_season_number')
                ->orderByDesc('calculated_season_number')
                ->orderBy('program_series.sort_order')
                ->orderBy('program_series.id')
                ->with(['episodes' => function ($q) {
                    $q->where('is_active', true)
                      ->where('show_on_public', true)
                      ->orderByRaw('CASE WHEN episode_number IS NULL THEN 1 ELSE 0 END')
                      ->orderBy('episode_number', 'asc')
                      ->orderBy('aired_at', 'asc');
                }])
                ->get();

            // Optional filter by series if requested via query
            $requestedSeries = $request->query('series', $request->query('seri'));
            if (filled($requestedSeries)) {
                $seriesGroups = $seriesGroups->filter(function ($s) use ($requestedSeries) {
                    return (string) $s->id === (string) $requestedSeries
                        || $s->slug === (string) $requestedSeries
                        || mb_strtolower($s->name) === mb_strtolower((string) $requestedSeries);
                });
            }

            $unassignedEpisodes = $program->episodes()
                ->whereNull('program_series_id')
                ->where('is_active', true)
                ->where('show_on_public', true)
                ->orderByRaw('CASE WHEN episode_number IS NULL THEN 1 ELSE 0 END')
                ->orderBy('episode_number', 'asc')
                ->orderBy('aired_at', 'asc')
                ->get();

            return view('programs.show', [
                'program' => $program,
                'hasSeries' => true,
                'seriesGroups' => $seriesGroups,
                'unassignedEpisodes' => $unassignedEpisodes,
                'seasons' => collect(),
                'selectedSeason' => null,
                'episodes' => collect(),
            ]);
        }

        // Non-series programs (e.g. Hikmet Arayışları, Akla Kapı)
        $seasons = $program->episodes()
            ->whereNotNull('season_number')
            ->selectRaw('season_number, season_year, count(*) as total_episodes')
            ->groupBy('season_number', 'season_year')
            ->orderByDesc('season_number')
            ->get();

        $selectedSeason = null;

        if ($seasons->isNotEmpty()) {
            $requestedSeason = $request->query('season', $request->query('sezon'));
            $requestedYear = $request->query('year', $request->query('yil'));

            if (filled($requestedSeason)) {
                $selectedSeason = $seasons->first(function ($s) use ($requestedSeason, $requestedYear) {
                    if ($requestedYear) {
                        return (string) $s->season_number === (string) $requestedSeason && (string) $s->season_year === (string) $requestedYear;
                    }
                    return (string) $s->season_number === (string) $requestedSeason;
                });
            }

            if (! $selectedSeason) {
                $selectedSeason = $seasons->first();
            }

            $episodes = $program->episodes()
                ->where('season_number', $selectedSeason->season_number)
                ->when($selectedSeason->season_year, fn ($q) => $q->where('season_year', $selectedSeason->season_year))
                ->orderByRaw('CASE WHEN episode_number IS NULL THEN 1 ELSE 0 END')
                ->orderBy('episode_number', 'asc')
                ->orderBy('aired_at', 'asc')
                ->get();
        } else {
            $episodes = $program->episodes()
                ->orderBy('sort_order')
                ->orderByDesc('aired_at')
                ->get();
        }

        return view('programs.show', [
            'program' => $program,
            'hasSeries' => false,
            'seriesGroups' => collect(),
            'unassignedEpisodes' => collect(),
            'seasons' => $seasons,
            'selectedSeason' => $selectedSeason,
            'episodes' => $episodes,
        ]);
    }
}
