@props([
    'sections' => [],
    'collection' => null,
    'programs' => collect(),
    'settings' => [],
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
                    <span>Tıkla & Düzenle: {{ \App\Services\Home\HomepageBlockRegistry::getBlockTypesForPageType('program_collection')[$blockType] ?? $blockType }} {{ $isVis ? '' : '(Gizli)' }}</span>
                </div>
            @endif

            @switch($blockType)
                @case('collection_header')
                    <x-site.blocks.collection-header-block :block="$section" :collection="$collection" :settings="$settings" />
                    @break

                @case('program_collection_grid')
                    <x-site.blocks.program-collection-grid-block :block="$section" :collection="$collection" :programs="$programs" />
                    @break

                @case('content_shelf')
                    <x-site.blocks.content-shelf-block :block="$section" :items="$programs" />
                    @break

                @default
                    @break
            @endswitch
        </div>
    @endif
@endforeach
</div>
