@extends('layouts.app')

@section('title', $program->name . ' - Dost TV')
@section('description', filled($program->meta_description) ? e(trim($program->meta_description)) : \Illuminate\Support\Str::limit(strip_tags($program->description ?? ''), 160))

@section('content')
    <x-site.admin-edit-bar :program="$program" />
    @php
        $featured = $featuredEpisode;

        $initialSrc = ($featured?->video_source === 'youtube' && filled($featured?->youtube_embed_url))
            ? $featured->youtube_embed_url
            : null;

        $initialVideoSrc = (!$initialSrc && $featured?->video_source === 'upload' && filled($featured?->video_path))
            ? asset('storage/' . $featured->video_path)
            : null;

        // Fallback to program trailer embed URL if no public video exists
        if (!$initialSrc && !$initialVideoSrc && filled($program->trailer_embed_url)) {
            $initialSrc = $program->trailer_embed_url;
        }
    @endphp

    <section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8 space-y-10">
        {{-- Breadcrumb --}}
        <div>
            <a href="{{ route('programs.index') }}" class="inline-flex items-center text-sm font-semibold text-rose-400 hover:text-rose-300 transition-colors">
                &larr; Programlar
            </a>
        </div>

        {{-- TOP SECTION: SOL (Video Player + Categories) & SAĞ (Program Name + Description) --}}
        <div class="grid gap-8 lg:grid-cols-3 items-start">
            {{-- SOL: Video/Player Alanı ve Altında Sadece Kategoriler --}}
            <div class="lg:col-span-2 space-y-4">
                <div class="aspect-video overflow-hidden rounded-2xl bg-black ring-1 ring-white/10 shadow-2xl">
                    @if ($initialSrc)
                        <iframe id="program-player-iframe" src="{{ $initialSrc }}"
                                class="h-full w-full" allowfullscreen
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"></iframe>
                        <video id="program-player-video" class="hidden h-full w-full" controls></video>
                    @elseif ($initialVideoSrc)
                        <iframe id="program-player-iframe" class="hidden h-full w-full" allowfullscreen></iframe>
                        <video id="program-player-video" src="{{ $initialVideoSrc }}" class="h-full w-full" controls></video>
                    @else
                        <div class="flex h-full w-full items-center justify-center text-slate-500 text-sm">
                            Bu program için henüz video eklenmedi.
                        </div>
                        <iframe id="program-player-iframe" class="hidden"></iframe>
                        <video id="program-player-video" class="hidden"></video>
                    @endif
                </div>

                {{-- VİDEONUN ALTINDA: Sadece program kategori etiketleri --}}
                @if ($program->categories->isNotEmpty())
                    <div class="flex flex-wrap gap-2 pt-1">
                        @foreach ($program->categories as $category)
                            <span class="inline-flex items-center rounded-full bg-rose-500/10 px-3 py-1 text-xs font-semibold text-rose-300 ring-1 ring-inset ring-rose-500/20">
                                {{ $category->name }}
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- SAĞ: Program Adı & Açıklaması --}}
            <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-6 md:p-8 space-y-4 shadow-xl backdrop-blur-md">
                <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight leading-tight">
                    {{ $program->name }}
                </h1>

                @if (filled($program->description))
                    <div class="text-sm md:text-base text-slate-300 leading-relaxed whitespace-pre-line border-t border-white/10 pt-4">
                        {{ $program->description }}
                    </div>
                @endif
            </div>
        </div>

        {{-- SEZONLAR / SERİLER SEÇİCİ --}}
        @if (!empty($hasSeasons) && $seasonItems->isNotEmpty())
            <div class="pt-2">
                <x-site.program-season-selector
                    :has-seasons="$hasSeasons"
                    :season-items="$seasonItems"
                    :selected-season-item="$selectedSeasonItem"
                />
            </div>
        @endif

        {{-- VİDEO ARŞİVİ GRID --}}
        @if ($episodes->isNotEmpty())
            @php
                $featuredIndex = $episodes->search(fn ($ep) => $ep->id === $featuredEpisode?->id);
                $initialLimit = ($featuredIndex !== false && $featuredIndex >= 24) ? ($featuredIndex + 1) : 24;
            @endphp
            <div x-data="{ limit: {{ $initialLimit }}, total: {{ $episodes->count() }} }" class="pt-2">
                <div class="flex items-center justify-between gap-4 mb-6 border-b border-white/10 pb-4">
                    <div>
                        <h2 class="text-xl md:text-2xl font-bold text-white tracking-tight">Video Arşivi</h2>
                        @if ($selectedSeasonItem)
                            <p class="text-xs md:text-sm text-slate-400 mt-0.5">
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
                    @foreach ($episodes as $index => $episode)
                        @php
                            $isFeaturedActive = ($featuredEpisode && $featuredEpisode->id === $episode->id);
                            $episodeLabel = $episode->episode_number
                                ? $episode->episode_number . '. Bölüm'
                                : $episode->title;
                        @endphp
                        <button type="button"
                                x-show="{{ $index }} < limit"
                                x-cloak
                                class="episode-select group text-left flex flex-col cursor-pointer p-1.5 rounded-xl transition duration-200 {{ $isFeaturedActive ? 'bg-rose-950/40 ring-2 ring-rose-500 shadow-lg shadow-rose-950/50' : '' }}"
                                title="{{ $episode->title }}"
                                data-type="{{ $episode->video_source === 'youtube' ? 'iframe' : 'video' }}"
                                data-src="{{ $episode->video_source === 'youtube' ? $episode->youtube_embed_url : ($episode->video_path ? asset('storage/' . $episode->video_path) : '') }}">
                            <div class="aspect-video w-full overflow-hidden rounded-xl bg-slate-900 ring-1 {{ $isFeaturedActive ? 'ring-rose-500' : 'ring-white/10' }} relative group-hover:ring-rose-500/50 transition duration-200">
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

                                @if ($isFeaturedActive)
                                    <div class="absolute top-2 left-2 rounded-md bg-rose-600 px-2 py-0.5 text-[10px] font-bold text-white shadow">
                                        Oynatılıyor
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
                                <p class="text-sm font-semibold {{ $isFeaturedActive ? 'text-rose-400' : 'text-white' }} group-hover:text-rose-300 transition-colors line-clamp-1">
                                    {{ $episodeLabel }}
                                </p>
                            </div>
                        </button>
                    @endforeach
                </div>

                {{-- Show More Button --}}
                <div x-show="limit < total" class="mt-10 text-center">
                    <button type="button"
                            @click="limit += 24"
                            class="inline-flex items-center justify-center rounded-xl bg-rose-600 hover:bg-rose-500 px-6 py-3 text-sm font-bold text-white shadow-lg shadow-rose-950/50 transition duration-200 active:scale-95 border border-rose-500/50 focus:outline-none">
                        Daha Fazla Göster
                    </button>
                </div>
            </div>
        @else
            <div class="mt-8 rounded-2xl border border-white/10 bg-white/[0.02] p-8 text-center text-slate-500">
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
