@props([
    'block' => [],
    'programs' => collect(),
])

@php
    if ($programs->isEmpty()) return;

    $colors = \App\Services\Home\HomepageBlockRegistry::resolveSectionColors($block);
    $layout = \App\Services\Home\HomepageBlockRegistry::resolveSectionLayout($block);

    $showTitle = $block['show_title'] ?? true;
    $title = $block['title'] ?? 'Benzer Programlar';
@endphp

<section class="{{ $layout['shell_class'] }}" style="{{ $colors['shell_style'] }} {{ $layout['shell_style'] }}">
    <div class="{{ $layout['inner_class'] }} px-4 sm:px-6 lg:px-8 space-y-6" style="{{ $layout['inner_style'] }}">
        @if ($showTitle && filled($title))
            <div class="flex items-center justify-between border-b border-white/10 pb-4">
                <h2 class="text-xl md:text-2xl font-bold text-white flex items-center gap-2">
                    <span class="h-5 w-1 rounded-full bg-rose-500"></span>
                    {{ $title }}
                </h2>
                <a href="{{ route('programs.index') }}" class="text-xs md:text-sm font-medium text-rose-400 hover:text-rose-300">
                    Tüm Programlar →
                </a>
            </div>
        @endif

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4 md:gap-6">
            @foreach ($programs as $prog)
                <x-site.program-card :program="$prog" />
            @endforeach
        </div>
    </div>
</section>
