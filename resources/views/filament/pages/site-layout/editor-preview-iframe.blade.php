@extends('layouts.app')

@section('title', 'Ana Sayfa Canlı Önizleme (Taslak)')

@section('content')
    {{-- Fixed Header & Hero Selection Wrapper for Editor --}}
    <div class="relative group cursor-pointer transition ring-2 ring-transparent hover:ring-slate-500/50"
         data-fixed-area="hero"
         onclick="window.parent.postMessage({ type: 'select-fixed', area: 'hero' }, '*')"
         title="Sabit Alan: Hero ve Header (Düzenlenemez)">
        <div class="absolute top-2 right-4 z-40 hidden group-hover:flex items-center gap-1 rounded bg-slate-900/90 px-2 py-1 text-[11px] font-semibold text-slate-300 shadow backdrop-blur-sm border border-slate-700">
            <span>📌 Sabit Alan (Header / Hero)</span>
        </div>

        <x-site.home.hero-section
            :today-schedule="$todaySchedule"
            :hero-programs="$heroPrograms"
            :block="$fixedSettings['hero'] ?? []"
        />
    </div>

    {{-- Dynamic Builder Blocks (Between Hero and Footer) --}}
    <x-site.homepage-sections
        :sections="$homepageSections"
        :banners="$banners"
        :settings="$settings"
        :today-schedule="$todaySchedule"
        :featured-programs="$featuredPrograms"
        :hero-programs="$heroPrograms"
        :resolved-block-data="$resolvedBlockData ?? []"
        :preview="true"
        :editor-mode="true"
    />

    {{-- Footer Selection Indicator --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const footerEl = document.querySelector('footer');
            if (footerEl) {
                footerEl.classList.add('relative', 'group', 'cursor-pointer', 'transition', 'ring-2', 'ring-transparent', 'hover:ring-slate-500/50');
                footerEl.addEventListener('click', function(e) {
                    window.parent.postMessage({ type: 'select-fixed', area: 'footer' }, '*');
                });
            }
        });
    </script>
@endsection
