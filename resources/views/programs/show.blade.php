@extends('layouts.app')

@section('title', $program->name . ' - Dost TV')
@section('description', \Illuminate\Support\Str::limit(strip_tags($program->description ?? ''), 150))

@section('content')
    @php
        $firstEpisode = ($hasSeries ?? false)
            ? ($seriesGroups->first()?->episodes->first() ?? $unassignedEpisodes->first() ?? $program->episodes->first())
            : $program->episodes->first();
        $initialSrc = $program->trailer_embed_url
            ?? ($firstEpisode?->video_source === 'youtube' ? $firstEpisode?->youtube_embed_url : null);
        $initialVideoSrc = (!$initialSrc && $firstEpisode?->video_source === 'upload' && $firstEpisode?->video_path)
            ? asset('storage/' . $firstEpisode->video_path)
            : null;
    @endphp

    <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <a href="{{ route('programs.index') }}" class="text-sm font-semibold text-rose-400 hover:text-rose-300">&larr; Programlar</a>

        <div class="mt-6 grid gap-10 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <div class="aspect-video overflow-hidden rounded-2xl bg-black ring-1 ring-white/10">
                    @if ($initialSrc)
                        <iframe id="program-player-iframe" src="{{ $initialSrc }}"
                                class="h-full w-full" allowfullscreen
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"></iframe>
                        <video id="program-player-video" class="hidden h-full w-full" controls></video>
                    @elseif ($initialVideoSrc)
                        <iframe id="program-player-iframe" class="hidden h-full w-full" allowfullscreen></iframe>
                        <video id="program-player-video" src="{{ $initialVideoSrc }}" class="h-full w-full" controls></video>
                    @else
                        <div class="flex h-full w-full items-center justify-center text-slate-600">
                            Bu program için henüz video eklenmedi.
                        </div>
                        <iframe id="program-player-iframe" class="hidden"></iframe>
                        <video id="program-player-video" class="hidden"></video>
                    @endif
                </div>

                <h1 class="mt-6 text-3xl font-black text-white">{{ $program->name }}</h1>
                @if ($program->categories->isNotEmpty())
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach ($program->categories as $category)
                            <span class="inline-block rounded-full bg-white/5 px-3 py-1 text-xs font-semibold text-rose-300 ring-1 ring-inset ring-white/10">
                                {{ $category->name }}
                            </span>
                        @endforeach
                    </div>
                @endif
                @if ($program->description)
                    <p class="mt-4 max-w-3xl text-slate-400">{{ $program->description }}</p>
                @endif
            </div>

            <aside class="space-y-6">
                <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                    <h2 class="text-sm font-semibold uppercase tracking-wider text-slate-400">Yayın Saatleri</h2>
                    <ul class="mt-4 space-y-2">
                        @forelse ($program->schedules as $schedule)
                            <li class="flex items-center justify-between text-sm">
                                <span class="text-slate-300">{{ $schedule->day_name }}</span>
                                <span class="font-semibold text-rose-300">
                                    {{ \Illuminate\Support\Carbon::parse($schedule->start_time)->format('H:i') }}
                                </span>
                            </li>
                        @empty
                            <li class="text-sm text-slate-500">Yayın akışı henüz planlanmadı.</li>
                        @endforelse
                    </ul>
                </div>
            </aside>
        </div>

        @if (!empty($hasSeasons) && $seasonItems->isNotEmpty())
            {{-- Fixed "Sezonlar" Section with Dynamic Selection Boxes --}}
            <div class="mt-16">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-white/10">
                    <div>
                        <h2 class="text-2xl font-bold text-white tracking-tight">Sezonlar</h2>
                        <p class="text-sm text-slate-400 mt-1">İzlemek istediğiniz sezonu seçin.</p>
                    </div>
                    @if ($selectedSeasonItem)
                        <div class="text-xs font-semibold text-rose-300 bg-rose-950/40 border border-rose-800/40 px-3 py-1.5 rounded-full self-start sm:self-auto">
                            Seçili: {{ $selectedSeasonItem->label }} · {{ $selectedSeasonItem->total_episodes }} Bölüm
                        </div>
                    @endif
                </div>

                @if ($seasonItems->count() > 1)
                    <div class="mt-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
                        @foreach ($seasonItems as $item)
                            @php
                                $isActive = ($selectedSeasonItem && $selectedSeasonItem->key === $item->key);
                            @endphp
                            <a href="{{ $item->url }}"
                               class="group relative flex items-center justify-between p-4 rounded-xl transition-all duration-200 {{ $isActive ? 'bg-gradient-to-r from-rose-900/60 to-rose-950/80 border-rose-500/60 shadow-lg shadow-rose-950/50 ring-1 ring-rose-500/50' : 'bg-white/[0.03] hover:bg-white/[0.07] border-white/10 text-slate-300 hover:text-white' }} border">
                                <div class="flex items-center gap-3">
                                    <div class="w-2.5 h-2.5 rounded-full {{ $isActive ? 'bg-rose-500 animate-pulse' : 'bg-slate-600 group-hover:bg-slate-400' }}"></div>
                                    <div>
                                        <div class="font-bold text-base text-white group-hover:text-rose-300 transition-colors">
                                            {{ $item->label }}
                                        </div>
                                        <div class="text-xs text-slate-400 mt-0.5">
                                            {{ $item->total_episodes }} Bölüm
                                        </div>
                                    </div>
                                </div>
                                <span class="text-xs font-semibold px-2.5 py-1 rounded-lg transition-colors {{ $isActive ? 'bg-rose-500/30 text-rose-200 border border-rose-400/30' : 'bg-white/5 text-slate-400 group-hover:bg-white/10 group-hover:text-slate-200' }}">
                                    {{ $isActive ? 'Seçili' : 'Görüntüle' }}
                                </span>
                            </a>
                        @endforeach
                    </div>
                @else
                    @php
                        $singleItem = $seasonItems->first();
                    @endphp
                    <div class="mt-4 inline-flex items-center gap-3 px-4 py-2 rounded-xl bg-white/[0.03] border border-white/10 text-sm">
                        <span class="font-semibold text-white">{{ $singleItem->label }}</span>
                        <span class="text-xs text-slate-400 bg-white/10 px-2 py-0.5 rounded-md">{{ $singleItem->total_episodes }} Bölüm</span>
                    </div>
                @endif
            </div>
        @endif

        {{-- Video Archive Grid --}}
        @if ($episodes->isNotEmpty())
            <div class="{{ (!empty($hasSeasons) && $seasonItems->isNotEmpty()) ? 'mt-12' : 'mt-16' }}">
                <div class="flex items-center justify-between gap-4 mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-white tracking-tight">Video Arşivi</h2>
                        @if ($selectedSeasonItem)
                            <p class="text-sm text-slate-400 mt-1">
                                {{ $selectedSeasonItem->label }} videoları listeleniyor
                            </p>
                        @endif
                    </div>
                    <span class="text-xs font-semibold text-slate-400 bg-white/5 border border-white/10 px-3 py-1 rounded-full">
                        {{ $episodes->count() }} Video
                    </span>
                </div>

                {{-- 4-Column Responsive Video Grid: Desktop 4, Tablet 3, Mobile 2 --}}
                <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-5">
                    @foreach ($episodes as $episode)
                        @php
                            $episodeLabel = $episode->episode_number
                                ? $episode->episode_number . '. Bölüm'
                                : $episode->title;
                        @endphp
                        <button type="button"
                                class="episode-select group text-left flex flex-col cursor-pointer"
                                title="{{ $episode->title }}"
                                data-type="{{ $episode->video_source === 'youtube' ? 'iframe' : 'video' }}"
                                data-src="{{ $episode->video_source === 'youtube' ? $episode->youtube_embed_url : ($episode->video_path ? asset('storage/' . $episode->video_path) : '') }}">
                            <div class="aspect-video w-full overflow-hidden rounded-xl bg-slate-900 ring-1 ring-white/10 relative group-hover:ring-rose-500/50 transition duration-200">
                                @if ($episode->thumbnail_url)
                                    <img src="{{ $episode->thumbnail_url }}" alt="{{ $episodeLabel }}"
                                         class="h-full w-full object-cover transition duration-300 group-hover:scale-105" loading="lazy">
                                @else
                                    <div class="flex h-full w-full items-center justify-center text-slate-600 bg-slate-950">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-8 w-8">
                                            <path d="M8 5v14l11-7z" />
                                        </svg>
                                    </div>
                                @endif

                                <div class="absolute inset-0 bg-black/20 group-hover:bg-black/0 transition flex items-center justify-center">
                                    <div class="w-10 h-10 rounded-full bg-rose-600/90 text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transform scale-75 group-hover:scale-100 transition duration-200 shadow-lg">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 ml-0.5">
                                            <path d="M8 5v14l11-7z" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-2.5">
                                <p class="text-sm font-semibold text-white group-hover:text-rose-300 transition-colors line-clamp-1">
                                    {{ $episodeLabel }}
                                </p>
                            </div>
                        </button>
                    @endforeach
                </div>
            </div>
        @else
            <div class="mt-12 rounded-2xl border border-white/10 bg-white/[0.02] p-8 text-center text-slate-500">
                Bu {{ (!empty($hasSeasons) && $seasonItems->isNotEmpty()) ? 'sezon' : 'program' }} için video bulunamadı.
            </div>
        @endif

    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Video Player Selection
            document.querySelectorAll('.episode-select').forEach(function (button) {
                button.addEventListener('click', function () {
                    var type = button.dataset.type;
                    var src = button.dataset.src;
                    var iframe = document.getElementById('program-player-iframe');
                    var video = document.getElementById('program-player-video');

                    if (!src) {
                        return;
                    }

                    if (type === 'iframe') {
                        iframe.src = src;
                        iframe.classList.remove('hidden');
                        video.pause();
                        video.classList.add('hidden');
                    } else {
                        video.src = src;
                        video.classList.remove('hidden');
                        video.play();
                        iframe.src = '';
                        iframe.classList.add('hidden');
                    }

                    window.scrollTo({ top: 0, behavior: 'smooth' });
                });
            });
        });
    </script>
@endsection
