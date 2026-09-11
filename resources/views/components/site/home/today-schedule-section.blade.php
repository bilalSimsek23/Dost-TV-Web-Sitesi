@props([
    'todaySchedule' => collect(),
    'block' => [],
])

@php
    $title = $block['title'] ?? 'Bugünün Yayın Akışı';
    $showTitle = $block['show_title'] ?? true;
    $showNowBadge = $block['show_now_badge'] ?? true;
    $showNextBadge = $block['show_next_badge'] ?? true;
    $showAllLink = $block['show_all_link'] ?? true;
    $showArrows = $block['show_arrows'] ?? true;
    $autoScrollToNow = $block['auto_scroll_to_now'] ?? true;
    $bgStyle = $block['bg_style'] ?? 'dark';

    $responsive = \App\Services\Home\HomepageBlockRegistry::resolveResponsiveSettings($block);

    $bgClass = match ($bgStyle) {
        'transparent' => 'bg-transparent',
        'light' => 'bg-slate-900/40',
        default => 'bg-gradient-to-b from-slate-950 via-slate-900/50 to-slate-950',
    };

    $now = now();
    $items = collect($todaySchedule)->filter(fn ($item) => $item->program)->values();

    $findNowIndex = function ($collection) use ($now) {
        foreach ($collection as $idx => $item) {
            $start = \Illuminate\Support\Carbon::parse($item->start_time);
            $end = $item->end_time ? \Illuminate\Support\Carbon::parse($item->end_time) : null;
            if ($end ? $now->between($start, $end) : $start->lte($now)) {
                return $idx;
            }
        }
        return null;
    };

    $findNextIndex = function ($collection) use ($now) {
        foreach ($collection as $idx => $item) {
            if (\Illuminate\Support\Carbon::parse($item->start_time)->gt($now)) {
                return $idx;
            }
        }
        return null;
    };

    $nowIndex = $findNowIndex($items);
    $nextIndex = $nowIndex !== null ? $nowIndex + 1 : $findNextIndex($items);
    $scrollTarget = $nowIndex ?? $nextIndex ?? 0;

    $sectionId = 'today-schedule-' . ($block['uuid'] ?? \Illuminate\Support\Str::random(6));
    $layout = \App\Services\Home\HomepageBlockRegistry::resolveSectionLayout($block);
    $colors = \App\Services\Home\HomepageBlockRegistry::resolveSectionColors($block);

    $m = $responsive['mobile'];
    $t = $responsive['tablet'];
    $d = $responsive['desktop'];
@endphp

