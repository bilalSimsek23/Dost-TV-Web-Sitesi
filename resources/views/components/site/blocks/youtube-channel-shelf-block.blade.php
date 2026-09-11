@props([
    'block' => [],
    'channels' => collect(),
])

@php
    $showTitle = $block['show_title'] ?? true;
    $title = $block['title'] ?? 'YouTube Kanallarımız';
    $subtitle = $block['subtitle'] ?? null;
    $showArrows = $block['show_arrows'] ?? true;
    $id = 'yt-shelf-' . ($block['uuid'] ?? \Illuminate\Support\Str::random(8));
    $layout = \App\Services\Home\HomepageBlockRegistry::resolveSectionLayout($block);
    $colors = \App\Services\Home\HomepageBlockRegistry::resolveSectionColors($block);
@endphp

@if ($channels->isNotEmpty())
    <style>
        #{{ $id }} .carousel-scrollbar-hidden {
            scrollbar-width: none !important;
            -ms-overflow-style: none !important;
        }
        #{{ $id }} .carousel-scrollbar-hidden::-webkit-scrollbar {
            display: none !important;
            width: 0 !important;
            height: 0 !important;
        }
    </style>

    <section class="relative py-8 sm:py-10 overflow-hidden {{ $layout['shell_class'] }}" style="{{ $layout['shell_style'] }} {{ $colors['shell_style'] }}">
        <div class="px-4 sm:px-6 lg:px-8 {{ $layout['inner_class'] }}" style="{{ $layout['inner_style'] }}">
            {{-- Header --}}
            @if ($showTitle && ! empty($title))
                <div class="mb-6 flex flex-col sm:flex-row sm:items-end justify-between gap-2 pb-3.5">
                    <div>
                        <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-white flex items-center gap-2.5">
                            <span class="inline-flex items-center justify-center h-7 w-7 rounded-full bg-rose-600/20 text-rose-500 ring-1 ring-rose-500/30">
                                <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                                    <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                                </svg>
                            </span>
                            <span>{{ $title }}</span>
                        </h2>
                        @if (! empty($subtitle))
                            <p class="mt-1 text-sm text-slate-400 font-normal">{{ $subtitle }}</p>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Slider Container --}}
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
                 x-init="$nextTick(() => updateNav())"
                 class="relative group/shelf w-full">

                {{-- Left Arrow --}}
                @if ($showArrows && $channels->count() > 3)
                    <button type="button"
                            @click="scrollLeft()"
                            x-show="canScrollLeft"
                            x-cloak
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 scale-90"
                            x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-90"
                            class="absolute -left-3 sm:-left-4 top-1/2 -translate-y-1/2 z-30 flex h-10 w-10 sm:h-11 sm:w-11 items-center justify-center rounded-full bg-slate-900/90 text-white border border-slate-700/80 shadow-2xl backdrop-blur-md transition-all duration-200 hover:scale-110 hover:bg-rose-600 hover:border-rose-500 focus:outline-none"
                            aria-label="Önceki kanallar">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                @endif

                {{-- Channel Items Track --}}
                <div x-ref="sliderContainer"
                     @scroll.passive="updateNav()"
                     @resize.window.debounce.100ms.passive="updateNav()"
                     class="yt-slider-track carousel-scrollbar-hidden flex gap-4 sm:gap-6 overflow-x-auto pb-4 pt-1 snap-x snap-mandatory scroll-smooth">
                    @foreach ($channels as $channel)
                        @php
                            $logoUrl = $channel->avatar_url;
                            $hasUrl = ! empty($channel->url);
                        @endphp

                        <div class="snap-start flex-shrink-0 w-[120px] sm:w-[150px] lg:w-[170px]">
                            @if ($hasUrl)
                                <a href="{{ $channel->url }}"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   class="group flex flex-col items-center text-center p-3 rounded-2xl transition-all duration-200 hover:bg-slate-900/60 hover:shadow-xl focus:outline-none">
                                    {{-- Avatar --}}
                                    <div class="relative mb-3">
                                        <img src="{{ $logoUrl }}"
                                             alt="{{ $channel->name }}"
                                             class="w-16 h-16 sm:w-20 sm:h-20 rounded-full object-cover shadow-lg border border-slate-800 transition-all duration-200 group-hover:scale-105 group-hover:ring-2 group-hover:ring-rose-500/60 group-hover:border-rose-500/40" />
                                    </div>

                                    {{-- Name --}}
                                    <span class="text-xs sm:text-sm font-semibold text-slate-100 group-hover:text-rose-400 transition-colors line-clamp-2 leading-snug">
                                        {{ $channel->name }}
                                    </span>

                                    {{-- Handle --}}
                                    @if (! empty($channel->handle))
                                        <span class="text-[11px] text-slate-400 font-normal line-clamp-1 mt-0.5">
                                            {{ $channel->handle }}
                                        </span>
                                    @endif
                                </a>
                            @else
                                <div class="flex flex-col items-center text-center p-3 rounded-2xl opacity-80">
                                    {{-- Avatar --}}
                                    <div class="relative mb-3">
                                        <img src="{{ $logoUrl }}"
                                             alt="{{ $channel->name }}"
                                             class="w-16 h-16 sm:w-20 sm:h-20 rounded-full object-cover shadow-lg border border-slate-800" />
                                    </div>

                                    {{-- Name --}}
                                    <span class="text-xs sm:text-sm font-semibold text-slate-300 line-clamp-2 leading-snug">
                                        {{ $channel->name }}
                                    </span>

                                    {{-- Handle --}}
                                    @if (! empty($channel->handle))
                                        <span class="text-[11px] text-slate-500 font-normal line-clamp-1 mt-0.5">
                                            {{ $channel->handle }}
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                {{-- Right Arrow --}}
                @if ($showArrows && $channels->count() > 3)
                    <button type="button"
                            @click="scrollRight()"
                            x-show="canScrollRight"
                            x-cloak
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 scale-90"
                            x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-90"
                            class="absolute -right-3 sm:-right-4 top-1/2 -translate-y-1/2 z-30 flex h-10 w-10 sm:h-11 sm:w-11 items-center justify-center rounded-full bg-slate-900/90 text-white border border-slate-700/80 shadow-2xl backdrop-blur-md transition-all duration-200 hover:scale-110 hover:bg-rose-600 hover:border-rose-500 focus:outline-none"
                            aria-label="Sonraki kanallar">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                @endif
            </div>
        </div>
    </section>
@endif
