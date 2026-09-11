@props([
    'sections' => [],
    'collection' => null,
    'episodes' => collect(),
    'settings' => [],
    'preview' => false,
    'editorMode' => false,
])

<div class="dost-progressive-bg min-h-screen space-y-6">
@forelse ($sections as $section)
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
                    <span>Tıkla & Düzenle: {{ \App\Services\Home\HomepageBlockRegistry::getBlockTypesForPageType('video_collection')[$blockType] ?? $blockType }} {{ $isVis ? '' : '(Gizli)' }}</span>
                </div>
            @endif

            @switch($blockType)
                @case('collection_header')
                    <x-site.blocks.collection-header-block :block="$section" :collection="$collection" :settings="$settings" />
                    @break

                @case('video_collection_grid')
                @case('video_collection')
                    <x-site.blocks.video-collection-block :block="$section" :episodes="$episodes" :preview="$preview" />
                    @break

                @case('content_shelf')
                    <x-site.blocks.content-shelf-block :block="$section" :items="$episodes" />
                    @break

                @case('program_showcase')
                    <x-site.blocks.program-showcase-block :block="$section" />
                    @break

                @case('category_shelf')
                    <x-site.blocks.category-shelf-block :block="$section" />
                    @break

                @case('today_schedule')
                    <x-site.home.today-schedule-section :block="$section" />
                    @break

                @default
                    @break
            @endswitch
        </div>
    @endif
@empty
    <div class="container mx-auto px-4 py-16">
        <div class="rounded-2xl border border-dashed border-white/20 bg-slate-900/60 p-12 text-center shadow-xl backdrop-blur-md">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-800 text-slate-400 text-xl">
                📑
            </div>
            <h3 class="mt-4 text-base font-bold text-white">Bu Düzende Henüz Bölüm Bulunmuyor</h3>
            <p class="mt-1 text-sm text-slate-400">Sol panelde bulunan "+ Yeni Bölüm Ekle" menüsünü kullanarak sayfaya içerik ekleyebilirsiniz.</p>
        </div>
    </div>
@endforelse
</div>