@if ($items->isNotEmpty())
    <style>
        #{{ $sectionId }} .dost-schedule-inner {
            padding-top: {{ $m['section_padding_top'] }}px;
            padding-bottom: {{ $m['section_padding_bottom'] }}px;
            padding-left: {{ $m['section_padding_x'] }}px;
            padding-right: {{ $m['section_padding_x'] }}px;
        }
        #{{ $sectionId }} .dost-schedule-heading {
            font-size: {{ $m['heading_size'] }}px;
        }
        #{{ $sectionId }} .dost-schedule-time {
            font-size: {{ $m['schedule_time_size'] }}px;
        }
        #{{ $sectionId }} .dost-schedule-title {
            font-size: {{ $m['schedule_program_size'] }}px;
            line-height: 1.25;
        }
        #{{ $sectionId }} .dost-schedule-item {
            width: 145px !important;
            min-width: 145px !important;
            max-width: 145px !important;
            flex: 0 0 145px !important;
            min-height: {{ $m['schedule_item_height'] }}px;
            padding: 6px {{ $m['schedule_horizontal_padding'] }}px;
            box-sizing: border-box !important;
        }
        #{{ $sectionId }} .dost-schedule-strip {
            gap: {{ $m['schedule_item_gap'] }}px;
        }

        @media (min-width: 640px) {
            #{{ $sectionId }} .dost-schedule-inner {
                padding-top: {{ $t['section_padding_top'] }}px;
                padding-bottom: {{ $t['section_padding_bottom'] }}px;
                padding-left: {{ $t['section_padding_x'] }}px;
                padding-right: {{ $t['section_padding_x'] }}px;
            }
            #{{ $sectionId }} .dost-schedule-heading {
                font-size: {{ $t['heading_size'] }}px;
            }
            #{{ $sectionId }} .dost-schedule-time {
                font-size: {{ $t['schedule_time_size'] }}px;
            }
            #{{ $sectionId }} .dost-schedule-title {
                font-size: {{ $t['schedule_program_size'] }}px;
                line-height: 1.3;
            }
            #{{ $sectionId }} .dost-schedule-item {
                width: 170px !important;
                min-width: 170px !important;
                max-width: 170px !important;
                flex: 0 0 170px !important;
                min-height: {{ $t['schedule_item_height'] }}px;
                padding: 8px {{ $t['schedule_horizontal_padding'] }}px;
            }
            #{{ $sectionId }} .dost-schedule-strip {
                gap: {{ $t['schedule_item_gap'] }}px;
            }
        }

        @media (min-width: 1024px) {
            #{{ $sectionId }} .dost-schedule-inner {
                padding-top: {{ $d['section_padding_top'] }}px;
                padding-bottom: {{ $d['section_padding_bottom'] }}px;
                padding-left: {{ $d['section_padding_x'] }}px;
                padding-right: {{ $d['section_padding_x'] }}px;
            }
            #{{ $sectionId }} .dost-schedule-heading {
                font-size: {{ $d['heading_size'] }}px;
            }
            #{{ $sectionId }} .dost-schedule-time {
                font-size: {{ $d['schedule_time_size'] }}px;
            }
            #{{ $sectionId }} .dost-schedule-title {
                font-size: {{ $d['schedule_program_size'] }}px;
                line-height: 1.35;
            }
            #{{ $sectionId }} .dost-schedule-item {
                width: 190px !important;
                min-width: 190px !important;
                max-width: 190px !important;
                flex: 0 0 190px !important;
                min-height: {{ $d['schedule_item_height'] }}px;
                padding: 10px {{ $d['schedule_horizontal_padding'] }}px;
            }
            #{{ $sectionId }} .dost-schedule-strip {
                gap: {{ $d['schedule_item_gap'] }}px;
            }
        }

        #{{ $sectionId }} .carousel-scrollbar-hidden::-webkit-scrollbar { display: none !important; width: 0 !important; height: 0 !important; }
    </style>

    <section id="{{ $sectionId }}" class="{{ $layout['shell_class'] }}" style="{{ $layout['shell_style'] }} {{ $colors['shell_style'] }}">
        <div class="dost-schedule-inner relative group/schedule px-4 sm:px-6 lg:px-8 {{ $layout['inner_class'] }}"
             style="{{ $layout['inner_style'] }}"
             x-data="{
                 canScrollLeft: false,
                 canScrollRight: true,

                 updateNav() {
                     const el = this.$refs.track;
                     if (!el) return;
                     this.canScrollLeft = el.scrollLeft > 4;
                     this.canScrollRight = el.scrollLeft + el.clientWidth < el.scrollWidth - 4;
                 },

                 scrollLeft() {
                     const el = this.$refs.track;
                     if (!el) return;
                     el.scrollBy({ left: -(el.clientWidth * 0.8), behavior: 'smooth' });
                 },

                 scrollRight() {
                     const el = this.$refs.track;
                     if (!el) return;
                     el.scrollBy({ left: el.clientWidth * 0.8, behavior: 'smooth' });
                 },

                 scrollToTarget() {
                     const el = this.$refs.track;
                     if (!el) return;
                     const target = el.querySelector('[data-target=\'true\']');
                     if (target) {
                         el.scrollLeft = target.offsetLeft - el.offsetLeft;
                     }
                     this.updateNav();
                 }
             }"
             x-init="$nextTick(() => { if ({{ $autoScrollToNow ? 'true' : 'false' }}) { scrollToTarget(); } else { updateNav(); } })">

            {{-- Top Header Row --}}
            <div class="flex items-center justify-between pb-3 mb-3.5">
                @if ($showTitle && filled($title))
                    <div class="flex items-center gap-2.5">
                        <span class="inline-block h-3.5 w-1 rounded-full bg-gradient-to-b from-orange-500 to-rose-600"></span>
                        <h2 class="dost-schedule-heading font-bold uppercase tracking-wider text-white">
                            {{ $title }}
                        </h2>
                    </div>
                @else
                    <div></div>
                @endif

                <div class="flex items-center gap-3">
                    {{-- Nav Arrows --}}
                    @if ($showArrows)
                        <div class="flex items-center gap-1.5">
                            <button type="button"
                                    @click="scrollLeft()"
                                    x-show="canScrollLeft"
                                    x-cloak
                                    class="flex h-7 w-7 sm:h-8 sm:w-8 items-center justify-center rounded-full bg-slate-900/80 text-slate-300 ring-1 ring-white/10 transition-all hover:bg-rose-600 hover:text-white hover:ring-rose-500 focus:outline-none"
                                    aria-label="Önceki yayınlar">
                                <svg class="h-3.5 w-3.5 sm:h-4 sm:w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                                </svg>
                            </button>
                            <button type="button"
                                    @click="scrollRight()"
                                    x-show="canScrollRight"
                                    x-cloak
                                    class="flex h-7 w-7 sm:h-8 sm:w-8 items-center justify-center rounded-full bg-slate-900/80 text-slate-300 ring-1 ring-white/10 transition-all hover:bg-rose-600 hover:text-white hover:ring-rose-500 focus:outline-none"
                                    aria-label="Sonraki yayınlar">
                                <svg class="h-3.5 w-3.5 sm:h-4 sm:w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        </div>
                    @endif

                    @if ($showAllLink)
                        <a href="{{ route('schedule.index') }}" class="dost-cta-link text-xs font-semibold text-rose-400 hover:text-rose-300 transition-colors inline-flex items-center gap-1 min-h-[36px] group">
                            <span>Tüm Akış</span>
                            <svg class="h-3.5 w-3.5 transform group-hover:translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    @endif
                </div>
            </div>

            {{-- Horizontal Broadcast Strip Container --}}
            <div x-ref="track"
                 @scroll.passive="updateNav()"
                 @resize.window.debounce.100ms.passive="updateNav()"
                 class="dost-schedule-strip carousel-scrollbar-hidden flex overflow-x-auto scroll-smooth py-1"
                 style="scrollbar-width: none; -ms-overflow-style: none;">
                @foreach ($items as $idx => $item)
                    @php
                        $start = \Illuminate\Support\Carbon::parse($item->start_time);
                        $end = $item->end_time ? \Illuminate\Support\Carbon::parse($item->end_time) : null;
                        $isNow = $nowIndex === $idx;
                        $isNext = $nextIndex === $idx;

                        $targetUrl = null;
                        if ($isNow) {
                            $targetUrl = route('live.tv');
                        } elseif ($item->program) {
                            $targetUrl = route('programs.show', $item->program);
                        }
                    @endphp

                    @if ($targetUrl)
                        <a href="{{ $targetUrl }}"
                           data-target="{{ $idx === $scrollTarget ? 'true' : 'false' }}"
                           class="dost-schedule-item flex flex-col justify-between shrink-0 border transition-all duration-300 group/item rounded-xl
                                  {{ $isNow ? 'bg-rose-500/10 border-rose-500/40 shadow-md shadow-rose-500/5 hover:bg-rose-500/15' : 'bg-slate-900/40 border-white/[0.06] hover:bg-slate-900/70 hover:border-white/10' }}">
                            
                            <div class="flex items-center justify-between gap-1 mb-1">
                                <span class="dost-schedule-time font-bold font-mono tracking-wide {{ $isNow ? 'text-rose-400' : 'text-slate-400 group-hover/item:text-slate-200' }}">
                                    {{ $start->format('H:i') }}
                                </span>

                                @if ($isNow && $showNowBadge)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-gradient-to-r from-rose-600 to-orange-600 px-2 py-0.5 text-[9px] font-bold uppercase tracking-wide text-white shadow-sm">
                                        <span class="h-1.5 w-1.5 rounded-full bg-white animate-ping"></span>
                                        ŞİMDİ
                                    </span>
                                @elseif ($isNext && $showNextBadge)
                                    <span class="inline-flex items-center rounded-full border border-slate-700 bg-slate-900/60 px-1.5 py-0.5 text-[9px] font-medium uppercase tracking-wide text-slate-300">
                                        SIRADAKİ
                                    </span>
                                @endif
                            </div>

                            <span class="dost-schedule-title line-clamp-2 font-semibold {{ $isNow ? 'text-white font-bold' : 'text-slate-200 group-hover/item:text-rose-400' }}" title="{{ $item->program->name }}">
                                {{ $item->program->name }}
                            </span>
                        </a>
                    @else
                        <div data-target="{{ $idx === $scrollTarget ? 'true' : 'false' }}"
                             class="dost-schedule-item flex flex-col justify-between shrink-0 border transition-all duration-300 rounded-xl
                                    {{ $isNow ? 'bg-rose-500/10 border-rose-500/40 shadow-md shadow-rose-500/5' : 'bg-slate-900/40 border-white/[0.06]' }}">
                            
                            <div class="flex items-center justify-between gap-1 mb-1">
                                <span class="dost-schedule-time font-bold font-mono tracking-wide {{ $isNow ? 'text-rose-400' : 'text-slate-400' }}">
                                    {{ $start->format('H:i') }}
                                </span>

                                @if ($isNow && $showNowBadge)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-gradient-to-r from-rose-600 to-orange-600 px-2 py-0.5 text-[9px] font-bold uppercase tracking-wide text-white shadow-sm">
                                        <span class="h-1.5 w-1.5 rounded-full bg-white animate-ping"></span>
                                        ŞİMDİ
                                    </span>
                                @elseif ($isNext && $showNextBadge)
                                    <span class="inline-flex items-center rounded-full border border-slate-700 bg-slate-900/60 px-1.5 py-0.5 text-[9px] font-medium uppercase tracking-wide text-slate-300">
                                        SIRADAKİ
                                    </span>
                                @endif
                            </div>

                            <span class="dost-schedule-title line-clamp-2 font-semibold {{ $isNow ? 'text-white font-bold' : 'text-slate-200' }}" title="{{ $item->program?->name ?? 'Yayın' }}">
                                {{ $item->program?->name ?? 'Yayın' }}
                            </span>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </section>
@endif
