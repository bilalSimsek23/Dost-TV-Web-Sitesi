@extends('layouts.app')

@section('title', 'DOST TV Instagram Videoları & Reels')
@section('description', 'DOST TV resmi Instagram hesabımızdan yayınlanan özel sohbetler, kısa videolar ve Reels içerikleri.')

@php
    $validVideos = $videos->getCollection()->filter(fn ($v) => filled($v->cover_image_url));
@endphp

@section('content')
    <div class="bg-slate-950 py-10 sm:py-14 min-h-[75vh]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Header --}}
            <div class="mb-8 sm:mb-12 border-b border-slate-800/80 pb-6">
                <h1 class="text-2xl sm:text-4xl font-extrabold text-white tracking-tight flex items-center gap-3">
                    <span class="inline-flex items-center justify-center h-9 w-9 sm:h-10 sm:w-10 rounded-xl bg-gradient-to-tr from-amber-500 via-rose-500 to-purple-600 text-white shadow-lg ring-1 ring-white/20">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6 fill-current" viewBox="0 0 24 24">
                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                        </svg>
                    </span>
                    <span>DOST TV Instagram Videoları & Reels</span>
                </h1>
                <p class="mt-2 text-sm sm:text-base text-slate-400 max-w-3xl leading-relaxed">
                    DOST TV resmi Instagram hesabımızdan yayınlanan özel sohbetler, kısa videolar ve Reels içerikleri.
                </p>
            </div>

            {{-- 9:16 Reels Tile Grid --}}
            @if ($validVideos->isNotEmpty())
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4 sm:gap-5">
                    @foreach ($validVideos as $video)
                        <div class="w-full flex justify-start sm:justify-center">
                            <div class="w-full max-w-[220px]">
                                <a href="{{ $video->permalink }}" target="_blank" rel="noopener noreferrer" class="group relative block w-full aspect-[9/16] bg-slate-900 rounded-xl overflow-hidden shadow-lg transition-transform duration-300 hover:scale-[1.02] border border-slate-800/80">
                                    <img src="{{ $video->cover_image_url }}" alt="DOST TV Reel" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" loading="lazy">

                                    {{-- Simple Gradient Overlay --}}
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-40 group-hover:opacity-60 transition-opacity"></div>

                                    {{-- Minimal Center Play Icon Overlay --}}
                                    <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                                        <div class="w-10 h-10 rounded-full bg-black/50 text-white flex items-center justify-center backdrop-blur-sm border border-white/20 group-hover:scale-110 group-hover:bg-pink-600/80 transition-all duration-300">
                                            <svg class="w-5 h-5 fill-current translate-x-0.5" viewBox="0 0 24 24">
                                                <path d="M8 5v14l11-7z"/>
                                            </svg>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Pagination Links --}}
                <div class="mt-10">
                    {{ $videos->links() }}
                </div>
            @else
                <div class="text-center py-16 bg-slate-900/40 rounded-2xl border border-slate-800/80">
                    <div class="w-16 h-16 rounded-2xl bg-slate-800/80 text-slate-500 flex items-center justify-center mx-auto mb-4 border border-slate-700/50">
                        <svg class="w-8 h-8 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-base font-bold text-white mb-1">Henüz Instagram Görsel İçeriği Eklenmemiş</h3>
                    <p class="text-slate-400 text-sm max-w-md mx-auto">
                        Yakında DOST TV resmi Instagram hesabımızdan yayınlanan Reels ve özel video kesitleri burada listelenecektir.
                    </p>
                </div>
            @endif

        </div>
    </div>
@endsection
