@props([
    'block' => [],
    'program' => null,
])

@php
    if (! $program || blank($program->description)) return;

    $colors = \App\Services\Home\HomepageBlockRegistry::resolveSectionColors($block);
    $layout = \App\Services\Home\HomepageBlockRegistry::resolveSectionLayout($block);

    $showTitle = $block['show_title'] ?? true;
    $title = $block['title'] ?? 'Program Hakkında';
@endphp

<section class="{{ $layout['shell_class'] }}" style="{{ $colors['shell_style'] }} {{ $layout['shell_style'] }}">
    <div class="{{ $layout['inner_class'] }} px-4 sm:px-6 lg:px-8" style="{{ $layout['inner_style'] }}">
        <div class="rounded-2xl border border-white/10 bg-slate-900/60 p-6 md:p-8 backdrop-blur-md space-y-4">
            @if ($showTitle && filled($title))
                <h2 class="text-xl md:text-2xl font-bold text-white flex items-center gap-2 border-b border-white/10 pb-3">
                    <span class="h-5 w-1 rounded-full bg-rose-500"></span>
                    {{ $title }}
                </h2>
            @endif

            <div class="prose prose-invert max-w-none text-slate-300 text-sm md:text-base leading-relaxed space-y-3">
                {!! nl2br(e($program->description)) !!}
            </div>
        </div>
    </div>
</section>
