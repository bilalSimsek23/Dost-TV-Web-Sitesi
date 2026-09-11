@props([
    'sections' => [],
    'banners' => collect(),
    'settings' => null,
    'todaySchedule' => collect(),
    'featuredPrograms' => collect(),
    'heroPrograms' => collect(),
    'resolvedBlockData' => [],
    'preview' => false,
    'editorMode' => false,
])

@php
    $hasVisibleSection = false;
@endphp

<div class="dost-progressive-bg min-h-screen">
@foreach ($sections as $section)
    @if (! empty($section['visible']) || $editorMode)
        @php
            $blockType = $section['block_type'] ?? ($section['key'] ?? '');
            $uuid = $section['uuid'] ?? ($section['key'] ?? '');
            $isVis = ! empty($section['visible']);
        @endphp

        @if ($blockType === 'hero')
            @continue
        @endif

        @php
            $hasVisibleSection = true;
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
                    <span>Tıkla & Düzenle: {{ \App\Services\Home\HomepageBlockRegistry::getBlockTypes()[$blockType] ?? $blockType }} {{ $isVis ? '' : '(Gizli)' }}</span>
                </div>
            @endif

            @switch($blockType)
                @case('live_intro')
                @case('live_stream')
                    <x-site.home.live-intro-section :settings="$settings" :block="$section" />
                    @break

                @case('today_schedule')
                    <x-site.home.today-schedule-section :today-schedule="$todaySchedule" :block="$section" />
                    @break

                @case('featured_programs')
                    <x-site.home.featured-programs-section :featured-programs="$featuredPrograms" :block="$section" :preview="$preview" />
                    @break

                @case('video_collection')
                    <x-site.blocks.video-collection-block
                        :block="$section"
                        :episodes="$resolvedBlockData[$uuid] ?? collect()"
                        :preview="$preview"
                    />
                    @break

                @case('program_showcase')
                    <x-site.blocks.program-showcase-block
                        :block="$section"
                        :programs="$resolvedBlockData[$uuid] ?? collect()"
                        :preview="$preview"
                    />
                    @break

                @case('category_shelf')
                    <x-site.blocks.category-shelf-block
                        :block="$section"
                        :programs="$resolvedBlockData[$uuid] ?? collect()"
                    />
                    @break

                @case('content_shelf')
                    <x-site.blocks.content-shelf-block
                        :block="$section"
                        :items="$resolvedBlockData[$uuid] ?? collect()"
                    />
                    @break

                @case('youtube_channel_shelf')
                    <x-site.blocks.youtube-channel-shelf-block
                        :block="$section"
                        :channels="$resolvedBlockData[$uuid] ?? collect()"
                    />
                    @break

                @case('instagram_videos')
                    <x-site.blocks.instagram-videos-block
                        :block="$section"
                        :videos="$resolvedBlockData[$uuid] ?? collect()"
                    />
                    @break

                @default
                    @break
            @endswitch
        </div>
    @endif
@endforeach
</div>
