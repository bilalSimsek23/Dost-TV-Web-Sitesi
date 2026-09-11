@props([
    'block' => [],
    'programs' => collect(),
    'preview' => false,
])

@php
    $title = $block['title'] ?? 'Program Vitrini';
    $showTitle = $block['show_title'] ?? true;
    $titleAlignment = $block['title_alignment'] ?? 'left';
    $subtitle = $block['subtitle'] ?? null;
    $desktopColumns = \App\Services\Home\HomepageBlockRegistry::resolveDesktopColumns($block);
    $rowCount = (int) ($block['row_count'] ?? 1);
    $displayVariant = $block['display_variant'] ?? 'grid';
    $showArrows = ! empty($block['show_arrows'] ?? true);
    $gapSize = $block['gap_size'] ?? 'md';
    $ctaUrl = \App\Services\Home\CtaRouteResolver::resolveForProgramBlock($block);
    $layout = \App\Services\Home\HomepageBlockRegistry::resolveSectionLayout($block);
    $colors = \App\Services\Home\HomepageBlockRegistry::resolveSectionColors($block);
@endphp

<section class="relative {{ $layout['shell_class'] }}" style="{{ $layout['shell_style'] }} {{ $colors['shell_style'] }}">
    <div class="px-4 sm:px-6 lg:px-8 {{ $layout['inner_class'] }}" style="{{ $layout['inner_style'] }}">
        @if ($showTitle && filled($title))
            <div class="mb-6 flex flex-col sm:flex-row sm:items-end justify-between gap-3 pb-3.5 {{ $titleAlignment === 'center' ? 'text-center sm:text-center' : '' }}">
                <div class="{{ $titleAlignment === 'center' ? 'mx-auto' : '' }}">
                    <div class="flex items-center gap-2.5 {{ $titleAlignment === 'center' ? 'justify-center' : '' }}">
                        <span class="inline-block h-4 w-1 rounded-full" style="background-color: {{ $colors['accent'] }};"></span>
                        <h2 class="text-2xl font-extrabold text-white sm:text-3xl tracking-tight">
                            {{ $title }}
                        </h2>
                    </div>
                    @if ($subtitle)
                        <p class="mt-1 text-sm text-slate-400 font-normal">{{ $subtitle }}</p>
                    @endif
                </div>

                @if ($ctaUrl)
                    <a href="{{ $ctaUrl }}" class="inline-flex items-center text-xs font-semibold transition-colors group self-start sm:self-auto min-h-[36px]" style="color: {{ $colors['accent'] }};">
                        <span>Tüm Programları İncele</span>
                        <svg class="ml-1.5 h-4 w-4 transform group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                @endif
            </div>
        @endif

        <x-site.blocks.shelf-grid
            :items="$programs"
            :block="$block"
            :display-variant="$displayVariant"
            :desktop-columns="$desktopColumns"
            :row-count="$rowCount"
            :show-arrows="$showArrows"
            :gap-size="$gapSize"
            :id="$block['uuid'] ?? null"
            card-component="site.program-card"
        />
    </div>
</section>
