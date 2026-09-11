@props([
    'block' => [],
    'collection' => null,
    'settings' => [],
])

@php
    if (! $collection) return;

    $colors = \App\Services\Home\HomepageBlockRegistry::resolveSectionColors($block);
    $layout = \App\Services\Home\HomepageBlockRegistry::resolveSectionLayout($block);

    $title = $collection->name;
    $subtitle = $collection->subtitle ?: ($collection->description ?? null);
@endphp

<section class="{{ $layout['shell_class'] }}" style="{{ $colors['shell_style'] }} {{ $layout['shell_style'] }}">
    <div class="{{ $layout['inner_class'] }} px-4 sm:px-6 lg:px-8" style="{{ $layout['inner_style'] }}">
        <div class="relative overflow-hidden rounded-2xl border border-white/10 bg-slate-900/90 p-6 md:p-10 shadow-2xl backdrop-blur-xl space-y-3">
            <div class="flex items-center gap-2">
                <span class="rounded-full bg-rose-500/20 px-3 py-1 text-xs font-bold text-rose-300 border border-rose-500/30 uppercase tracking-wider">
                    {{ $block['block_type'] === 'program_collection_grid' ? 'Program Koleksiyonu' : 'Koleksiyon' }}
                </span>
            </div>

            <h1 class="text-2xl sm:text-3xl md:text-4xl font-black text-white tracking-tight">
                {{ $title }}
            </h1>

            @if ($subtitle)
                <p class="text-sm md:text-base text-slate-300 max-w-3xl leading-relaxed">
                    {{ $subtitle }}
                </p>
            @endif
        </div>
    </div>
</section>
