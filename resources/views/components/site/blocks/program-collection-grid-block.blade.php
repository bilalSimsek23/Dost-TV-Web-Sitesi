@props([
    'block' => [],
    'collection' => null,
    'programs' => collect(),
])

@php
    if (! $collection) return;

    $colors = \App\Services\Home\HomepageBlockRegistry::resolveSectionColors($block);
    $layout = \App\Services\Home\HomepageBlockRegistry::resolveSectionLayout($block);

    $desktopCols = \App\Services\Home\HomepageBlockRegistry::resolveDesktopColumns($block, 4);
    $showTitle = $block['show_title'] ?? true;
    $title = $block['title'] ?? ($collection->name . ' Programları');
@endphp

<section class="{{ $layout['shell_class'] }}" style="{{ $colors['shell_style'] }} {{ $layout['shell_style'] }}">
    <div class="{{ $layout['inner_class'] }} px-4 sm:px-6 lg:px-8 space-y-6" style="{{ $layout['inner_style'] }}">
        @if ($showTitle && filled($title))
            <div class="flex items-center justify-between border-b border-white/10 pb-4">
                <h2 class="text-xl md:text-2xl font-bold text-white flex items-center gap-2">
                    <span class="h-5 w-1 rounded-full bg-rose-500"></span>
                    {{ $title }}
                </h2>
                <span class="text-xs font-semibold text-slate-400 bg-white/5 border border-white/10 px-3 py-1 rounded-full">
                    {{ is_countable($programs) ? count($programs) : $programs->count() }} Program
                </span>
            </div>
        @endif

        @if ($programs->isNotEmpty())
            <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6">
                @foreach ($programs as $prog)
                    <x-site.program-card :program="$prog" />
                @endforeach
            </div>

            @if ($programs instanceof \Illuminate\Contracts\Pagination\Paginator)
                <div class="pt-6 flex justify-center">
                    {{ $programs->links() }}
                </div>
            @endif
        @else
            <div class="rounded-2xl border border-white/10 bg-white/[0.02] p-8 text-center text-slate-500">
                Bu koleksiyonda henüz program bulunmuyor.
            </div>
        @endif
    </div>
</section>
