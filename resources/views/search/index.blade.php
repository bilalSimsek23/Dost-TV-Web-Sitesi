@extends('layouts.app')

@section('title', ($query !== '' ? '"' . $query . '" için arama sonuçları' : 'Arama') . ' - Dost TV')

@section('content')
    <div class="bg-slate-950 py-10 sm:py-16 min-h-[70vh]">
        <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <h1 class="text-2xl sm:text-4xl font-extrabold text-white tracking-tight">
                @if ($query !== '')
                    “<span class="text-rose-500">{{ $query }}</span>” için arama sonuçları
                @else
                    Arama
                @endif
            </h1>

            @if ($query === '')
                <p class="mt-4 text-slate-400">Aramak için üstteki arama kutusunu kullanın.</p>
            @else
                {{-- Kompakt Filtre / Sekmeler --}}
                <div class="mt-6 flex flex-wrap gap-2 border-b border-slate-800/80 pb-4">
                    <a href="{{ route('search.index', ['q' => $query, 'type' => 'all']) }}"
                       class="rounded-full px-5 py-2 text-sm font-medium transition {{ $type === 'all' ? 'bg-rose-600 text-white shadow-lg ring-1 ring-rose-500' : 'bg-white/5 text-slate-300 hover:bg-white/10' }}">
                        Tümü
                        @if ($totalProgramsCount + $totalEpisodesCount > 0)
                            <span class="ml-1 rounded-full bg-white/20 px-2 py-0.5 text-xs text-white">{{ $totalProgramsCount + $totalEpisodesCount }}</span>
                        @endif
                    </a>

                    <a href="{{ route('search.index', ['q' => $query, 'type' => 'programs']) }}"
                       class="rounded-full px-5 py-2 text-sm font-medium transition {{ $type === 'programs' ? 'bg-rose-600 text-white shadow-lg ring-1 ring-rose-500' : 'bg-white/5 text-slate-300 hover:bg-white/10' }}">
                        Programlar
                        @if ($totalProgramsCount > 0)
                            <span class="ml-1 rounded-full bg-white/20 px-2 py-0.5 text-xs text-white">{{ $totalProgramsCount }}</span>
                        @endif
                    </a>

                    <a href="{{ route('search.index', ['q' => $query, 'type' => 'episodes']) }}"
                       class="rounded-full px-5 py-2 text-sm font-medium transition {{ $type === 'episodes' ? 'bg-rose-600 text-white shadow-lg ring-1 ring-rose-500' : 'bg-white/5 text-slate-300 hover:bg-white/10' }}">
                        Bölümler
                        @if ($totalEpisodesCount > 0)
                            <span class="ml-1 rounded-full bg-white/20 px-2 py-0.5 text-xs text-white">{{ $totalEpisodesCount }}</span>
                        @endif
                    </a>
                </div>

                {{-- Empty State --}}
                @if ($type === 'all' && $programs->isEmpty() && $episodes->isEmpty())
                    <div class="mt-8 rounded-2xl bg-slate-900/40 p-8 text-center border border-slate-800/80">
                        <p class="text-slate-400 text-base">“<span class="text-white font-semibold">{{ $query }}</span>” için sonuç bulunamadı.</p>
                    </div>
                @elseif ($type === 'programs' && $programs->isEmpty())
                    <div class="mt-8 rounded-2xl bg-slate-900/40 p-8 text-center border border-slate-800/80">
                        <p class="text-slate-400 text-base">“<span class="text-white font-semibold">{{ $query }}</span>” aramasına uygun program bulunamadı.</p>
                    </div>
                @elseif ($type === 'episodes' && $episodes->isEmpty())
                    <div class="mt-8 rounded-2xl bg-slate-900/40 p-8 text-center border border-slate-800/80">
                        <p class="text-slate-400 text-base">“<span class="text-white font-semibold">{{ $query }}</span>” aramasına uygun bölüm bulunamadı.</p>
                    </div>
                @else
                    {{-- 1. TÜMÜ GÖRÜNÜMÜ --}}
                    @if ($type === 'all')
                        {{-- Programlar Bölümü (Tümü sekmesinde en başta) --}}
                        @if ($programs->isNotEmpty())
                            <div class="mt-8">
                                <div class="flex items-center justify-between border-b border-slate-800/60 pb-3 mb-6">
                                    <h2 class="text-xl font-bold text-white flex items-center gap-2">
                                        <span class="h-2 w-2 rounded-full bg-rose-500"></span>
                                        <span>Programlar</span>
                                    </h2>
                                    @if ($totalProgramsCount > 4)
                                        <a href="{{ route('search.index', ['q' => $query, 'type' => 'programs']) }}" class="hidden sm:inline-flex items-center text-xs font-semibold text-rose-400 hover:text-rose-300 transition">
                                            Tüm Program Sonuçlarını Gör ({{ $totalProgramsCount }}) &rarr;
                                        </a>
                                    @endif
                                </div>

                                <div class="grid grid-cols-2 gap-6 sm:grid-cols-3 lg:grid-cols-4">
                                    @foreach ($programs as $program)
                                        <a href="{{ route('programs.show', $program) }}" class="block">
                                            <x-site.program-card
                                                :title="$program->name"
                                                :cover-image="$program->cover_image"
                                                :categories="$program->categories->pluck('name')->toArray()"
                                            />
                                        </a>
                                    @endforeach
                                </div>

                                @if ($totalProgramsCount > 4)
                                    <div class="mt-6 text-center sm:hidden">
                                        <a href="{{ route('search.index', ['q' => $query, 'type' => 'programs']) }}" class="inline-flex items-center rounded-xl bg-white/5 px-4 py-2.5 text-xs font-semibold text-rose-400 border border-white/10 hover:bg-white/10 transition">
                                            Tüm Program Sonuçlarını Gör ({{ $totalProgramsCount }}) &rarr;
                                        </a>
                                    </div>
                                @endif
                            </div>
                        @endif

                        {{-- Bölümler Bölümü (Tümü sekmesinde programlardan sonra) --}}
                        @if ($episodes->isNotEmpty())
                            <div class="mt-12">
                                <div class="flex items-center justify-between border-b border-slate-800/60 pb-3 mb-6">
                                    <h2 class="text-xl font-bold text-white flex items-center gap-2">
                                        <span class="h-2 w-2 rounded-full bg-rose-500"></span>
                                        <span>Bölümler</span>
                                    </h2>
                                    @if ($totalEpisodesCount > 6)
                                        <a href="{{ route('search.index', ['q' => $query, 'type' => 'episodes']) }}" class="hidden sm:inline-flex items-center text-xs font-semibold text-rose-400 hover:text-rose-300 transition">
                                            Tüm Bölüm Sonuçlarını Gör ({{ $totalEpisodesCount }}) &rarr;
                                        </a>
                                    @endif
                                </div>

                                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                                    @foreach ($episodes as $episode)
                                        <x-site.video-card :episode="$episode" />
                                    @endforeach
                                </div>

                                @if ($totalEpisodesCount > 6)
                                    <div class="mt-6 text-center sm:hidden">
                                        <a href="{{ route('search.index', ['q' => $query, 'type' => 'episodes']) }}" class="inline-flex items-center rounded-xl bg-white/5 px-4 py-2.5 text-xs font-semibold text-rose-400 border border-white/10 hover:bg-white/10 transition">
                                            Tüm Bölüm Sonuçlarını Gör ({{ $totalEpisodesCount }}) &rarr;
                                        </a>
                                    </div>
                                @endif
                            </div>
                        @endif

                    {{-- 2. PROGRAMLAR GÖRÜNÜMÜ --}}
                    @elseif ($type === 'programs')
                        <div class="mt-8">
                            <div class="grid grid-cols-2 gap-6 sm:grid-cols-3 lg:grid-cols-4">
                                @foreach ($programs as $program)
                                    <a href="{{ route('programs.show', $program) }}" class="block">
                                        <x-site.program-card
                                            :title="$program->name"
                                            :cover-image="$program->cover_image"
                                            :categories="$program->categories->pluck('name')->toArray()"
                                        />
                                    </a>
                                @endforeach
                            </div>

                            @if ($paginatedPrograms)
                                <div class="mt-12">
                                    {{ $paginatedPrograms->links() }}
                                </div>
                            @endif
                        </div>

                    {{-- 3. BÖLÜMLER GÖRÜNÜMÜ --}}
                    @elseif ($type === 'episodes')
                        <div class="mt-8">
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                                @foreach ($episodes as $episode)
                                    <x-site.video-card :episode="$episode" />
                                @endforeach
                            </div>

                            @if ($paginatedEpisodes)
                                <div class="mt-12">
                                    {{ $paginatedEpisodes->links() }}
                                </div>
                            @endif
                        </div>
                    @endif
                @endif
            @endif
        </section>
    </div>
@endsection
