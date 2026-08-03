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
            <h1 class="mt-6 text-2xl font-black text-white">{{ $settings->radio_name ?? $settings->site_name . ' Radyo' }}</h1>
            <p class="mt-2 text-slate-400">Canlı radyo yayınımızı dinlemek için oynat tuşuna basın.</p>

            <div class="mt-8">
                @if ($settings->radio_stream_url)
                    <audio controls class="w-full" preload="none">
                        <source src="{{ $settings->radio_stream_url }}">
                        Tarayıcınız ses oynatmayı desteklemiyor.
                    </audio>
                @else
                    <p class="text-sm text-slate-500">Radyo yayın linki henüz tanımlanmadı. Admin panelden Site Ayarları'na ekleyebilirsiniz.</p>
                @endif
            </div>
        </div>
    </section>
@endsection
