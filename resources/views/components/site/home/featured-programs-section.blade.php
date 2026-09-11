@props([
    'featuredPrograms' => collect(),
    'preview' => false,
    'block' => [],
])

@php
    $layout = \App\Services\Home\HomepageBlockRegistry::resolveSectionLayout($block);
    $colors = \App\Services\Home\HomepageBlockRegistry::resolveSectionColors($block);
    $desktopColumns = \App\Services\Home\HomepageBlockRegistry::resolveDesktopColumns($block);
    $rowCount = (int) ($block['row_count'] ?? 1);
    $displayVariant = $block['display_variant'] ?? 'grid';
    $showArrows = ! empty($block['show_arrows'] ?? true);
    $gapSize = $block['gap_size'] ?? 'md';
    $cardWidthPx = \App\Services\Home\HomepageBlockRegistry::resolveCardWidthPx($block['card_width'] ?? null, $block['card_width_custom'] ?? null);
    $cardRatio = $block['card_ratio'] ?? 'default';
    $cardAlignment = $block['card_alignment'] ?? 'left';
    $alignmentClass = match($cardAlignment) {
        'center' => 'justify-center',
        'right' => 'justify-end',
        default => 'justify-start',
    };
@endphp

<section class="relative {{ $layout['shell_class'] }}" style="{{ $layout['shell_style'] }} {{ $colors['shell_style'] }}">
    <div class="px-4 sm:px-6 lg:px-8 {{ $layout['inner_class'] }}" style="{{ $layout['inner_style'] }}">
        <div class="mb-6 flex flex-col sm:flex-row sm:items-end justify-between gap-3 pb-3.5">
            <div>
                <div class="flex items-center gap-2.5">
                    <span class="inline-block h-4 w-1 rounded-full" style="background-color: var(--color-accent);"></span>
                    <h2 class="text-2xl font-extrabold text-white sm:text-3xl tracking-tight">Öne Çıkan Programlar</h2>
                </div>
                <p class="mt-1 text-sm text-slate-400 font-normal">Kanalımızın en sevilen ve ilgi gören programlarını keşfedin.</p>
            </div>
            <a href="{{ $preview ? 'javascript:void(0)' : route('programs.index') }}" class="inline-flex items-center text-xs sm:text-sm font-semibold transition-colors group self-start sm:self-auto min-h-[36px]" style="color: var(--color-accent);">
                <span>Tümünü Gör</span>
                <svg class="ml-1.5 h-4 w-4 transform group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>
        </div>

        @if ($cardWidthPx)
            <div class="flex flex-wrap gap-4 sm:gap-6 {{ $alignmentClass }}">
                @forelse ($featuredPrograms as $program)
                    <div style="width: {{ $cardWidthPx }}px; flex: 0 0 {{ $cardWidthPx }}px; max-width: {{ $cardWidthPx }}px;">
                        @if($preview)
                            <x-site.program-card
                                :title="$program->name"
                                :cover-image="$program->cover_image"
                                :categories="$program->categories->pluck('name')->all()"
                                :card-ratio="$cardRatio"
                                :preview="true"
                            />
                        @else
                            <a href="{{ route('programs.show', $program) }}" class="group block">
                                <x-site.program-card
                                    :title="$program->name"
                                    :cover-image="$program->cover_image"
                                    :categories="$program->categories->pluck('name')->all()"
                                    :card-ratio="$cardRatio"
                                />
                            </a>
                        @endif
                    </div>
                @empty
                    <p class="col-span-full text-slate-500">Henüz program eklenmedi. Admin panelden program ekleyebilirsiniz.</p>
                @endforelse
            </div>
        @else
            <x-site.blocks.shelf-grid
                :items="$featuredPrograms"
                :block="$block"
                :display-variant="$displayVariant"
                :desktop-columns="$desktopColumns"
                :row-count="$rowCount"
                :show-arrows="$showArrows"
                :gap-size="$gapSize"
                :id="$block['uuid'] ?? null"
                card-component="site.program-card"
            />
        @endif
    </div>
</section>
