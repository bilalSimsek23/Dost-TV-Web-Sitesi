@extends('layouts.app')

@section('title', $collection->name . ' - Dost TV')

@section('content')
    <x-site.admin-edit-bar :video-collection="$collection" />
    @php
        $resolved = \App\Support\CollectionPageDesignResolver::resolve($collection, 'video');
        $showDesc = $resolved['show_description'];
    @endphp

    <div class="py-12 sm:py-16 theme-bg-main min-h-[60vh] theme-text-main">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-8 border-b border-white/10 pb-6">
                <span class="inline-block rounded-full bg-rose-500/10 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-rose-400 mb-2 border border-rose-500/20">
                    Video Koleksiyonu
                </span>
                <h1 class="text-3xl font-extrabold text-white sm:text-4xl tracking-tight">
                    {{ $collection->name }}
                </h1>
                @if ($showDesc && filled($collection->description))
                    <p class="mt-2 text-base text-slate-400 max-w-3xl">
                        {{ $collection->description }}
                    </p>
                @endif
            </div>

            {{-- Kategori & Program Filtre Sekmeleri --}}
            @if ($categories->isNotEmpty() || $programs->isNotEmpty())
                <div class="mb-8 flex flex-wrap items-center gap-2 border-b border-white/10 pb-6">
                    <a href="{{ request()->url() }}"
                       class="px-4 py-2 text-xs font-semibold rounded-full border transition-all duration-200 {{ !request('category') && !request('program') ? 'bg-rose-600 text-white border-rose-500 shadow-lg shadow-rose-600/20' : 'bg-slate-900/80 text-slate-300 border-white/15 hover:border-white/40 hover:text-white' }}">
                        TÜMÜ ({{ $totalCount }})
                    </a>

                    @foreach ($categories as $cat)
                        @if (($cat->filtered_count ?? 0) > 0)
                            <a href="{{ request()->fullUrlWithQuery(['category' => $cat->slug, 'program' => null, 'page' => 1]) }}"
                               class="px-4 py-2 text-xs font-semibold rounded-full border transition-all duration-200 {{ request('category') === $cat->slug ? 'bg-rose-600 text-white border-rose-500 shadow-lg shadow-rose-600/20' : 'bg-slate-900/80 text-slate-300 border-white/15 hover:border-white/40 hover:text-white' }}">
                                {{ $cat->name }} ({{ $cat->filtered_count }})
                            </a>
                        @endif
                    @endforeach

                    @foreach ($programs as $prog)
                        @if (($prog->filtered_count ?? 0) > 0 && $categories->isEmpty())
                            <a href="{{ request()->fullUrlWithQuery(['program' => $prog->slug, 'category' => null, 'page' => 1]) }}"
                               class="px-4 py-2 text-xs font-semibold rounded-full border transition-all duration-200 {{ request('program') === $prog->slug ? 'bg-rose-600 text-white border-rose-500 shadow-lg shadow-rose-600/20' : 'bg-slate-900/80 text-slate-300 border-white/15 hover:border-white/40 hover:text-white' }}">
                                {{ $prog->name }} ({{ $prog->filtered_count }})
                            </a>
                        @endif
                    @endforeach
                </div>
            @endif

            @if ($episodes->isEmpty())
                <div class="rounded-2xl border border-dashed border-white/10 p-12 text-center text-slate-500 bg-white/5">
                    <p>Bu kategoride veya koleksiyonda henüz video bulunmuyor.</p>
                </div>
            @else
                @if ($resolved['display_variant'] === 'carousel')
                    <div class="w-full">
                        <x-site.blocks.shelf-grid
                            :items="collect($episodes->items())"
                            :block="$collection->resolvePublicSettings()"
                            card-component="site.video-card"
                        />
                    </div>
                @else
                    <div class="{{ $resolved['grid_class'] }}">
                        @foreach ($episodes as $item)
                            <div class="w-full min-w-0">
                                <x-site.video-card :episode="$item" />
                            </div>
                        @endforeach
                    </div>
                @endif

                @if ($episodes instanceof \Illuminate\Pagination\LengthAwarePaginator && $episodes->hasPages())
                    <div class="mt-12 flex justify-center border-t border-white/10 pt-8">
                        {{ $episodes->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
@endsection
