@props([
    'sections' => [],
    'program' => null,
    'hasSeasons' => false,
    'seasonItems' => collect(),
    'selectedSeasonItem' => null,
    'episodes' => collect(),
    'featuredEpisode' => null,
    'relatedPrograms' => collect(),
    'preview' => false,
    'editorMode' => false,
])

<div class="dost-progressive-bg min-h-screen space-y-6">
@foreach ($sections as $section)
    @if (! empty($section['visible']) || $editorMode)
        @php
            $blockType = $section['block_type'] ?? ($section['key'] ?? '');
            $uuid = $section['uuid'] ?? ($section['key'] ?? '');
            $isVis = ! empty($section['visible']);
        @endphp

        <div @if($editorMode)
                class="relative group cursor-pointer transition rounded-xl ring-2 {{ $isVis ? 'ring-transparent hover:ring-rose-500/60' : 'ring-dashed ring-amber-500/40 opacity-60 hover:opacity-100 hover:ring-amber-500' }}"
                data-block-uuid="{{ $uuid }}"
                data-block-type="{{ $blockType }}"
                onclick="window.parent.postMessage({ type: 'select-block', uuid: '{{ $uuid }}', blockType: '{{ $blockType }}' }, '*')"
             @endif>
            @if ($editorMode)
                <div class="absolute top-2 right-4 z-40 hidden group-hover:flex items-center gap-1.5 rounded-full bg-slate-900/90 px-3 py-1 text-xs font-semibold text-rose-300 shadow-md backdrop-blur-sm border border-rose-500/30">
                    <span class="h-2 w-2 rounded-full {{ $isVis ? 'bg-rose-400' : 'bg-amber-400' }}"></span>
                    <span>Tıkla & Düzenle: {{ \App\Services\Home\HomepageBlockRegistry::getBlockTypesForPageType('program_detail')[$blockType] ?? $blockType }} {{ $isVis ? '' : '(Gizli)' }}</span>
                </div>
            @endif

            @switch($blockType)
                @case('program_hero')
                    <x-site.blocks.program-hero-block :block="$section" :program="$program" />
                    @break

                @case('program_description')
                    <x-site.blocks.program-description-block :block="$section" :program="$program" />
                    @break

                @case('episodes_shelf')
                    <x-site.blocks.episodes-shelf-block
                        :block="$section"
                        :program="$program"
                        :has-seasons="$hasSeasons"
                        :season-items="$seasonItems"
                        :selected-season-item="$selectedSeasonItem"
                        :episodes="$episodes"
                        :featured-episode="$featuredEpisode"
                    />
                    @break

                @case('related_programs')
                    <x-site.blocks.related-programs-block :block="$section" :programs="$relatedPrograms" />
                    @break

                @default
                    @break
            @endswitch
        </div>
    @endif
@endforeach
</div>
