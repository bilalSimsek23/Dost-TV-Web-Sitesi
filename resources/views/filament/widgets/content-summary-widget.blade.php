<x-filament-widgets::widget>
    <div class="fi-wi-card p-6 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm h-full flex flex-col justify-between">
        {{-- Header --}}
        <div>
            <div class="flex items-center gap-3 pb-4 border-b border-slate-100 dark:border-slate-800">
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-amber-500/10 text-amber-500">
                    <x-heroicon-o-film class="w-5 h-5" />
                </span>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">İçerik Özeti</h3>
            </div>

            {{-- Metric Rows --}}
            <div class="grid grid-cols-2 gap-3 mt-4">
                {{-- Programlar --}}
                <a href="{{ $programsUrl }}" class="p-3 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-700/60 hover:border-slate-300 dark:hover:border-slate-600 transition-colors block group">
                    <span class="text-xs font-medium text-slate-500 dark:text-slate-400 block">Programlar</span>
                    <div class="flex items-baseline gap-1.5 mt-1">
                        <span class="text-lg font-bold text-slate-900 dark:text-white group-hover:text-rose-500 transition-colors">{{ $totalPrograms }}</span>
                        <span class="text-xs text-emerald-500 font-semibold">({{ $activePrograms }} Aktif)</span>
                    </div>
                </a>

                {{-- Bölümler --}}
                <a href="{{ $episodesUrl }}" class="p-3 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-700/60 hover:border-slate-300 dark:hover:border-slate-600 transition-colors block group">
                    <span class="text-xs font-medium text-slate-500 dark:text-slate-400 block">Bölümler (Video)</span>
                    <div class="flex items-baseline gap-1.5 mt-1">
                        <span class="text-lg font-bold text-slate-900 dark:text-white group-hover:text-rose-500 transition-colors">{{ $totalEpisodes }}</span>
                        <span class="text-xs text-emerald-500 font-semibold">({{ $publishedEpisodes }} Yayında)</span>
                    </div>
                </a>

                {{-- Duyurular --}}
                <a href="{{ $announcementsUrl }}" class="p-3 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-700/60 hover:border-slate-300 dark:hover:border-slate-600 transition-colors block group">
                    <span class="text-xs font-medium text-slate-500 dark:text-slate-400 block">Aktif Duyurular</span>
                    <div class="flex items-baseline gap-1.5 mt-1">
                        <span class="text-lg font-bold text-slate-900 dark:text-white group-hover:text-amber-500 transition-colors">{{ $activeAnnouncements }}</span>
                        <span class="text-xs text-slate-400">Yayında</span>
                    </div>
                </a>

                {{-- Hatimler --}}
                <a href="{{ $khatmsUrl }}" class="p-3 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-700/60 hover:border-slate-300 dark:hover:border-slate-600 transition-colors block group">
                    <span class="text-xs font-medium text-slate-500 dark:text-slate-400 block">Aktif Hatimler</span>
                    <div class="flex items-baseline gap-1.5 mt-1">
                        <span class="text-lg font-bold text-slate-900 dark:text-white group-hover:text-emerald-500 transition-colors">{{ $activeKhatms }}</span>
                        <span class="text-xs text-slate-400">Cüz Dağıtımında</span>
                    </div>
                </a>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
