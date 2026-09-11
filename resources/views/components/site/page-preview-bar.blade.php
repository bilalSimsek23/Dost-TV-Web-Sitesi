@props([
    'page' => null,
])

@if(auth()->check() && auth()->user()?->hasAnyRole(['super_admin', 'administrator', 'designer', 'editor']) && $page)
    <div class="fixed top-0 left-0 right-0 z-[9999] bg-slate-950/95 border-b border-amber-500/50 px-4 py-2 text-xs text-white shadow-2xl backdrop-blur-md flex items-center justify-between gap-4">
        <div class="flex items-center gap-2">
            <span class="relative flex h-2.5 w-2.5">
              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
              <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-amber-500"></span>
            </span>
            <strong class="font-bold uppercase tracking-wider text-amber-400">SAYFA TEST MODU</strong>
            <span class="text-slate-300 font-semibold truncate max-w-xs sm:max-w-md">— {{ $page->title }}</span>
            <span class="text-slate-400 hidden lg:inline">(Geçici Görünüm — Veritabanına Kaydedilmedi)</span>
        </div>

        <div class="flex items-center gap-3 shrink-0">
            {{-- Düzenlemeye Dön --}}
            <a href="{{ \App\Filament\Resources\Pages\PageResource::getUrl('edit', ['record' => $page]) }}"
               class="px-3 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 transition font-medium flex items-center gap-1">
                ‹ Düzenlemeye Dön
            </a>

            {{-- Testi Kapat --}}
            <a href="{{ route('page.preview.close', ['slug' => $page->slug]) }}"
               class="px-3 py-1 rounded-lg bg-rose-600/90 hover:bg-rose-600 text-white font-medium transition shadow">
                Testi Kapat ✕
            </a>
        </div>
    </div>
    <div class="h-10 w-full"></div>
@endif
