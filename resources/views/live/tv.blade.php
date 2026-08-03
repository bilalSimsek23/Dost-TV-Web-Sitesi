@extends('layouts.app')

@section('title', 'Canlı TV İzle - Dost TV')

@section('content')
    <section class="mx-auto max-w-5xl px-4 py-16 sm:px-6 lg:px-8">
        <div class="flex items-center gap-2">
            <span class="relative flex h-3 w-3">
                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-rose-500 opacity-75"></span>
                <span class="relative inline-flex h-3 w-3 rounded-full bg-rose-500"></span>
            </span>
            <span class="text-sm font-semibold uppercase tracking-wider text-rose-400">Canlı Yayın</span>
        </div>
        <h1 class="mt-3 text-3xl font-black text-white">{{ $settings->site_name }} Canlı TV</h1>

        <div class="mt-8 aspect-video overflow-hidden rounded-2xl bg-black ring-1 ring-white/10">
            @if (! $settings->live_tv_url)
                <div class="flex h-full w-full items-center justify-center text-slate-500">
                    Canlı yayın linki henüz tanımlanmadı. Admin panelden Site Ayarları'na ekleyebilirsiniz.
                </div>
            @elseif ($settings->live_tv_type === 'hls')
                <video id="hls-player" class="h-full w-full" controls autoplay muted data-src="{{ $settings->live_tv_url }}"></video>
                @vite('resources/js/live-tv.js')
            @else
                <iframe src="{{ $settings->live_tv_url }}" class="h-full w-full" allowfullscreen
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"></iframe>
            @endif
        </div>
    </section>
@endsection
