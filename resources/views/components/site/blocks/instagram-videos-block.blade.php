@props([
    'block' => [],
    'videos' => collect(),
])

@php
    $showTitle = $block['show_title'] ?? true;
    $title = $block['title'] ?? 'Instagram Videolarımız';
    $subtitle = $block['subtitle'] ?? null;
    $displayVariant = $block['display_variant'] ?? 'horizontal_carousel';
    $showArrows = $block['show_arrows'] ?? true;
    $id = 'ig-block-' . ($block['uuid'] ?? \Illuminate\Support\Str::random(8));

    $videos = $videos
        ->filter(fn ($video) => filled($video->cover_image_url))
        ->unique(function ($video) {
            if (! empty($video->shortcode)) {
                return $video->shortcode;
            }
            if (preg_match('#instagram\.com/(reel|p|tv)/([A-Za-z0-9_-]+)#i', (string) $video->permalink, $m)) {
                return $m[2];
            }
            return $video->permalink;
        })
        ->unique('id')
        ->values();

    $isShelf = ($displayVariant === 'horizontal_carousel');

    $responsive = \App\Services\Home\HomepageBlockRegistry::resolveResponsiveSettings($block);

    $columns = max(1, min(8, (int) ($responsive['desktop']['columns'] ?? 4)));
    $columnsTablet = max(1, min(6, (int) ($responsive['tablet']['columns'] ?? 3)));
    $columnsMobile = max(1, min(4, (int) ($responsive['mobile']['columns'] ?? 2)));

    $gapRaw = $responsive['desktop']['gap_size'] ?? $block['gap_size'] ?? $block['gap'] ?? 'md';
    $gapPx = match($gapRaw) {
        1 => 4,
        2 => 8,
        3 => 12,
        4 => 16,
        5 => 20,
        6 => 24,
        default => \App\Services\Home\HomepageBlockRegistry::resolveGapPx($gapRaw),
    };

    $layout = \App\Services\Home\HomepageBlockRegistry::resolveSectionLayout($block);
    $colors = \App\Services\Home\HomepageBlockRegistry::resolveSectionColors($block);
@endphp

