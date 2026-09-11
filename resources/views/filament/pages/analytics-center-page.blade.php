<x-filament-panels::page>
    @vite(['resources/css/app.css'])

    @php
        $data = $this->getViewData();
        $ga4 = $data['ga4Integration'];
        $ga4Metrics = $ga4?->metrics_snapshot ?? [];
        $hasGa4 = $ga4 && $ga4->is_enabled && !empty($ga4Metrics);
    @endphp

    <div class="space-y-6">
        {{-- Header Bar --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-gray-200 pb-5 dark:border-gray-800">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Analiz Merkezi</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">İzleyicilerimizi daha iyi anlayarak, daha iyi içerikler üretiyoruz.</p>
            </div>

            {{-- Date Range Selector --}}
            <div class="flex items-center gap-2 bg-white px-3 py-2 rounded-xl border border-gray-200 shadow-sm dark:bg-gray-900 dark:border-gray-800">
                <x-heroicon-o-calendar-days class="w-4 h-4 text-gray-400 shrink-0" style="width: 1rem; height: 1rem; min-width: 1rem;" />
                <select wire:model.live="dateRange" class="bg-transparent text-xs font-semibold text-gray-700 border-none p-0 focus:ring-0 dark:text-gray-200">
                    <option value="7">Son 7 Gün</option>
                    <option value="30">Son 30 Gün</option>
                    <option value="90">Son 90 Gün</option>
                </select>
            </div>
        </div>

        {{-- Navigation Tabs --}}
        <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-none border-b border-gray-200 dark:border-gray-800">
            <button type="button" wire:click="$set('activeTab', 'overview')"
                    class="flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-lg transition whitespace-nowrap {{ $activeTab === 'overview' ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/40 dark:text-blue-300 font-bold shadow-xs border border-blue-200/60 dark:border-blue-800' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800' }}">
                <x-heroicon-o-home class="w-4 h-4 shrink-0" style="width: 1rem; height: 1rem; min-width: 1rem;" />
                Genel Bakış
            </button>
            <button type="button" wire:click="$set('activeTab', 'content')"
                    class="flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-lg transition whitespace-nowrap {{ $activeTab === 'content' ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/40 dark:text-blue-300 font-bold shadow-xs border border-blue-200/60 dark:border-blue-800' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800' }}">
                <x-heroicon-o-document-text class="w-4 h-4 shrink-0" style="width: 1rem; height: 1rem; min-width: 1rem;" />
                İçerik
            </button>
            <button type="button" wire:click="$set('activeTab', 'searches')"
                    class="flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-lg transition whitespace-nowrap {{ $activeTab === 'searches' ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/40 dark:text-blue-300 font-bold shadow-xs border border-blue-200/60 dark:border-blue-800' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800' }}">
                <x-heroicon-o-magnifying-glass class="w-4 h-4 shrink-0" style="width: 1rem; height: 1rem; min-width: 1rem;" />
                Aramalar
            </button>
            <button type="button" wire:click="$set('activeTab', 'technical')"
                    class="flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-lg transition whitespace-nowrap {{ $activeTab === 'technical' ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/40 dark:text-blue-300 font-bold shadow-xs border border-blue-200/60 dark:border-blue-800' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800' }}">
                <x-heroicon-o-exclamation-triangle class="w-4 h-4 shrink-0" style="width: 1rem; height: 1rem; min-width: 1rem;" />
                Teknik (404)
            </button>
            <button type="button" wire:click="$set('activeTab', 'ads')"
                    class="flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-lg transition whitespace-nowrap {{ $activeTab === 'ads' ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/40 dark:text-blue-300 font-bold shadow-xs border border-blue-200/60 dark:border-blue-800' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800' }}">
                <x-heroicon-o-chart-pie class="w-4 h-4 shrink-0" style="width: 1rem; height: 1rem; min-width: 1rem;" />
                Reklamlar
            </button>
            <button type="button" wire:click="$set('activeTab', 'integrations')"
                    class="flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-lg transition whitespace-nowrap {{ $activeTab === 'integrations' ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/40 dark:text-blue-300 font-bold shadow-xs border border-blue-200/60 dark:border-blue-800' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800' }}">
                <x-heroicon-o-cpu-chip class="w-4 h-4 shrink-0" style="width: 1rem; height: 1rem; min-width: 1rem;" />
                Entegrasyonlar
            </button>
        </div>

        {{-- TAB: GENEL BAKIŞ --}}
        @if ($activeTab === 'overview')
            {{-- KPI Cards Row (5 Columns) --}}
            <div class="grid gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5">
                {{-- KPI 1: Toplam Ziyaretçi --}}
                <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center gap-2 text-gray-500 dark:text-gray-400">
                        <div class="p-1.5 rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-950 dark:text-blue-400">
                            <x-heroicon-o-user-group class="w-4 h-4 shrink-0" style="width: 1rem; height: 1rem; min-width: 1rem;" />
                        </div>
                        <span class="text-xs font-semibold">Toplam Ziyaretçi</span>
                    </div>
                    <div class="mt-3">
                        @if ($hasGa4 && isset($ga4Metrics['total_users']))
                            <div class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">{{ number_format($ga4Metrics['total_users']) }}</div>
                            <div class="mt-1 text-[11px] font-medium text-emerald-600">GA4 Gerçek Veri</div>
                        @else
                            <div class="text-lg font-bold text-gray-400 dark:text-gray-500">—</div>
                            <div class="mt-1 text-[11px] text-gray-400">GA4 verisi bekleniyor</div>
                        @endif
                    </div>
                </div>

                {{-- KPI 2: Toplam Sayfa Görüntüleme --}}
                <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center gap-2 text-gray-500 dark:text-gray-400">
                        <div class="p-1.5 rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-950 dark:text-indigo-400">
                            <x-heroicon-o-eye class="w-4 h-4 shrink-0" style="width: 1rem; height: 1rem; min-width: 1rem;" />
                        </div>
                        <span class="text-xs font-semibold">Sayfa Görüntüleme</span>
                    </div>
                    <div class="mt-3">
                        @if ($hasGa4 && isset($ga4Metrics['pageviews']))
                            <div class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">{{ number_format($ga4Metrics['pageviews']) }}</div>
                            <div class="mt-1 text-[11px] font-medium text-emerald-600">GA4 Gerçek Veri</div>
                        @else
                            <div class="text-lg font-bold text-gray-400 dark:text-gray-500">—</div>
                            <div class="mt-1 text-[11px] text-gray-400">GA4 verisi bekleniyor</div>
                        @endif
                    </div>
                </div>

                {{-- KPI 3: Ortalama Oturum Süresi --}}
                <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center gap-2 text-gray-500 dark:text-gray-400">
                        <div class="p-1.5 rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-950 dark:text-amber-400">
                            <x-heroicon-o-clock class="w-4 h-4 shrink-0" style="width: 1rem; height: 1rem; min-width: 1rem;" />
                        </div>
                        <span class="text-xs font-semibold">Ortalama Oturum</span>
                    </div>
                    <div class="mt-3">
                        @if ($hasGa4 && isset($ga4Metrics['avg_session_duration']))
                            <div class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">{{ $ga4Metrics['avg_session_duration'] }}</div>
                            <div class="mt-1 text-[11px] font-medium text-emerald-600">GA4 Gerçek Veri</div>
                        @else
                            <div class="text-lg font-bold text-gray-400 dark:text-gray-500">—</div>
                            <div class="mt-1 text-[11px] text-gray-400">GA4 verisi bekleniyor</div>
                        @endif
                    </div>
                </div>

                {{-- KPI 4: Toplam Arama Sayısı --}}
                <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center gap-2 text-gray-500 dark:text-gray-400">
                        <div class="p-1.5 rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400">
                            <x-heroicon-o-magnifying-glass class="w-4 h-4 shrink-0" style="width: 1rem; height: 1rem; min-width: 1rem;" />
                        </div>
                        <span class="text-xs font-semibold">Toplam Arama Sayısı</span>
                    </div>
                    <div class="mt-3">
                        <div class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">{{ number_format($data['totalSearchesCount']) }}</div>
                        <div class="mt-1 text-[11px] text-gray-400">Son {{ $data['days'] }} güne göre</div>
                    </div>
                </div>

                {{-- KPI 5: Sonuçsuz Aramalar --}}
                <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center gap-2 text-gray-500 dark:text-gray-400">
                        <div class="p-1.5 rounded-lg bg-rose-50 text-rose-600 dark:bg-rose-950 dark:text-rose-400">
                            <x-heroicon-o-exclamation-circle class="w-4 h-4 shrink-0" style="width: 1rem; height: 1rem; min-width: 1rem;" />
                        </div>
                        <span class="text-xs font-semibold">Sonuçsuz Aramalar</span>
                    </div>
                    <div class="mt-3">
                        <div class="text-2xl font-bold tracking-tight text-rose-600 dark:text-rose-400">{{ number_format($data['zeroResultCount']) }}</div>
                        <div class="mt-1 text-[11px] text-gray-400">Son {{ $data['days'] }} güne göre</div>
                    </div>
                </div>
            </div>

            {{-- Charts & Breakdowns Row --}}
            <div class="grid gap-6 lg:grid-cols-12">
                {{-- Main Traffic Chart (6 cols) --}}
                <div class="lg:col-span-6 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-3 dark:border-gray-800">
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white">Ziyaretçi ve Sayfa Görüntüleme</h3>
                        <div class="flex items-center gap-4 text-xs">
                            <span class="flex items-center gap-1.5 text-blue-600 font-semibold"><span class="w-2.5 h-2.5 rounded-full bg-blue-600 inline-block" style="width:0.625rem;height:0.625rem;"></span> Ziyaretçi</span>
                            <span class="flex items-center gap-1.5 text-purple-600 font-semibold"><span class="w-2.5 h-2.5 rounded-full bg-purple-500 inline-block" style="width:0.625rem;height:0.625rem;"></span> Sayfa Görüntüleme</span>
                        </div>
                    </div>

                    <div class="mt-4 h-48 flex items-center justify-center rounded-xl bg-gray-50/50 p-4 border border-dashed border-gray-200 dark:bg-gray-800/30 dark:border-gray-700 overflow-hidden">
                        @if ($hasGa4 && !empty($ga4Metrics['top_pages']))
                            <div class="w-full h-full flex flex-col justify-between">
                                <div class="text-xs font-semibold text-gray-500">GA4 Zaman Serisi (Örnek Metrikler)</div>
                                <svg class="w-full h-28" viewBox="0 0 500 100" preserveAspectRatio="none" style="max-height: 7rem;">
                                    <path d="M0,80 Q100,20 200,60 T400,30 T500,50" fill="none" stroke="#2563eb" stroke-width="3"/>
                                    <path d="M0,90 Q100,40 200,80 T400,50 T500,70" fill="none" stroke="#a855f7" stroke-width="3"/>
                                </svg>
                                <div class="flex justify-between text-[10px] text-gray-400">
                                    <span>Son {{ $data['days'] }} Gün Başı</span>
                                    <span>Bugün</span>
                                </div>
                            </div>
                        @else
                            <div class="text-center">
                                <x-heroicon-o-chart-bar class="mx-auto w-8 h-8 text-gray-300 dark:text-gray-600" style="width: 2rem; height: 2rem; min-width: 2rem;" />
                                <p class="mt-2 text-xs font-semibold text-gray-500 dark:text-gray-400">GA4 Verisi Bekleniyor</p>
                                <p class="text-[11px] text-gray-400">GA4 API senkronize edildiğinde zaman serisi grafiği burada görünecektir.</p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Device Distribution (3 cols) --}}
                <div class="lg:col-span-3 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white border-b border-gray-100 pb-3 dark:border-gray-800">Cihaz Dağılımı</h3>

                    <div class="mt-4 flex flex-col items-center">
                        <div class="relative w-28 h-28 flex items-center justify-center" style="width: 7rem; height: 7rem;">
                            <svg class="w-full h-full" viewBox="0 0 36 36" style="width: 7rem; height: 7rem;">
                                <path class="text-gray-100 dark:text-gray-800" stroke-width="3.8" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="text-blue-600" stroke-dasharray="{{ $data['deviceStats']['mobile_pct'] }}, 100" stroke-width="3.8" stroke-linecap="round" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <div class="absolute text-center">
                                <span class="text-lg font-bold text-gray-900 dark:text-white">%{{ $data['deviceStats']['mobile_pct'] }}</span>
                                <span class="block text-[10px] text-gray-400">Mobil</span>
                            </div>
                        </div>

                        <div class="mt-4 w-full space-y-2 text-xs">
                            <div class="flex items-center justify-between">
                                <span class="flex items-center gap-1.5 text-gray-600 dark:text-gray-300"><span class="w-2.5 h-2.5 rounded-full bg-blue-600 inline-block" style="width:0.625rem;height:0.625rem;"></span> Mobil</span>
                                <span class="font-bold text-gray-900 dark:text-white">%{{ $data['deviceStats']['mobile_pct'] }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="flex items-center gap-1.5 text-gray-600 dark:text-gray-300"><span class="w-2.5 h-2.5 rounded-full bg-purple-500 inline-block" style="width:0.625rem;height:0.625rem;"></span> Masaüstü</span>
                                <span class="font-bold text-gray-900 dark:text-white">%{{ $data['deviceStats']['desktop_pct'] }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="flex items-center gap-1.5 text-gray-600 dark:text-gray-300"><span class="w-2.5 h-2.5 rounded-full bg-amber-400 inline-block" style="width:0.625rem;height:0.625rem;"></span> Tablet</span>
                                <span class="font-bold text-gray-900 dark:text-white">%{{ $data['deviceStats']['tablet_pct'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Traffic Sources Breakdown (3 cols) --}}
                <div class="lg:col-span-3 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white border-b border-gray-100 pb-3 dark:border-gray-800">Trafik Kaynağı</h3>

                    <div class="mt-4 space-y-3">
                        @if ($hasGa4 && !empty($ga4Metrics['traffic_sources']))
                            @foreach ($ga4Metrics['traffic_sources'] as $src)
                                <div>
                                    <div class="flex justify-between text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                        <span>{{ $src['source'] }}</span>
                                        <span>{{ number_format($src['users']) }}</span>
                                    </div>
                                    <div class="h-2 w-full rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden">
                                        <div class="h-full bg-blue-600 rounded-full" style="width: 45%"></div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="py-6 text-center">
                                <x-heroicon-o-arrow-path class="mx-auto w-6 h-6 text-gray-300 dark:text-gray-600" style="width: 1.5rem; height: 1.5rem; min-width: 1.5rem;" />
                                <p class="mt-2 text-xs font-semibold text-gray-500 dark:text-gray-400">GA4 Trafik Kaynağı</p>
                                <p class="text-[11px] text-gray-400">Senkronize edildiğinde gösterilir.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Integrations Status Cards Row --}}
            <div class="grid gap-4 md:grid-cols-3">
                {{-- GA4 Card --}}
                <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900 flex items-start gap-4">
                    <div class="p-3 rounded-xl bg-orange-50 text-orange-600 dark:bg-orange-950 dark:text-orange-400 shrink-0">
                        <x-heroicon-o-chart-bar class="w-6 h-6" style="width: 1.5rem; height: 1.5rem; min-width: 1.5rem;" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-2">
                            <h4 class="text-sm font-bold text-gray-900 dark:text-white truncate">Google Analytics 4</h4>
                            @if ($ga4?->is_enabled && !$ga4?->last_error)
                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300 shrink-0">Bağlı</span>
                            @elseif($ga4?->last_error)
                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300 shrink-0">Hata</span>
                            @else
                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300 shrink-0">Bağlı Değil</span>
                            @endif
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 truncate">{{ $ga4?->property_id ? 'DOST TV - GA4 (' . $ga4->property_id . ')' : 'DOST TV - GA4' }}</p>
                        <p class="mt-2 text-[11px] text-gray-400">Son senkronizasyon: {{ $ga4?->last_synced_at ? $ga4->last_synced_at->diffForHumans() : 'Yapılmadı' }}</p>
                    </div>
                </div>

                {{-- Google Ads Card --}}
                <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900 flex items-start gap-4">
                    <div class="p-3 rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-950 dark:text-blue-400 shrink-0">
                        <x-heroicon-o-megaphone class="w-6 h-6" style="width: 1.5rem; height: 1.5rem; min-width: 1.5rem;" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-2">
                            <h4 class="text-sm font-bold text-gray-900 dark:text-white truncate">Google Ads</h4>
                            <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 shrink-0">Hazırlık Aşamasında</span>
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 truncate">Henüz bağlanmadı</p>
                        <p class="mt-2 text-[11px] text-gray-400">Reklam performansınızı yakında burada görebilirsiniz.</p>
                    </div>
                </div>

                {{-- Meta Ads Card --}}
                <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900 flex items-start gap-4">
                    <div class="p-3 rounded-xl bg-purple-50 text-purple-600 dark:bg-purple-950 dark:text-purple-400 shrink-0">
                        <x-heroicon-o-share class="w-6 h-6" style="width: 1.5rem; height: 1.5rem; min-width: 1.5rem;" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-2">
                            <h4 class="text-sm font-bold text-gray-900 dark:text-white truncate">Meta Ads</h4>
                            <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 shrink-0">Hazırlık Aşamasında</span>
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 truncate">Henüz bağlanmadı</p>
                        <p class="mt-2 text-[11px] text-gray-400">Reklam performansınızı yakında burada görebilirsiniz.</p>
                    </div>
                </div>
            </div>

            {{-- 3 Tables Grid Row --}}
            <div class="grid gap-6 lg:grid-cols-3">
                {{-- En Çok İzlenen Programlar --}}
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-3 dark:border-gray-800">
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white">En Çok İzlenen Programlar</h3>
                        <span class="text-xs font-semibold text-blue-600">Tümünü Gör &rarr;</span>
                    </div>
                    <div class="mt-3 divide-y divide-gray-100 dark:divide-gray-800">
                        @if ($hasGa4 && !empty($ga4Metrics['top_pages']))
                            @foreach (array_slice($ga4Metrics['top_pages'], 0, 5) as $idx => $pg)
                                <div class="flex items-center justify-between py-2.5 text-xs">
                                    <div class="flex items-center gap-3">
                                        <span class="font-bold text-gray-400 w-4">{{ $idx + 1 }}</span>
                                        <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $pg['path'] }}</span>
                                    </div>
                                    <span class="font-bold text-gray-900 dark:text-white">{{ number_format($pg['views']) }}</span>
                                </div>
                            @endforeach
                        @else
                            <div class="py-8 text-center text-xs text-gray-400">
                                GA4 izlenme verisi bekleniyor.
                            </div>
                        @endif
                    </div>
                </div>

                {{-- En Çok Aranan Kelimeler --}}
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-3 dark:border-gray-800">
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white">En Çok Aranan Kelimeler</h3>
                        <button type="button" wire:click="$set('activeTab', 'searches')" class="text-xs font-semibold text-blue-600 hover:underline">Tümünü Gör &rarr;</button>
                    </div>
                    <div class="mt-3 divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($data['topSearches']->take(5) as $idx => $search)
                            <div class="flex items-center justify-between py-2.5 text-xs">
                                <div class="flex items-center gap-3">
                                    <span class="font-bold text-gray-400 w-4">{{ $idx + 1 }}</span>
                                    <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $search->search_query }}</span>
                                </div>
                                <span class="font-bold text-gray-900 dark:text-white">{{ number_format($search->total_count) }}</span>
                            </div>
                        @empty
                            <div class="py-8 text-center text-xs text-gray-400">Henüz arama kaydı bulunmuyor.</div>
                        @endforelse
                    </div>
                </div>

                {{-- Sonuç Bulunamayan Aramalar --}}
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-3 dark:border-gray-800">
                        <h3 class="text-sm font-bold text-rose-600 dark:text-rose-400">Sonuç Bulunamayan Aramalar</h3>
                        <button type="button" wire:click="$set('activeTab', 'searches')" class="text-xs font-semibold text-rose-600 hover:underline">Tümünü Gör &rarr;</button>
                    </div>
                    <div class="mt-3 divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($data['zeroResultSearches']->take(5) as $idx => $zero)
                            <div class="flex items-center justify-between py-2.5 text-xs">
                                <div class="flex items-center gap-3">
                                    <span class="font-bold text-rose-400 w-4">{{ $idx + 1 }}</span>
                                    <span class="font-semibold text-rose-600 dark:text-rose-300">{{ $zero->search_query }}</span>
                                </div>
                                <span class="font-bold text-rose-600 dark:text-rose-400">{{ number_format($zero->total_count) }}</span>
                            </div>
                        @empty
                            <div class="py-8 text-center text-xs text-gray-400">Sonuçsuz arama bulunmuyor.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Bottom 2 Tables Grid Row --}}
            <div class="grid gap-6 lg:grid-cols-2">
                {{-- Site İçi Önemli Tıklamalar --}}
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-3 dark:border-gray-800">
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white">Site İçi Önemli Tıklamalar</h3>
                        <button type="button" wire:click="$set('activeTab', 'content')" class="text-xs font-semibold text-blue-600 hover:underline">Tümünü Gör &rarr;</button>
                    </div>
                    <div class="mt-3 divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($data['topEvents']->take(5) as $event)
                            <div class="flex items-center justify-between py-2.5 text-xs">
                                <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $event->label }}</span>
                                <span class="font-bold text-blue-600 dark:text-blue-400">{{ number_format($event->total_count) }}</span>
                            </div>
                        @empty
                            <div class="py-6 text-center text-xs text-gray-400">Henüz kaydedilmiş tıklama bulunmuyor.</div>
                        @endforelse
                    </div>
                </div>

                {{-- En Çok 404 Alınan URL'ler --}}
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-3 dark:border-gray-800">
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white">En Çok 404 Alınan URL'ler</h3>
                        <button type="button" wire:click="$set('activeTab', 'technical')" class="text-xs font-semibold text-blue-600 hover:underline">Tümünü Gör &rarr;</button>
                    </div>
                    <div class="mt-3 divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($data['notFoundLogs']->take(5) as $idx => $log)
                            <div class="flex items-center justify-between py-2.5 text-xs">
                                <div class="flex items-center gap-3">
                                    <span class="font-bold text-gray-400 w-4">{{ $idx + 1 }}</span>
                                    <span class="font-mono font-medium text-rose-500">{{ $log->path }}</span>
                                </div>
                                <span class="font-bold text-gray-900 dark:text-white">{{ number_format($log->hit_count) }}</span>
                            </div>
                        @empty
                            <div class="py-6 text-center text-xs text-gray-400">404 hatası kaydedilmedi.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        @endif

        {{-- TAB: İÇERİK TIKLAMALARI --}}
        @if ($activeTab === 'content')
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white border-b border-gray-100 pb-3 dark:border-gray-800">Site İçi Etkinlik ve Tıklama Detayları</h3>
                <div class="mt-4 divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($data['topEvents'] as $event)
                        <div class="flex items-center justify-between py-3 text-xs">
                            <div>
                                <div class="font-semibold text-gray-900 dark:text-white">{{ $event->label }}</div>
                                <div class="font-mono text-[10px] text-gray-400">{{ $event->event_name }}</div>
                            </div>
                            <span class="rounded-lg bg-blue-50 px-3 py-1 font-bold text-blue-600 dark:bg-blue-950 dark:text-blue-400">{{ number_format($event->total_count) }} tıklama</span>
                        </div>
                    @empty
                        <div class="py-8 text-center text-xs text-gray-400">Henüz kaydedilmiş etkinlik bulunmuyor.</div>
                    @endforelse
                </div>
            </div>
        @endif

        {{-- TAB: ARAMALAR --}}
        @if ($activeTab === 'searches')
            <div class="space-y-6">
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white border-b border-gray-100 pb-3 dark:border-gray-800">En Çok Aranan Kelimeler (Son {{ $data['days'] }} Gün)</h3>
                    <div class="mt-4 overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="border-b border-gray-200 text-gray-500 dark:border-gray-800 dark:text-gray-400">
                                    <th class="pb-2 font-semibold">Sıra</th>
                                    <th class="pb-2 font-semibold">Arama Sorgusu</th>
                                    <th class="pb-2 font-semibold">Toplam Arama</th>
                                    <th class="pb-2 font-semibold">Ortalama Sonuç</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @forelse ($data['topSearches'] as $idx => $search)
                                    <tr>
                                        <td class="py-2.5 font-bold text-gray-400 w-8">{{ $idx + 1 }}</td>
                                        <td class="py-2.5 font-semibold text-gray-800 dark:text-gray-200">{{ $search->search_query }}</td>
                                        <td class="py-2.5 font-bold text-gray-900 dark:text-white">{{ number_format($search->total_count) }}</td>
                                        <td class="py-2.5 text-gray-500 dark:text-gray-400">{{ round($search->avg_results, 1) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="py-4 text-center text-gray-400">Kayıt bulunmuyor.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <h3 class="text-sm font-bold text-rose-600 dark:text-rose-400 border-b border-gray-100 pb-3 dark:border-gray-800">Sonuç Bulunamayan Aramalar (0 Sonuç)</h3>
                    <div class="mt-4 overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="border-b border-gray-200 text-gray-500 dark:border-gray-800 dark:text-gray-400">
                                    <th class="pb-2 font-semibold">Sıra</th>
                                    <th class="pb-2 font-semibold">Aranan Sorgu</th>
                                    <th class="pb-2 font-semibold">Tekrar Sayısı</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @forelse ($data['zeroResultSearches'] as $idx => $zero)
                                    <tr>
                                        <td class="py-2.5 font-bold text-rose-400 w-8">{{ $idx + 1 }}</td>
                                        <td class="py-2.5 font-semibold text-rose-600 dark:text-rose-400">{{ $zero->search_query }}</td>
                                        <td class="py-2.5 font-bold text-gray-900 dark:text-white">{{ number_format($zero->total_count) }} kez</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="py-4 text-center text-gray-400">Sonuçsuz arama kaydı yok.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        {{-- TAB: TEKNİK (404) --}}
        @if ($activeTab === 'technical')
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white border-b border-gray-100 pb-3 dark:border-gray-800">En Çok 404 (Sayfa Bulunamadı) Alınan URL'ler</h3>
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-gray-200 text-gray-500 dark:border-gray-800 dark:text-gray-400">
                                <th class="pb-2 font-semibold">Sıra</th>
                                <th class="pb-2 font-semibold">URL Path</th>
                                <th class="pb-2 font-semibold">Yönlendiren (Referer)</th>
                                <th class="pb-2 font-semibold">Hit Sayısı</th>
                                <th class="pb-2 font-semibold">Son Görülme</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse ($data['notFoundLogs'] as $idx => $log)
                                <tr>
                                    <td class="py-2.5 font-bold text-gray-400 w-8">{{ $idx + 1 }}</td>
                                    <td class="py-2.5 font-mono text-xs font-semibold text-rose-500">{{ $log->path }}</td>
                                    <td class="py-2.5 text-gray-500 dark:text-gray-400">{{ $log->referer ?: 'Direkt / Bilinmiyor' }}</td>
                                    <td class="py-2.5 font-bold text-gray-900 dark:text-white">{{ number_format($log->hit_count) }}</td>
                                    <td class="py-2.5 text-gray-400">{{ $log->last_occurred_at ? $log->last_occurred_at->diffForHumans() : '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="py-4 text-center text-gray-400">404 hata kaydı bulunmuyor.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- TAB: REKLAMLAR --}}
        @if ($activeTab === 'ads')
            <div class="rounded-2xl border border-gray-200 bg-white p-6 text-center dark:border-gray-800 dark:bg-gray-900">
                <div class="mx-auto w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center dark:bg-amber-950 dark:text-amber-400" style="width:3rem;height:3rem;">
                    <x-heroicon-o-chart-pie class="w-6 h-6" style="width: 1.5rem; height: 1.5rem; min-width: 1.5rem;" />
                </div>
                <h3 class="mt-3 text-base font-bold text-gray-900 dark:text-white">Reklam Entegrasyonları (Hazırlık Aşamasında)</h3>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 max-w-md mx-auto">Google Ads ve Meta Ads entegrasyon altyapısı bir sonraki fazda aktif edilecektir. Sahte reklam verisi üretilmemektedir.</p>
            </div>
        @endif

        {{-- TAB: ENTEGRASYONLAR --}}
        @if ($activeTab === 'integrations')
            <div class="space-y-6">
                <form wire:submit="saveGa4Settings">
                    {{ $this->form }}

                    <div class="mt-4 flex items-center gap-3">
                        <button type="submit" class="rounded-xl bg-blue-600 px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-blue-700 transition">
                            GA4 Ayarlarını Kaydet
                        </button>

                        <button type="button" wire:click="syncGa4Now" class="rounded-xl border border-gray-300 bg-white px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 transition">
                            Şimdi GA4 Verilerini Senkronize Et
                        </button>
                    </div>
                </form>

                @if ($ga4?->last_error)
                    <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-xs text-rose-700 dark:border-rose-900/50 dark:bg-rose-900/20 dark:text-rose-300">
                        <span class="font-bold">Son Entegrasyon Hatası:</span> {{ $ga4->last_error }}
                    </div>
                @endif
            </div>
        @endif
    </div>
</x-filament-panels::page>
