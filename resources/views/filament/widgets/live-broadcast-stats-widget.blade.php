<x-filament-widgets::widget>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        {{-- 1. Canlı TV Kartı --}}
        <div class="fi-wi-card p-5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm relative overflow-hidden flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between gap-2 mb-3">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-rose-500/10 text-rose-500">
                            <x-heroicon-o-tv class="w-5 h-5" />
                        </span>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Dost TV Canlı Yayın</h3>
                    </div>
                    <span @class([
                        'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold border',
                        'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20' => $tvStatus === 'Aktif',
                        'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20' => $tvStatus === 'Bakımda',
                        'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20' => $tvStatus === 'Pasif',
                    ])>
                        <span @class([
                            'w-1.5 h-1.5 rounded-full mr-1.5',
                            'bg-emerald-500' => $tvStatus === 'Aktif',
                            'bg-amber-500' => $tvStatus === 'Bakımda',
                            'bg-rose-500' => $tvStatus === 'Pasif',
                        ])></span>
                        {{ $tvStatus }}
                    </span>
                </div>

                <div class="space-y-1.5 text-xs text-slate-600 dark:text-slate-400 mt-2">
                    <div class="flex items-center justify-between">
                        <span>Yayın Kaynağı:</span>
                        <span class="font-medium text-slate-800 dark:text-slate-200">{{ $tvTypeLabel }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>Public Görünürlük:</span>
                        <span class="font-medium {{ $tvIsPublic ? 'text-emerald-500' : 'text-slate-500' }}">
                            {{ $tvIsPublic ? 'Herkese Açık' : 'Gizli' }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 flex justify-end">
                <a href="{{ $tvLiveUrl }}" class="text-xs font-semibold text-rose-600 dark:text-rose-400 hover:underline inline-flex items-center gap-1">
                    Canlı Yayın Yönetimine Git &rarr;
                </a>
            </div>
        </div>

        {{-- 2. Dost FM Kartı --}}
        <div class="fi-wi-card p-5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm relative overflow-hidden flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between gap-2 mb-3">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-amber-500/10 text-amber-500">
                            <x-heroicon-o-radio class="w-5 h-5" />
                        </span>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Dost FM Canlı Radyo</h3>
                    </div>
                    <span @class([
                        'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold border',
                        'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20' => $radioStatus === 'Aktif',
                        'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20' => $radioStatus === 'Bakımda',
                        'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20' => $radioStatus === 'Pasif',
                    ])>
                        <span @class([
                            'w-1.5 h-1.5 rounded-full mr-1.5',
                            'bg-emerald-500' => $radioStatus === 'Aktif',
                            'bg-amber-500' => $radioStatus === 'Bakımda',
                            'bg-rose-500' => $radioStatus === 'Pasif',
                        ])></span>
                        {{ $radioStatus }}
                    </span>
                </div>

                <div class="space-y-1.5 text-xs text-slate-600 dark:text-slate-400 mt-2">
                    <div class="flex items-center justify-between">
                        <span>Kanal Adı:</span>
                        <span class="font-medium text-slate-800 dark:text-slate-200 truncate max-w-[150px]">{{ $radioName }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>Public Görünürlük:</span>
                        <span class="font-medium {{ $radioIsPublic ? 'text-emerald-500' : 'text-slate-500' }}">
                            {{ $radioIsPublic ? 'Herkese Açık' : 'Gizli' }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 flex justify-end">
                <a href="{{ $radioLiveUrl }}" class="text-xs font-semibold text-amber-600 dark:text-amber-400 hover:underline inline-flex items-center gap-1">
                    Canlı Yayın Yönetimine Git &rarr;
                </a>
            </div>
        </div>

        {{-- 3. Bugünkü Yayın Kartı --}}
        <div class="fi-wi-card p-5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm relative overflow-hidden flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between gap-2 mb-3">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-blue-500/10 text-blue-500">
                            <x-heroicon-o-calendar-days class="w-5 h-5" />
                        </span>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Bugünkü Yayın</h3>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20">
                        {{ $totalBroadcastsCount }} Program
                    </span>
                </div>

                <div class="space-y-1.5 text-xs text-slate-600 dark:text-slate-400 mt-2">
                    <div class="flex items-start justify-between gap-2">
                        <span class="shrink-0">Şu Anda:</span>
                        <span class="font-semibold text-rose-600 dark:text-rose-400 truncate text-right">
                            {{ $nowPlaying ? $nowPlaying->display_title : '—' }}
                        </span>
                    </div>
                    <div class="flex items-start justify-between gap-2">
                        <span class="shrink-0">Sıradaki:</span>
                        <span class="font-medium text-slate-800 dark:text-slate-200 truncate text-right">
                            @if($nextUpcoming)
                                {{ \Illuminate\Support\Carbon::parse($nextUpcoming->start_time)->format('H:i') }} · {{ $nextUpcoming->display_title }}
                            @else
                                <span class="text-slate-400">Bugünkü yayın akışı tamamlandı</span>
                            @endif
                        </span>
                    </div>
                </div>
            </div>

            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 flex justify-end">
                <a href="{{ $scheduleUrl }}" class="text-xs font-semibold text-blue-600 dark:text-blue-400 hover:underline inline-flex items-center gap-1">
                    Yayın Akışına Git &rarr;
                </a>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
