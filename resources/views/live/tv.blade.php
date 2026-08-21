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

        {{-- Dynamic Title --}}
        <h1 class="mt-3 text-3xl font-black text-white">
            {{ $settings->live_tv_title ?: ($settings->site_name . ' Canlı TV') }}
        </h1>

        {{-- Dynamic Short Description --}}
        @if (!empty($settings->live_tv_description))
            <p class="mt-2 text-slate-400 leading-relaxed max-w-3xl">
                {{ $settings->live_tv_description }}
            </p>
        @endif

        {{-- Player or State Box --}}
        @if (! $settings->live_tv_is_public)
            {{-- Public Access Disabled --}}
            <div class="mt-8 aspect-video overflow-hidden rounded-2xl bg-slate-900/60 border border-white/10 p-8 flex flex-col items-center justify-center text-center backdrop-blur shadow-2xl">
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-rose-500/10 text-rose-400 mb-4 ring-1 ring-rose-500/20">
                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                    </svg>
                </div>
                <h2 class="text-xl font-bold text-white">Canlı Yayın Genel Erişime Kapalıdır</h2>
                <p class="mt-2 text-sm text-slate-400 max-w-md">Canlı televizyon yayını şu anda public erişime kapalı tutulmaktadır. Lütfen daha sonra tekrar kontrol ediniz.</p>
            </div>
        @elseif (! $settings->live_tv_is_active)
            {{-- Maintenance Mode --}}
            <div class="mt-8 aspect-video overflow-hidden rounded-2xl bg-slate-900/60 border border-white/10 p-8 flex flex-col items-center justify-center text-center backdrop-blur shadow-2xl">
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-500/10 text-amber-400 mb-4 ring-1 ring-amber-500/20">
                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.67 2.67 0 0021 17.25l-5.83-5.83M11.42 15.17l2.49-2.49a3.5 3.5 0 10-4.95-4.95l-2.5 2.49m4.96 4.95l-5.83 5.83A2.67 2.67 0 012 17.25l5.83-5.83" />
                    </svg>
                </div>
                <h2 class="text-xl font-bold text-white">Yayın Bakımda</h2>
                <p class="mt-2 text-sm text-slate-400 max-w-md">{{ $settings->live_tv_maintenance_message ?: 'Canlı yayınımız şu anda kullanılamıyor. Lütfen daha sonra tekrar deneyin.' }}</p>
            </div>
        @elseif (! $settings->live_tv_url)
            {{-- URL Unconfigured --}}
            <div class="mt-8 aspect-video overflow-hidden rounded-2xl bg-black ring-1 ring-white/10 flex h-full w-full items-center justify-center text-slate-500">
                Canlı yayın linki henüz tanımlanmadı.
            </div>
        @elseif ($settings->live_tv_type === 'hls')
            {{-- HLS Video Player with Backup & Error Container --}}
            <div class="mt-8 aspect-video relative overflow-hidden rounded-2xl bg-black ring-1 ring-white/10">
                <video id="hls-player" class="h-full w-full" controls autoplay muted
                       data-src="{{ $settings->live_tv_url }}"
                       data-backup-src="{{ $settings->live_tv_backup_url }}"
                       data-error-msg="{{ $settings->live_tv_error_message ?: 'Canlı yayın şu anda yüklenemiyor. Lütfen daha sonra tekrar deneyin.' }}"></video>

                <div id="hls-error-container" class="hidden absolute inset-0 flex flex-col items-center justify-center p-6 text-center bg-slate-950/90 backdrop-blur text-slate-300 z-10">
                    <svg class="h-10 w-10 text-rose-500 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                    <p id="hls-error-text" class="text-sm font-semibold text-white max-w-md"></p>
                </div>

                @vite('resources/js/live-tv.js')
            </div>
        @else
            {{-- iFrame / Embedded Player --}}
            <div class="mt-8 aspect-video overflow-hidden rounded-2xl bg-black ring-1 ring-white/10">
                <iframe src="{{ $settings->live_tv_url }}" class="h-full w-full" allowfullscreen
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"></iframe>
            </div>
        @endif

        {{-- Dost FM Canlı Radyo Geçiş Butonu (Eğer radio_is_public aktifse) --}}
        @if ($settings->radio_is_public)
            <div class="mt-6 flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-white/10 bg-slate-900/60 p-4 sm:px-6 backdrop-blur">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-500/15 text-amber-400">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.114 5.636a9 9 0 010 12.728M16.463 8.288a5.25 5.25 0 010 7.424M6.75 8.25l4.72-4.72a.75.75 0 011.28.53v15.88a.75.75 0 01-1.28.53l-4.72-4.72H4.51c-.88 0-1.704-.507-1.938-1.354A9.01 9.01 0 012.25 12c0-.83.112-1.633.322-2.396C2.806 8.757 3.63 8.25 4.51 8.25H6.75z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-white">{{ $settings->radio_name ?: 'Dost FM Canlı Radyo' }}</h3>
                        <p class="text-xs text-slate-400">{{ $settings->radio_description ?: 'Gönüllerin sesi Dost FM yayınını canlı dinleyin.' }}</p>
                    </div>
                </div>

                <a href="{{ route('live.radio') }}"
                   class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-amber-500/20 to-amber-600/20 border border-amber-500/30 px-4 py-2.5 text-sm font-semibold text-amber-300 shadow-lg transition hover:bg-amber-500 hover:text-slate-950 hover:border-amber-500">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Dost FM Canlı</span>
                    <span class="text-xs">&rarr;</span>
                </a>
            </div>
        @endif
    </section>
@endsection
