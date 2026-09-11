@extends('layouts.app')

@php
    $pageTitle = ($settings['show_page_title'] ?? true) ? ($settings['page_title'] ?? 'DOST TV YouTube Kanalları') : 'DOST TV YouTube Kanalları';
    $showSubtitle = ($settings['show_page_subtitle'] ?? true) && ! empty($settings['page_subtitle']);
    $subtitle = $settings['page_subtitle'] ?? null;
    $showVideoSectionTitle = ($settings['show_video_section_title'] ?? true) && ! empty($settings['video_section_title']);
    $videoSectionTitle = $settings['video_section_title'] ?? 'YouTube Videolarımız';
@endphp

@section('title', $pageTitle)
@section('description', $subtitle ?: 'DOST TV resmi YouTube kanalları ve güncel video arşivi.')

@section('content')
    <div class="bg-slate-950 py-10 sm:py-14 min-h-[75vh]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            {{-- CMS Page Header --}}
            @if (($settings['show_page_title'] ?? true) || $showSubtitle)
                <div class="mb-8 sm:mb-12 border-b border-slate-800/80 pb-6">
                    @if ($settings['show_page_title'] ?? true)
                        <h1 class="text-2xl sm:text-4xl font-extrabold text-white tracking-tight flex items-center gap-3">
                            <span class="inline-flex items-center justify-center h-9 w-9 sm:h-10 sm:w-10 rounded-full bg-rose-600/20 text-rose-500 ring-1 ring-rose-500/30">
                                <svg class="w-5 h-5 sm:w-6 sm:h-6 fill-current" viewBox="0 0 24 24">
                                    <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                                </svg>
                            </span>
                            <span>{{ $settings['page_title'] ?? 'DOST TV YouTube Kanalları' }}</span>
                        </h1>
                    @endif

                    @if ($showSubtitle)
                        <p class="mt-2 text-sm sm:text-base text-slate-400 max-w-3xl leading-relaxed">
                            {{ $subtitle }}
                        </p>
                    @endif
                </div>
            @endif

            {{-- Top Channels Horizontal Strip (~5 channels visible on desktop, scrollable for all) --}}
            @if ($channels->isNotEmpty())
                <div class="mb-12">
                    <x-site.blocks.youtube-channel-shelf-block
                        :block="['show_title' => false, 'show_arrows' => true, 'section_width' => 'full']"
                        :channels="$channels"
                    />
                </div>
            @else
                <div class="text-center py-10 bg-slate-900/40 rounded-2xl border border-slate-800/80 mb-10">
                    <p class="text-slate-400 text-sm">Henüz kayıtlı aktif YouTube kanalı bulunmamaktadır.</p>
                </div>
            @endif

            {{-- Video Section Main Header & Category Shelves --}}
            @if (! empty($categoryShelves) || $showVideoSectionTitle)
                <div class="pt-6 border-t border-slate-900/80">
                    @if ($showVideoSectionTitle)
                        <div class="mb-8">
                            <h2 class="text-xl sm:text-3xl font-extrabold text-white tracking-tight flex items-center gap-2.5">
                                <span class="h-6 w-1.5 rounded-full bg-rose-600"></span>
                                <span>{{ $videoSectionTitle }}</span>
                            </h2>
                        </div>
                    @endif

                    {{-- Category Live Video Shelves --}}
                    @if (! empty($categoryShelves))
                        <div class="space-y-12">
                            @foreach ($categoryShelves as $shelf)
                                <div class="space-y-4">
                                    @if ($shelf['show_title'] && ! empty($shelf['title']))
                                        <div class="flex items-center justify-between border-b border-slate-800/60 pb-3">
                                            <h3 class="text-lg sm:text-xl font-bold text-slate-100 flex items-center gap-2">
                                                <span class="h-2 w-2 rounded-full bg-rose-500"></span>
                                                <span>{{ $shelf['title'] }}</span>
                                            </h3>
                                        </div>
                                    @endif

                                    <x-site.blocks.shelf-grid
                                        :items="$shelf['episodes']"
                                        :display-variant="$shelf['view_mode']"
                                        :desktop-columns="$shelf['columns']"
                                        :row-count="$shelf['rows']"
                                        :show-arrows="$shelf['show_arrows']"
                                        :gap-size="$shelf['gap_size']"
                                        card-component="site.video-card"
                                    />
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-12 bg-slate-900/30 rounded-2xl border border-slate-900">
                            <p class="text-slate-500 text-sm">Gösterilecek kategori video rafı bulunmuyor. CMS üzerinden kategori rafları ekleyebilirsiniz.</p>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
@endsection
