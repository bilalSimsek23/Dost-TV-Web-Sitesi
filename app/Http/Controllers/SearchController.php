<?php

namespace App\Http\Controllers;

use App\Models\Episode;
use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function index(Request $request): View
    {
        $query = trim((string) $request->string('q'));
        $type = in_array($request->query('type'), ['all', 'programs', 'episodes'], true)
            ? (string) $request->query('type')
            : 'all';

        $programs = collect();
        $episodes = collect();
        $totalProgramsCount = 0;
        $totalEpisodesCount = 0;
        $paginatedPrograms = null;
        $paginatedEpisodes = null;

        if ($query !== '') {
            $queryLower = mb_strtolower($query);

            $baseProgramQuery = Program::query()
                ->with(['categories'])
                ->where('show_on_public', true)
                ->where('is_active', true)
                ->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                        ->orWhere('short_description', 'like', "%{$query}%");
                })
                ->orderByRaw("
                    CASE 
                        WHEN LOWER(name) = ? THEN 1
                        WHEN LOWER(name) LIKE ? THEN 2
                        WHEN LOWER(name) LIKE ? THEN 3
                        ELSE 4
                    END
                ", [
                    $queryLower,
                    $queryLower . '%',
                    '%' . $queryLower . '%',
                ])
                ->orderBy('sort_order')
                ->orderBy('name');

            $baseEpisodeQuery = Episode::query()
                ->with(['program'])
                ->where('show_on_public', true)
                ->where('is_active', true)
                ->whereHas('program', fn ($q) => $q->where('show_on_public', true)->where('is_active', true))
                ->where(function ($q) use ($query) {
                    $q->where('title', 'like', "%{$query}%")
                        ->orWhereHas('program', fn ($pq) => $pq->where('name', 'like', "%{$query}%"));
                })
                ->orderByRaw("
                    CASE 
                        WHEN LOWER(title) = ? THEN 1
                        WHEN LOWER(title) LIKE ? THEN 2
                        WHEN LOWER(title) LIKE ? THEN 3
                        ELSE 4
                    END
                ", [
                    $queryLower,
                    $queryLower . '%',
                    '%' . $queryLower . '%',
                ])
                ->orderByDesc('aired_at')
                ->orderByDesc('created_at');

            if ($type === 'all') {
                $totalProgramsCount = (clone $baseProgramQuery)->count();
                $totalEpisodesCount = (clone $baseEpisodeQuery)->count();

                $programs = (clone $baseProgramQuery)->take(4)->get();
                $episodes = (clone $baseEpisodeQuery)->take(6)->get();
            } elseif ($type === 'programs') {
                $paginatedPrograms = (clone $baseProgramQuery)->paginate(12)->withQueryString();
                $totalProgramsCount = $paginatedPrograms->total();
                $programs = collect($paginatedPrograms->items());

                $totalEpisodesCount = (clone $baseEpisodeQuery)->count();
            } elseif ($type === 'episodes') {
                $paginatedEpisodes = (clone $baseEpisodeQuery)->paginate(12)->withQueryString();
                $totalEpisodesCount = $paginatedEpisodes->total();
                $episodes = collect($paginatedEpisodes->items());

                $totalProgramsCount = (clone $baseProgramQuery)->count();
            }

            $totalResultCount = $totalProgramsCount + $totalEpisodesCount;

            $page = (int) $request->input('page', 1);
            if ($page <= 1) {
                app(\App\Services\Analytics\AnalyticsService::class)->logSearch(
                    query: $query,
                    resultCount: $totalResultCount,
                    deviceType: $request->header('User-Agent') && str_contains(strtolower($request->header('User-Agent')), 'mobile') ? 'mobile' : 'desktop',
                    sourcePage: $request->header('referer')
                );
            }
        }

        return view('search.index', [
            'query' => $query,
            'type' => $type,
            'programs' => $programs,
            'episodes' => $episodes,
            'totalProgramsCount' => $totalProgramsCount,
            'totalEpisodesCount' => $totalEpisodesCount,
            'paginatedPrograms' => $paginatedPrograms,
            'paginatedEpisodes' => $paginatedEpisodes,
        ]);
    }
}
