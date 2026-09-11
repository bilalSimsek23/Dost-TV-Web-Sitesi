@props([
    'activeMode' => 'dark',
])

@if(auth()->check() && auth()->user()?->hasAnyRole(['super_admin', 'administrator', 'designer', 'editor']))
    <div class="fixed top-0 left-0 right-0 z-[9999] bg-slate-950/95 border-b border-amber-500/50 px-4 py-2 text-xs text-white shadow-2xl backdrop-blur-md flex items-center justify-between gap-4">
        <div class="flex items-center gap-2">
            <span class="relative flex h-2.5 w-2.5">
              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
              <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-amber-500"></span>
            </span>
            <strong class="font-bold uppercase tracking-wider text-amber-400">TEMA TEST MODU</strong>
            <span class="text-slate-400 hidden sm:inline">(Geçici Tema — Veritabanına Kaydedilmedi)</span>
        </div>

        <div class="flex items-center gap-3">
            {{-- Quick Dark | Light mode toggle --}}
            <div class="flex items-center rounded-lg bg-slate-900 p-0.5 border border-slate-700/80">
                <a href="{{ route('theme.preview.toggle-mode', ['mode' => 'dark']) }}"
                   class="px-2.5 py-1 rounded-md text-[11px] font-semibold transition {{ $activeMode === 'dark' ? 'bg-amber-500 text-slate-950 shadow' : 'text-slate-400 hover:text-white' }}">
                    Dark
                </a>
                <a href="{{ route('theme.preview.toggle-mode', ['mode' => 'light']) }}"
                   class="px-2.5 py-1 rounded-md text-[11px] font-semibold transition {{ $activeMode === 'light' ? 'bg-amber-500 text-slate-950 shadow' : 'text-slate-400 hover:text-white' }}">
                    Light
                </a>
            </div>

            {{-- Görünüm Paneline Dön --}}
            <a href="/admin/site-layout/appearance"
               class="px-3 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 transition font-medium flex items-center gap-1">
                ‹ Görünüm Paneline Dön
            </a>

            {{-- Testi Kapat --}}
            <a href="{{ route('theme.preview.close') }}"
               class="px-3 py-1 rounded-lg bg-rose-600/90 hover:bg-rose-600 text-white font-medium transition shadow">
                Testi Kapat ✕
            </a>
        </div>
    </div>
    <div class="h-10 w-full"></div>
@endif
