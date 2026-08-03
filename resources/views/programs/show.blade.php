@extends('layouts.app')

@section('title', $program->name . ' - Dost TV')
@section('description', \Illuminate\Support\Str::limit(strip_tags($program->description ?? ''), 150))

@section('content')
    @php
        $firstEpisode = $program->episodes->first();
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

        @if ($program->episodes->isNotEmpty())
            <div class="mt-16">
                <h2 class="text-2xl font-bold text-white">Video Arşivi</h2>
                <div class="mt-6 grid grid-cols-2 gap-6 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach ($program->episodes as $episode)
                        <button type="button"
                                class="episode-select group text-left"
                                data-type="{{ $episode->video_source === 'youtube' ? 'iframe' : 'video' }}"
                                data-src="{{ $episode->video_source === 'youtube' ? $episode->youtube_embed_url : ($episode->video_path ? asset('storage/' . $episode->video_path) : '') }}">
                            <div class="aspect-video overflow-hidden rounded-lg bg-slate-900 ring-1 ring-white/10">
                                @if ($episode->thumbnail)
                                    <img src="{{ asset('storage/' . $episode->thumbnail) }}" alt="{{ $episode->title }}"
                                         class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                                @else
                                    <div class="flex h-full w-full items-center justify-center text-slate-700">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-8 w-8">
                                            <path d="M8 5v14l11-7z" />
                                        </svg>
                                    </div>
                                @endif
                            </div>
                            <p class="mt-2 text-sm font-medium text-white group-hover:text-rose-300">{{ $episode->title }}</p>
                            @if ($episode->aired_at)
                                <p class="text-xs text-slate-500">{{ $episode->aired_at->format('d.m.Y') }}</p>
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>
        @endif
    </section>

    <script>
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
    </script>
@endsection
