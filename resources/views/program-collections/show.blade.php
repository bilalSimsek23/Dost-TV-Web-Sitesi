@extends('layouts.app')

@section('title', $collection->name . ' - Dost TV')

@section('content')
    <x-site.admin-edit-bar :program-collection="$collection" />
    @php
        $resolved = \App\Support\CollectionPageDesignResolver::resolve($collection, 'program');
        $showDesc = $resolved['show_description'];
    @endphp

    <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12 sm:py-16 min-h-[60vh]">
        <div class="mb-8 border-b border-white/10 pb-6">
            <span class="inline-block rounded-full bg-rose-500/10 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-rose-400 mb-2 border border-rose-500/20">
                Program Koleksiyonu
            </span>
            <h1 class="text-3xl font-black text-white sm:text-4xl tracking-tight">{{ $collection->name }}</h1>
            @if ($showDesc && filled($collection->description))
                <p class="mt-2 text-base text-slate-400 max-w-3xl">{{ $collection->description }}</p>
            @endif
        </div>

        @if ($programs->isEmpty())
            <div class="rounded-2xl border border-dashed border-white/10 p-12 text-center text-slate-500 bg-white/5">
                <p>Henüz bu koleksiyonda gösterilecek program bulunmuyor.</p>
            </div>
        @else
            @if ($resolved['display_variant'] === 'carousel')
                <div class="w-full">
                    <x-site.blocks.shelf-grid
                        :items="$programs"
                        :block="$collection->resolvePublicSettings()"
                        card-component="site.program-card"
                    />
                </div>
            @else
                <div class="{{ $resolved['grid_class'] }}">
                    @foreach ($programs as $item)
                        <div class="w-full min-w-0">
                            <a href="{{ route('programs.show', $item) }}" class="block w-full">
                                <x-site.program-card :title="$item->name" :cover-image="$item->cover_image" />
                            </a>
                        </div>
                    @endforeach
                </div>
            @endif

            @if ($programs instanceof \Illuminate\Pagination\LengthAwarePaginator)
                <div class="mt-12">
                    {{ $programs->links() }}
                </div>
            @endif
        @endif
    </section>
@endsection
