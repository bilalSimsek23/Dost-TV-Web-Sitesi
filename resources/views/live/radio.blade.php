@extends('layouts.app')

@section('title', 'Canlı Radyo Dinle - Dost TV')

@section('content')
    <section class="mx-auto max-w-2xl px-4 py-16 sm:px-6 lg:px-8">
        <div class="rounded-2xl border border-white/10 bg-gradient-to-br from-white/[0.06] to-transparent p-10 text-center">
            <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-gradient-to-br from-rose-500 to-amber-400">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                     class="h-10 w-10 text-slate-950">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 9v10.5a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V15a2.25 2.25 0 012.25-2.25h1.5m3.75-3.75V19.5a2.25 2.25 0 002.25 2.25h.75a2.25 2.25 0 002.25-2.25V9m-7.5 0h7.5m-7.5 0a2.25 2.25 0 012.25-2.25h3a2.25 2.25 0 012.25 2.25m-7.5 0V6a2.25 2.25 0 012.25-2.25h3A2.25 2.25 0 0116.5 6v3" />
                </svg>
            </div>

            {{-- Dynamic Title --}}
            <h1 class="mt-6 text-2xl font-black text-white">
                {{ $settings->radio_name ?: ($settings->site_name . ' Canlı Radyo') }}
            </h1>

            {{-- Dynamic Description --}}
            @if (!empty($settings->radio_description))
                <p class="mt-2 text-slate-400 max-w-lg mx-auto leading-relaxed">
                    {{ $settings->radio_description }}
                </p>
            @else
                <p class="mt-2 text-slate-400">Canlı radyo yayınımızı dinlemek için oynat tuşuna basın.</p>
            @endif

            {{-- Player or State Box --}}
            @if (! $settings->radio_is_public)
                {{-- Public Access Disabled --}}
                <div class="mt-8 rounded-xl bg-slate-900/60 border border-white/10 p-6 text-center">
                    <p class="text-sm font-medium text-slate-300">Canlı radyo yayını şu anda public erişime kapalıdır.</p>
                </div>
            @elseif (! $settings->radio_is_active)
                {{-- Maintenance Mode --}}
                <div class="mt-8 rounded-xl bg-slate-900/60 border border-amber-500/20 p-6 text-center">
                    <p class="text-sm font-medium text-amber-300">{{ $settings->radio_maintenance_message ?: 'Canlı radyo yayınımız şu anda kullanılamıyor. Lütfen daha sonra tekrar deneyin.' }}</p>
                </div>
            @elseif (! $settings->radio_stream_url)
                {{-- URL Unconfigured --}}
                <div class="mt-8 text-center">
                    <p class="text-sm text-slate-500">Radyo yayın linki henüz tanımlanmadı.</p>
                </div>
            @else
                {{-- Audio Player with Backup & Error Container --}}
                <div class="mt-8 space-y-3" x-data="{
                    triedBackup: false,
                    hasError: false,
                    backupSrc: '{{ $settings->radio_backup_url }}',
                    errorMsg: '{{ $settings->radio_error_message ?: 'Radyo akışı şu anda yüklenemiyor. Lütfen daha sonra tekrar deneyin.' }}',
                    handleError() {
                        const audio = this.$refs.audioPlayer;
                        if (!this.triedBackup && this.backupSrc && this.backupSrc.trim() !== '') {
                            this.triedBackup = true;
                            audio.src = this.backupSrc;
                            audio.load();
                            audio.play().catch(() => {});
                        } else {
                            this.hasError = true;
                        }
                    }
                }">
                    <audio x-ref="audioPlayer" controls class="w-full" preload="none" x-on:error="handleError()">
                        <source src="{{ $settings->radio_stream_url }}">
                        Tarayıcınız ses oynatmayı desteklemiyor.
                    </audio>
                    <div x-show="hasError" x-cloak class="rounded-xl border border-rose-500/30 bg-rose-500/10 p-3 text-xs font-semibold text-rose-300">
                        <span x-text="errorMsg"></span>
                    </div>
                </div>
            @endif
        </div>
    </section>
@endsection
