@extends('layouts.app')

@section('title', 'Dost TV - Ana Sayfa')

@section('content')
    @if ($banners->isNotEmpty())
        <section class="relative overflow-hidden" x-data="{ active: 0, count: {{ $banners->count() }} }"
                  x-init="setInterval(() => active = (active + 1) % count, 6000)">
            <div class="relative aspect-[21/9] w-full sm:aspect-[3/1]">
                @foreach ($banners as $index => $banner)
                    <div x-show="active === {{ $index }}" x-transition:enter="transition ease-out duration-700"
                         x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                         class="absolute inset-0">
                        @if ($banner->link_url)
                            <a href="{{ $banner->link_url }}" class="absolute inset-0">
                        @endif
                        <img src="{{ asset('storage/' . $banner->image) }}" alt="{{ $banner->title }}"
                             class="h-full w-full object-cover">
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/30 to-transparent"></div>
                        <div class="absolute inset-x-0 bottom-0 p-6 sm:p-10">
                            <h2 class="text-2xl font-black text-white sm:text-4xl">{{ $banner->title }}</h2>
                            @if ($banner->subtitle)
                                <p class="mt-2 max-w-2xl text-slate-300">{{ $banner->subtitle }}</p>
                            @endif
                        </div>
                        @if ($banner->link_url)
                            </a>
                        @endif
                    </div>
                @endforeach
            </div>

            @if ($banners->count() > 1)
                <div class="absolute bottom-4 right-6 flex gap-2">
                    @foreach ($banners as $index => $banner)
                        <button type="button" @click="active = {{ $index }}"
                                :class="active === {{ $index }} ? 'bg-rose-500' : 'bg-white/30'"
                                class="h-2 w-2 rounded-full transition"></button>
                    @endforeach
                </div>
            @endif
        </section>
    @endif

    <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-24">
        <div class="grid items-center gap-12 lg:grid-cols-2">
            <div>
                <span class="inline-flex items-center gap-2 rounded-full bg-rose-500/10 px-3 py-1 text-xs font-semibold text-rose-400 ring-1 ring-inset ring-rose-500/20">
                    Uydu üzerinden 7/24 yayın
                </span>
                <h1 class="mt-6 text-4xl font-black leading-tight text-white sm:text-5xl lg:text-6xl">
                    {{ $settings->site_name }}
                    <span class="block bg-gradient-to-r from-rose-400 to-amber-300 bg-clip-text text-transparent">her an yanınızda</span>
                </h1>
                <p class="mt-6 max-w-xl text-lg text-slate-400">
                    Diziler, haberler ve belgesellerle dolu yayın akışımızı takip edin; canlı TV ve canlı radyomuzu
                    dilediğiniz an, dilediğiniz yerden izleyin, dinleyin.
                </p>
                <div class="mt-8 flex flex-wrap gap-4">
                    <a href="{{ route('live.tv') }}"
                       class="inline-flex items-center gap-2 rounded-full bg-rose-600 px-6 py-3 font-semibold text-white shadow-lg shadow-rose-600/30 transition hover:bg-rose-500">
                        Canlı TV İzle
                    </a>
                    <a href="{{ route('live.radio') }}"
                       class="inline-flex items-center gap-2 rounded-full bg-white/5 px-6 py-3 font-semibold text-white ring-1 ring-inset ring-white/10 transition hover:bg-white/10">
                        Canlı Radyo Dinle
                    </a>
                </div>
            </div>

            <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-6 shadow-2xl">
                <h2 class="flex items-center gap-2 text-sm font-semibold uppercase tracking-wider text-slate-400">
                    <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                    Bugünün Yayın Akışı
                </h2>
                <ul class="mt-4 divide-y divide-white/5">
                    @forelse ($todaySchedule as $item)
                        <li class="flex items-center justify-between py-3">
                            <div>
                                <p class="font-medium text-white">{{ $item->program->name }}</p>
                                <p class="text-xs text-slate-500">{{ $item->program->categories->pluck('name')->join(', ') }}</p>
                            </div>
                            <span class="rounded-full bg-white/5 px-3 py-1 text-sm font-semibold text-rose-300">
                                {{ \Illuminate\Support\Carbon::parse($item->start_time)->format('H:i') }}
                            </span>
                        </li>
                    @empty
                        <li class="py-6 text-center text-sm text-slate-500">Bugün için planlanmış bir yayın bulunmuyor.</li>
                    @endforelse
                </ul>
                <a href="{{ route('schedule.index') }}" class="mt-4 inline-block text-sm font-semibold text-rose-400 hover:text-rose-300">
                    Tüm yayın akışını görüntüle &rarr;
                </a>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 pb-24 sm:px-6 lg:px-8">
        <div class="mb-8 flex items-end justify-between">
            <div>
                <h2 class="text-2xl font-bold text-white">Öne Çıkan Programlar</h2>
                <p class="mt-1 text-slate-400">Kanalımızın en sevilen programlarını keşfedin.</p>
            </div>
            <a href="{{ route('programs.index') }}" class="text-sm font-semibold text-rose-400 hover:text-rose-300">
                Tümünü Gör &rarr;
            </a>
        </div>

        <div class="grid grid-cols-2 gap-6 sm:grid-cols-3 lg:grid-cols-4">
            @forelse ($featuredPrograms as $program)
                <a href="{{ route('programs.show', $program) }}" class="group">
                    <div class="aspect-[3/4] overflow-hidden rounded-xl bg-slate-900 ring-1 ring-white/10">
                        @if ($program->cover_image)
                            <img src="{{ asset('storage/' . $program->cover_image) }}" alt="{{ $program->name }}"
                                 class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                        @else
                            <div class="flex h-full w-full items-center justify-center text-4xl font-black text-slate-700">
                                {{ mb_substr($program->name, 0, 1) }}
                            </div>
                        @endif
                    </div>
                    <p class="mt-3 font-semibold text-white group-hover:text-rose-300">{{ $program->name }}</p>
                    @if ($program->categories->isNotEmpty())
                        <p class="text-xs text-slate-500">{{ $program->categories->pluck('name')->join(', ') }}</p>
                    @endif
                </a>
            @empty
                <p class="col-span-full text-slate-500">Henüz program eklenmedi. Admin panelden program ekleyebilirsiniz.</p>
            @endforelse
        </div>
    </section>
@endsection