@if ($videos->isNotEmpty())
    <style>
        #{{ $id }} .ig-reel-container::-webkit-scrollbar {
            display: none !important;
            width: 0 !important;
            height: 0 !important;
        }
        #{{ $id }} .ig-reel-container {
            -ms-overflow-style: none !important;
            scrollbar-width: none !important;
        }
        #{{ $id }} .ig-reel-card {
            flex: 0 0 calc((100% - (var(--gap-px) * (var(--cols-mob) - 1))) / var(--cols-mob)) !important;
            max-width: calc((100% - (var(--gap-px) * (var(--cols-mob) - 1))) / var(--cols-mob)) !important;
            width: calc((100% - (var(--gap-px) * (var(--cols-mob) - 1))) / var(--cols-mob)) !important;
        }
        #{{ $id }} .ig-reel-grid {
            display: grid !important;
            grid-template-columns: repeat(var(--cols-mob), minmax(0, 1fr)) !important;
            gap: var(--gap-px) !important;
        }
        @media (min-width: 640px) {
            #{{ $id }} .ig-reel-card {
                flex: 0 0 calc((100% - (var(--gap-px) * (var(--cols-tab) - 1))) / var(--cols-tab)) !important;
                max-width: calc((100% - (var(--gap-px) * (var(--cols-tab) - 1))) / var(--cols-tab)) !important;
                width: calc((100% - (var(--gap-px) * (var(--cols-tab) - 1))) / var(--cols-tab)) !important;
            }
            #{{ $id }} .ig-reel-grid {
                grid-template-columns: repeat(var(--cols-tab), minmax(0, 1fr)) !important;
                gap: var(--gap-px) !important;
            }
        }
        @media (min-width: 1024px) {
            #{{ $id }} .ig-reel-card {
                flex: 0 0 calc((100% - (var(--gap-px) * (var(--cols-desk) - 1))) / var(--cols-desk)) !important;
                max-width: calc((100% - (var(--gap-px) * (var(--cols-desk) - 1))) / var(--cols-desk)) !important;
                width: calc((100% - (var(--gap-px) * (var(--cols-desk) - 1))) / var(--cols-desk)) !important;
            }
            #{{ $id }} .ig-reel-grid {
                grid-template-columns: repeat(var(--cols-desk), minmax(0, 1fr)) !important;
                gap: var(--gap-px) !important;
            }
        }
    </style>

    <section class="relative py-6 sm:py-10 overflow-hidden {{ $layout['shell_class'] }}" style="{{ $layout['shell_style'] }} {{ $colors['shell_style'] }}">
        <div class="px-4 sm:px-6 lg:px-8 {{ $layout['inner_class'] }}" style="{{ $layout['inner_style'] }}">
            {{-- Section Header --}}
            @if ($showTitle && ! empty($title))
                <div class="mb-5 flex items-center justify-between gap-4 pb-3.5">
                    <div>
                        <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-white flex items-center gap-2.5">
                            <span class="inline-flex items-center justify-center h-8 w-8 rounded-xl bg-gradient-to-tr from-amber-500 via-rose-500 to-purple-600 text-white shadow-md">
                                <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                                    <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                                </svg>
                            </span>
                            <span>{{ $title }}</span>
                        </h2>
                        @if (! empty($subtitle))
                            <p class="mt-1 text-xs sm:text-sm text-slate-400 font-normal">{{ $subtitle }}</p>
                        @endif
                    </div>

                    <a href="{{ route('instagram-videos.index') }}" class="inline-flex items-center text-xs font-semibold text-rose-400 hover:text-rose-300 transition-colors group">
                        <span>Tümünü Gör</span>
                        <svg class="w-4 h-4 ml-1 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            @endif

            {{-- Slider / Grid Container --}}
            <div id="{{ $id }}"
                 x-data="{
                     canScrollLeft: false,
                     canScrollRight: true,
                     updateNav() {
                         const el = this.$refs.sliderContainer;
                         if (!el) return;
                         this.canScrollLeft = el.scrollLeft > 4;
                         this.canScrollRight = el.scrollLeft + el.clientWidth < el.scrollWidth - 4;
                     },
                     scrollLeft() {
                         const el = this.$refs.sliderContainer;
                         if (!el) return;
                         el.scrollBy({ left: -(el.clientWidth * 0.75), behavior: 'smooth' });
                     },
                     scrollRight() {
                         const el = this.$refs.sliderContainer;
                         if (!el) return;
                         el.scrollBy({ left: el.clientWidth * 0.75, behavior: 'smooth' });
                     }
                 }"
                 x-init="updateNav()"
                 class="relative">

                @if ($isShelf && $showArrows)
                    {{-- Nav Arrows --}}
                    <button type="button"
                            x-show="canScrollLeft"
                            x-cloak
                            @click="scrollLeft()"
                            class="absolute -left-3 top-1/2 -translate-y-1/2 z-20 w-10 h-10 rounded-full bg-slate-900/95 text-white flex items-center justify-center border border-slate-700 shadow-2xl hover:bg-slate-800 transition-all">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </button>

                    <button type="button"
                            x-show="canScrollRight"
                            x-cloak
                            @click="scrollRight()"
                            class="absolute -right-3 top-1/2 -translate-y-1/2 z-20 w-10 h-10 rounded-full bg-slate-900/95 text-white flex items-center justify-center border border-slate-700 shadow-2xl hover:bg-slate-800 transition-all">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                @endif

                @if ($isShelf)
                    {{-- Horizontal Shelf Track --}}
                    <div x-ref="sliderContainer"
                         @scroll.debounce.50ms="updateNav()"
                         class="ig-reel-container flex items-stretch justify-start overflow-x-auto scroll-smooth py-1"
                         style="gap: {{ $gapPx }}px; --cols-mob: {{ $columnsMobile }}; --cols-tab: {{ $columnsTablet }}; --cols-desk: {{ $columns }}; --gap-px: {{ $gapPx }}px;">
                        @foreach ($videos as $video)
                            <div class="shrink-0 ig-reel-card">
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
                        @endforeach
                    </div>
                @else
                    {{-- Grid Layout --}}
                    <div class="ig-reel-grid"
                         style="--cols-mob: {{ $columnsMobile }}; --cols-tab: {{ $columnsTablet }}; --cols-desk: {{ $columns }}; --gap-px: {{ $gapPx }}px;">
                        @foreach ($videos as $video)
                            <div class="w-full flex justify-start">
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
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </section>
@endif
