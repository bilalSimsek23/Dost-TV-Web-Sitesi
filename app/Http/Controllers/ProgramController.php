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

    public function show(Program $program): View
    {
        $program->load([
            'categories',
            'schedules' => fn ($query) => $query->orderBy('day_of_week')->orderBy('start_time'),
            'episodes' => fn ($query) => $query->orderBy('sort_order')->orderByDesc('aired_at'),
        ]);

        return view('programs.show', [
            'program' => $program,
        ]);
    }
}
