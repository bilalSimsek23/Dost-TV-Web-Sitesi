@props([
    'block' => [],
    'program' => null,
])

@php
    if (! $program) return;

    $colors = \App\Services\Home\HomepageBlockRegistry::resolveSectionColors($block);
    $layout = \App\Services\Home\HomepageBlockRegistry::resolveSectionLayout($block);

    $coverImage = $program->horizontal_image ?: ($program->cover_image ?: null);
    $logo = $program->program_logo;
    $categories = $program->categories ?? collect();
    $schedules = $program->schedules ?? collect();
@endphp

<section class="{{ $layout['shell_class'] }}" style="{{ $colors['shell_style'] }} {{ $layout['shell_style'] }}">
    <div class="{{ $layout['inner_class'] }} px-4 sm:px-6 lg:px-8" style="{{ $layout['inner_style'] }}">
        <div class="relative overflow-hidden rounded-2xl border border-white/10 bg-slate-900/90 p-6 md:p-10 shadow-2xl backdrop-blur-xl">
            {{-- Background Cover Image with Gradient Overlay --}}
            @if ($coverImage)
                <div class="absolute inset-0 z-0">
                    <img src="{{ \Illuminate\Support\Facades\Storage::url($coverImage) }}" alt="{{ $program->name }}" class="h-full w-full object-cover opacity-20 filter blur-sm">
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/80 to-transparent"></div>
                </div>
            @endif

            <div class="relative z-10 flex flex-col md:flex-row items-center md:items-start gap-6 md:gap-8">
                {{-- Program Logo or Poster --}}
                @if ($logo)
                    <div class="shrink-0 w-32 md:w-44 h-32 md:h-44 rounded-xl border border-white/15 bg-slate-950/60 p-2 shadow-lg flex items-center justify-center">
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($logo) }}" alt="{{ $program->name }}" class="max-h-full max-w-full object-contain">
                    </div>
                @elseif ($coverImage)
                    <div class="shrink-0 w-36 md:w-48 aspect-[3/4] rounded-xl overflow-hidden border border-white/15 shadow-lg">
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($coverImage) }}" alt="{{ $program->name }}" class="h-full w-full object-cover">
                    </div>
                @endif

                {{-- Text Content --}}
                <div class="flex-1 text-center md:text-left space-y-3">
                    @if ($categories->isNotEmpty())
                        <div class="flex flex-wrap items-center justify-center md:justify-start gap-2">
                            @foreach ($categories as $cat)
                                <span class="rounded-full bg-rose-500/20 px-3 py-1 text-xs font-semibold text-rose-300 border border-rose-500/30">
                                    {{ $cat->name }}
                                </span>
                            @endforeach
                        </div>
                    @endif

                    <h1 class="text-2xl sm:text-3xl md:text-4xl font-extrabold tracking-tight text-white">
                        {{ $program->name }}
                    </h1>

                    @if (filled($program->description) || filled($program->short_description))
                        <p class="text-sm md:text-base text-slate-300 max-w-3xl leading-relaxed">
                            {{ $program->description ?: $program->short_description }}
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
