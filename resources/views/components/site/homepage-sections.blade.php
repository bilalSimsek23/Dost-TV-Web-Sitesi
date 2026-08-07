@props([
    'sections' => [],
    'banners' => collect(),
    'settings' => null,
    'todaySchedule' => collect(),
    'featuredPrograms' => collect(),
    'preview' => false,
])

@php
    $sections = $sections ?: \App\Models\SiteSetting::current()->normalized_homepage_sections;
    $hasVisibleSection = false;
@endphp

@foreach ($sections as $section)
    @if (! empty($section['visible']))
        @php $hasVisibleSection = true; @endphp

        @switch($section['key'])
            @case('hero')
                <x-site.home.hero-section :banners="$banners" />
                @break

            @case('live_intro')
                <x-site.home.live-intro-section :settings="$settings" :today-schedule="$todaySchedule" />
                @break

            @case('today_schedule')
                <x-site.home.today-schedule-section :today-schedule="$todaySchedule" />
                @break

            @case('featured_programs')
                <x-site.home.featured-programs-section :featured-programs="$featuredPrograms" :preview="$preview" />
                @break
        @endswitch
    @endif
@endforeach

@if (! $hasVisibleSection)
    <div class="mx-auto max-w-7xl px-4 py-24 text-center text-xs text-slate-500">
        <p>Ana sayfa içerikleri güncellenmektedir.</p>
    </div>
@endif
