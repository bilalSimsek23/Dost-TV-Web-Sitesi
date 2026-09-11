@props([
    'settings' => null,
    'block' => [],
])

@php
    $settings = $settings ?? \App\Models\SiteSetting::current();
    $block = $block ?? [];

    $badgeText = filled($block['badge_text'] ?? null) ? $block['badge_text'] : 'Uydu üzerinden 7/24 yayın';
    $title = filled($block['title'] ?? null) ? $block['title'] : ($settings->site_name ?? 'Dost TV');
    $highlightText = filled($block['highlight_text'] ?? null) ? $block['highlight_text'] : 'her an yanınızda';
    $description = filled($block['description'] ?? null) ? $block['description'] : 'Diziler, haberler ve belgesellerle dolu yayın akışımızı takip edin; canlı TV ve canlı radyomuzu dilediğiniz an, dilediğiniz yerden izleyin, dinleyin.';
    $tvButtonText = filled($block['tv_button_text'] ?? null) ? $block['tv_button_text'] : 'Canlı TV İzle';
    $radioButtonText = filled($block['radio_button_text'] ?? null) ? $block['radio_button_text'] : 'Canlı Radyo Dinle';

    $showTvButton = !isset($block['show_tv_button']) || !empty($block['show_tv_button']);
    $showRadioButton = !isset($block['show_radio_button']) || !empty($block['show_radio_button']);
@endphp

<section class="w-full bg-slate-950 py-8">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="grid items-center gap-12">
            <div>
                <span class="inline-flex items-center gap-2 rounded-full bg-rose-500/10 px-3 py-1 text-xs font-semibold text-rose-400 ring-1 ring-inset ring-rose-500/20">
                    {{ $badgeText }}
                </span>
                <h1 class="mt-6 text-4xl font-black leading-tight text-white sm:text-5xl lg:text-6xl">
                    {{ $title }}
                    <span class="block bg-gradient-to-r from-rose-400 to-amber-300 bg-clip-text text-transparent">{{ $highlightText }}</span>
                </h1>
                <p class="mt-6 max-w-xl text-lg text-slate-400">
                    {{ $description }}
                </p>
                @if((($settings->live_tv_is_public ?? true) && $showTvButton) || (($settings->radio_is_public ?? true) && $showRadioButton))
                    <div class="mt-8 flex flex-wrap gap-4">
                        @if (($settings->live_tv_is_public ?? true) && $showTvButton)
                            <a href="{{ route('live.tv') }}"
                               class="inline-flex items-center gap-2 rounded-full bg-rose-600 px-6 py-3 font-semibold text-white shadow-lg shadow-rose-600/30 transition hover:bg-rose-500">
                                {{ $tvButtonText }}
                            </a>
                        @endif
                        @if (($settings->radio_is_public ?? true) && $showRadioButton)
                            <a href="{{ route('live.radio') }}"
                               class="inline-flex items-center gap-2 rounded-full bg-white/5 px-6 py-3 font-semibold text-white ring-1 ring-inset ring-white/10 transition hover:bg-white/10">
                                {{ $radioButtonText }}
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
