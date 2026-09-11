<x-filament-panels::page>
    @vite(['resources/css/app.css'])

    @php
        $data = $this->getViewData();

        $ga4 = $data['ga4Integration'];
        $ga4Metrics = $data['activeGa4Metrics'] ?? [];
        $hasGa4 = $ga4 && $ga4->is_enabled && !empty($ga4Metrics);

        $gads = $data['gadsIntegration'];
        $gadsMetrics = $data['activeGadsMetrics'] ?? [];
        $hasGads = $gads && $gads->is_enabled && !empty($gadsMetrics);

        $meta = $data['metaIntegration'];
        $metaMetrics = $data['activeMetaMetrics'] ?? [];
        $hasMeta = $meta && $meta->is_enabled && !empty($metaMetrics);

        $insightsData = $data['insights'] ?? [];
        $topPriority = $insightsData['top_priority'] ?? [];
        $opportunities = $insightsData['opportunities'] ?? [];
        $attentionNeeded = $insightsData['attention_needed'] ?? [];
        $byCategory = $insightsData['by_category'] ?? [];
        $insightsSummary = $insightsData['summary'] ?? [];
        $useFixture = $data['useFixture'] ?? false;
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
            <button type="button" wire:click="$set('activeTab', 'insights')"
                    class="flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-lg transition whitespace-nowrap {{ $activeTab === 'insights' ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/40 dark:text-blue-300 font-bold shadow-xs border border-blue-200/60 dark:border-blue-800' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800' }}">
                <x-heroicon-o-light-bulb class="w-4 h-4 shrink-0" style="width: 1rem; height: 1rem; min-width: 1rem;" />
                İçgörüler
                @if (($insightsSummary['warning_count'] ?? 0) + ($insightsSummary['critical_count'] ?? 0) > 0)
                    <span class="px-1.5 py-0.5 text-[10px] font-bold rounded-full bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300">
                        {{ ($insightsSummary['warning_count'] ?? 0) + ($insightsSummary['critical_count'] ?? 0) }}
                    </span>
                @endif
            </button>
            <button type="button" wire:click="$set('activeTab', 'actions')"
                    class="flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-lg transition whitespace-nowrap {{ $activeTab === 'actions' ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/40 dark:text-blue-300 font-bold shadow-xs border border-blue-200/60 dark:border-blue-800' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800' }}">
                <x-heroicon-o-shield-check class="w-4 h-4 shrink-0" style="width: 1rem; height: 1rem; min-width: 1rem;" />
                Aksiyonlar
                @if (($data['alertKpis']['total_open'] ?? 0) > 0)
                    <span class="px-1.5 py-0.5 text-[10px] font-bold rounded-full bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300">
                        {{ $data['alertKpis']['total_open'] }}
                    </span>
                @endif
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
            {{-- Insights Quick Summary Card --}}
            @if (!empty($topPriority))
                <div class="rounded-2xl border border-blue-100 bg-gradient-to-r from-blue-50/80 to-indigo-50/50 p-4 dark:border-blue-900/40 dark:from-blue-950/40 dark:to-gray-900 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="p-2.5 rounded-xl bg-blue-600 text-white shrink-0 shadow-sm">
                            <x-heroicon-o-light-bulb class="w-5 h-5" style="width:1.25rem;height:1.25rem;" />
                        </div>
                        <div class="min-w-0">
                            <h4 class="text-xs font-bold text-gray-900 dark:text-white truncate">
                                Öne Çıkan İçgörü: {{ $topPriority[0]['title'] }}
                            </h4>
                            <p class="mt-0.5 text-[11px] text-gray-600 dark:text-gray-300 truncate">
                                {{ $topPriority[0]['description'] }}
                            </p>
                        </div>
                    </div>
                    <button type="button" wire:click="$set('activeTab', 'insights')" class="shrink-0 rounded-xl bg-blue-600 px-3.5 py-2 text-xs font-bold text-white shadow-xs hover:bg-blue-700 transition">
                        Tüm İçgörüleri Gör ({{ $insightsSummary['total_count'] ?? 0 }}) &rarr;
                    </button>
                </div>
            @endif

            @if (($data['alertKpis']['total_open'] ?? 0) > 0)
                <div class="rounded-2xl border border-rose-100 bg-gradient-to-r from-rose-50/80 to-amber-50/50 p-4 dark:border-rose-900/40 dark:from-rose-950/40 dark:to-gray-900 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="p-2.5 rounded-xl bg-rose-600 text-white shrink-0 shadow-sm">
                            <x-heroicon-o-shield-exclamation class="w-5 h-5" style="width:1.25rem;height:1.25rem;" />
                        </div>
                        <div class="min-w-0">
                            <h4 class="text-xs font-bold text-gray-900 dark:text-white truncate">
                                Takip Edilen {{ $data['alertKpis']['total_open'] }} Açık Aksiyon Bulunuyor
                            </h4>
                            <p class="mt-0.5 text-[11px] text-gray-600 dark:text-gray-300 truncate">
                                {{ ($data['alertKpis']['critical_open'] ?? 0) > 0 ? $data['alertKpis']['critical_open'] . ' kritik seviye uyarı müdahale bekliyor.' : 'Sistem tarafından tespit edilen aksiyonları inceleyebilirsiniz.' }}
                            </p>
                        </div>
                    </div>
                    <button type="button" wire:click="$set('activeTab', 'actions')" class="shrink-0 rounded-xl bg-rose-600 px-3.5 py-2 text-xs font-bold text-white shadow-xs hover:bg-rose-700 transition">
                        Aksiyonları İncele &rarr;
                    </button>
                </div>
            @endif

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
                        @if ($hasGa4 && !empty($ga4Metrics['timeseries']))
                            @php
                                $ts = $ga4Metrics['timeseries'];
                                $tsCount = count($ts);
                                $maxUsers = max(array_column($ts, 'users') ?: [1]);
                                $maxViews = max(array_column($ts, 'pageviews') ?: [1]);
                                $uPoints = [];
                                $vPoints = [];
                                foreach ($ts as $idx => $pt) {
                                    $x = $tsCount > 1 ? round(($idx / ($tsCount - 1)) * 500) : 250;
                                    $yu = round(90 - (($pt['users'] / max(1, $maxUsers)) * 75));
                                    $yv = round(90 - (($pt['pageviews'] / max(1, $maxViews)) * 75));
                                    $uPoints[] = "{$x},{$yu}";
                                    $vPoints[] = "{$x},{$yv}";
                                }
                                $uPath = !empty($uPoints) ? 'M' . implode(' L', $uPoints) : '';
                                $vPath = !empty($vPoints) ? 'M' . implode(' L', $vPoints) : '';
                            @endphp
                            <div class="w-full h-full flex flex-col justify-between">
                                <div class="text-xs font-semibold text-gray-500">GA4 Zaman Serisi (Gerçek Veri)</div>
                                <svg class="w-full h-28" viewBox="0 0 500 100" preserveAspectRatio="none" style="max-height: 7rem;">
                                    <path d="{{ $uPath }}" fill="none" stroke="#2563eb" stroke-width="3"/>
                                    <path d="{{ $vPath }}" fill="none" stroke="#a855f7" stroke-width="3"/>
                                </svg>
                                <div class="flex justify-between text-[10px] text-gray-400">
                                    <span>{{ $ts[0]['date'] ?? "Son {$data['days']} Gün Başı" }}</span>
                                    <span>{{ $ts[$tsCount - 1]['date'] ?? 'Dün' }}</span>
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

                    @php
                        $devices = $ga4Metrics['devices'] ?? [];
                        $hasDeviceData = $hasGa4 && !empty($devices) && ($devices['total_users'] ?? 0) > 0;
                        $mobPct = $hasDeviceData ? ($devices['mobile']['percentage'] ?? 0) : 0;
                        $deskPct = $hasDeviceData ? ($devices['desktop']['percentage'] ?? 0) : 0;
                        $tabPct = $hasDeviceData ? ($devices['tablet']['percentage'] ?? 0) : 0;
                    @endphp

                    @if ($hasDeviceData)
                        <div class="mt-4 flex flex-col items-center">
                            <div class="relative w-28 h-28 flex items-center justify-center" style="width: 7rem; height: 7rem;">
                                <svg class="w-full h-full" viewBox="0 0 36 36" style="width: 7rem; height: 7rem;">
                                    <path class="text-gray-100 dark:text-gray-800" stroke-width="3.8" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                    <path class="text-blue-600" stroke-dasharray="{{ $mobPct }}, 100" stroke-width="3.8" stroke-linecap="round" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                </svg>
                                <div class="absolute text-center">
                                    <span class="text-lg font-bold text-gray-900 dark:text-white">%{{ $mobPct }}</span>
                                    <span class="block text-[10px] text-gray-400">Mobil</span>
                                </div>
                            </div>

                            <div class="mt-4 w-full space-y-2 text-xs">
                                <div class="flex items-center justify-between">
                                    <span class="flex items-center gap-1.5 text-gray-600 dark:text-gray-300"><span class="w-2.5 h-2.5 rounded-full bg-blue-600 inline-block" style="width:0.625rem;height:0.625rem;"></span> Mobil</span>
                                    <span class="font-bold text-gray-900 dark:text-white">%{{ $mobPct }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="flex items-center gap-1.5 text-gray-600 dark:text-gray-300"><span class="w-2.5 h-2.5 rounded-full bg-purple-500 inline-block" style="width:0.625rem;height:0.625rem;"></span> Masaüstü</span>
                                    <span class="font-bold text-gray-900 dark:text-white">%{{ $deskPct }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="flex items-center gap-1.5 text-gray-600 dark:text-gray-300"><span class="w-2.5 h-2.5 rounded-full bg-amber-400 inline-block" style="width:0.625rem;height:0.625rem;"></span> Tablet</span>
                                    <span class="font-bold text-gray-900 dark:text-white">%{{ $tabPct }}</span>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="py-12 text-center">
                            <x-heroicon-o-device-phone-mobile class="mx-auto w-7 h-7 text-gray-300 dark:text-gray-600" style="width: 1.75rem; height: 1.75rem; min-width: 1.75rem;" />
                            <p class="mt-2 text-xs font-semibold text-gray-500 dark:text-gray-400">GA4 Cihaz Verisi Yok</p>
                            <p class="text-[11px] text-gray-400">GA4 verisi bekleniyor.</p>
                        </div>
                    @endif
                </div>

                {{-- Traffic Sources Breakdown (3 cols) --}}
                <div class="lg:col-span-3 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white border-b border-gray-100 pb-3 dark:border-gray-800">Trafik Kaynağı</h3>

                    @php
                        $trafficSources = $ga4Metrics['traffic_sources'] ?? [];
                        $maxSessions = max(array_column($trafficSources, 'sessions') ?: [1]);
                    @endphp

                    <div class="mt-4 space-y-3">
                        @if ($hasGa4 && !empty($trafficSources))
                            @foreach (array_slice($trafficSources, 0, 5) as $src)
                                @php
                                    $pct = round(($src['sessions'] / max(1, $maxSessions)) * 100);
                                @endphp
                                <div>
                                    <div class="flex justify-between text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                        <span>{{ $src['source'] }}</span>
                                        <span>{{ number_format($src['sessions']) }}</span>
                                    </div>
                                    <div class="h-2 w-full rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden">
                                        <div class="h-full bg-blue-600 rounded-full" style="width: {{ $pct }}%"></div>
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
                            @if ($gads?->is_enabled && !$gads?->last_error)
                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300 shrink-0">Bağlı</span>
                            @elseif($gads?->last_error)
                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300 shrink-0">Hata</span>
                            @else
                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300 shrink-0">Bağlı Değil</span>
                            @endif
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 truncate">{{ $gads?->property_id ? 'Customer ID: ' . $gads->property_id : 'DOST TV - Google Ads' }}</p>
                        <p class="mt-2 text-[11px] text-gray-400">Son senkronizasyon: {{ $gads?->last_synced_at ? $gads->last_synced_at->diffForHumans() : 'Yapılmadı' }}</p>
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
                            @if ($meta?->is_enabled && !$meta?->last_error)
                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300 shrink-0">Bağlı</span>
                            @elseif($meta?->last_error)
                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300 shrink-0">Hata</span>
                            @else
                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300 shrink-0">Bağlı Değil</span>
                            @endif
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 truncate">{{ $meta?->property_id ? 'Ad Account: ' . $meta->property_id : 'DOST TV - Meta Ads' }}</p>
                        <p class="mt-2 text-[11px] text-gray-400">Son senkronizasyon: {{ $meta?->last_synced_at ? $meta->last_synced_at->diffForHumans() : 'Yapılmadı' }}</p>
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
                    @php
                        $topPrograms = $ga4Metrics['top_programs'] ?? array_values(array_filter($ga4Metrics['top_pages'] ?? [], fn($p) => !empty($p['is_program'])));
                    @endphp
                    <div class="mt-3 divide-y divide-gray-100 dark:divide-gray-800">
                        @if ($hasGa4 && !empty($topPrograms))
                            @foreach (array_slice($topPrograms, 0, 5) as $idx => $prog)
                                <div class="flex items-center justify-between py-2.5 text-xs">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <span class="font-bold text-gray-400 w-4 shrink-0">{{ $idx + 1 }}</span>
                                        <span class="font-semibold text-gray-800 dark:text-gray-200 truncate">{{ $prog['name'] ?? $prog['program_name'] ?? $prog['path'] }}</span>
                                    </div>
                                    <span class="font-bold text-gray-900 dark:text-white shrink-0 ml-2">{{ number_format($prog['views']) }}</span>
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

        {{-- TAB: İÇGÖRÜLER (INSIGHTS & DECISION SUPPORT) --}}
        @if ($activeTab === 'insights')
            <div class="space-y-6">
                {{-- Dev Environment Fixture Toggle --}}
                @if (config('app.env') !== 'production')
                    <div class="flex items-center justify-between rounded-xl bg-amber-50/80 px-4 py-2.5 border border-amber-200/80 dark:bg-amber-950/40 dark:border-amber-900/40 text-xs">
                        <div class="flex items-center gap-2 text-amber-800 dark:text-amber-300 font-semibold">
                            <x-heroicon-o-wrench-screwdriver class="w-4 h-4" style="width:1rem;height:1rem;" />
                            <span>Geliştirici / Test Modu: Demo Fixture Verisi</span>
                        </div>
                        <button type="button" wire:click="toggleFixture" class="rounded-lg bg-amber-600 px-3 py-1 text-[11px] font-bold text-white shadow-xs hover:bg-amber-700 transition">
                            {{ $useFixture ? 'Fixture Kapat (Gerçek Veri)' : 'Fixture Aç (Demo Veri)' }}
                        </button>
                    </div>
                @endif

                {{-- Header Section: BUGÜN NEYE BAKMALIYIM? --}}
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900 space-y-4">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-3 dark:border-gray-800">
                        <div class="flex items-center gap-2">
                            <div class="p-1.5 rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-950 dark:text-blue-400">
                                <x-heroicon-o-sparkles class="w-5 h-5" style="width:1.25rem;height:1.25rem;" />
                            </div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white">Bugün Neye Bakmalıyım?</h3>
                        </div>
                        <span class="text-xs text-gray-400 font-medium">En öncelikli 3 içgörü</span>
                    </div>

                    @if (!empty($topPriority))
                        <div class="grid gap-4 md:grid-cols-3">
                            @foreach ($topPriority as $item)
                                <div class="rounded-xl border p-4 flex flex-col justify-between space-y-3 transition shadow-xs {{ $item['severity'] === 'critical' ? 'border-rose-200 bg-rose-50/50 dark:border-rose-900/40 dark:bg-rose-950/20' : ($item['severity'] === 'warning' ? 'border-amber-200 bg-amber-50/50 dark:border-amber-900/40 dark:bg-amber-950/20' : ($item['severity'] === 'positive' ? 'border-emerald-200 bg-emerald-50/50 dark:border-emerald-900/40 dark:bg-emerald-950/20' : 'border-blue-200 bg-blue-50/50 dark:border-blue-900/40 dark:bg-blue-950/20')) }}">
                                    <div>
                                        <div class="flex items-center justify-between gap-2 mb-2">
                                            <div class="flex items-center gap-1.5">
                                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full {{ $item['severity'] === 'warning' ? 'bg-amber-200 text-amber-900' : ($item['severity'] === 'positive' ? 'bg-emerald-200 text-emerald-900' : 'bg-blue-200 text-blue-900') }}">
                                                    {{ $item['source'] }}
                                                </span>
                                                @if (in_array($item['fingerprint'] ?? $item['id'] ?? '', $data['openAlertFingerprints'] ?? [], true))
                                                    <span class="px-2 py-0.5 text-[9px] font-bold rounded-md bg-rose-600 text-white shadow-xs">
                                                        Açık Aksiyon
                                                    </span>
                                                @endif
                                            </div>
                                            @if (!empty($item['change']))
                                                <span class="text-[10px] font-bold text-gray-500 dark:text-gray-400">{{ $item['change'] }}</span>
                                            @endif
                                        </div>
                                        <h4 class="text-xs font-bold text-gray-900 dark:text-white line-clamp-1">{{ $item['title'] }}</h4>
                                        <p class="mt-1 text-[11px] text-gray-600 dark:text-gray-300 leading-relaxed line-clamp-3">{{ $item['description'] }}</p>
                                    </div>
                                    @if (!empty($item['action_label']) && !empty($item['action_target']))
                                        <div class="pt-2 border-t border-black/5 dark:border-white/5">
                                            <button type="button" wire:click="navigateToInsightAction({{ json_encode($item['action_target']) }})" class="text-xs font-bold text-blue-600 hover:underline inline-flex items-center gap-1">
                                                {{ $item['action_label'] }} &rarr;
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="py-8 text-center text-xs text-gray-400">
                            Henüz öncelikli bir uyarı tespit edilmedi. Sistem verileri izlemeye devam ediyor.
                        </div>
                    @endif
                </div>

                {{-- Two Columns: Fırsatlar vs Dikkat Edilmesi Gerekenler --}}
                <div class="grid gap-6 lg:grid-cols-2">
                    {{-- Left Column: Fırsatlar --}}
                    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900 space-y-4">
                        <div class="flex items-center justify-between border-b border-gray-100 pb-3 dark:border-gray-800">
                            <div class="flex items-center gap-2">
                                <div class="p-1.5 rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400">
                                    <x-heroicon-o-arrow-trending-up class="w-5 h-5" style="width:1.25rem;height:1.25rem;" />
                                </div>
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white">Fırsatlar (Olumlu & Öneriler)</h3>
                            </div>
                            <span class="text-xs font-bold text-emerald-600">{{ count($opportunities) }} Adet</span>
                        </div>

                        <div class="space-y-3 divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse ($opportunities as $opp)
                                <div class="pt-3 first:pt-0 flex items-start justify-between gap-3 text-xs">
                                    <div class="space-y-1 min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span class="px-2 py-0.5 text-[9px] font-bold rounded-md bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">{{ $opp['source'] }}</span>
                                            <span class="font-bold text-gray-900 dark:text-white truncate">{{ $opp['title'] }}</span>
                                        </div>
                                        <p class="text-[11px] text-gray-500 dark:text-gray-400 leading-normal">{{ $opp['description'] }}</p>
                                    </div>
                                    @if (!empty($opp['action_label']) && !empty($opp['action_target']))
                                        <button type="button" wire:click="navigateToInsightAction({{ json_encode($opp['action_target']) }})" class="shrink-0 text-[11px] font-semibold text-blue-600 hover:underline">
                                            {{ $opp['action_label'] }} &rarr;
                                        </button>
                                    @endif
                                </div>
                            @empty
                                <div class="py-6 text-center text-xs text-gray-400">Henüz fırsat olarak değerlendirilen veri bulunmuyor.</div>
                            @endforelse
                        </div>
                    </div>

                    {{-- Right Column: Dikkat Edilmesi Gerekenler --}}
                    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900 space-y-4">
                        <div class="flex items-center justify-between border-b border-gray-100 pb-3 dark:border-gray-800">
                            <div class="flex items-center gap-2">
                                <div class="p-1.5 rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-950 dark:text-amber-400">
                                    <x-heroicon-o-exclamation-triangle class="w-5 h-5" style="width:1.25rem;height:1.25rem;" />
                                </div>
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white">Dikkat Edilmesi Gerekenler</h3>
                            </div>
                            <span class="text-xs font-bold text-amber-600">{{ count($attentionNeeded) }} Adet</span>
                        </div>

                        <div class="space-y-3 divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse ($attentionNeeded as $att)
                                <div class="pt-3 first:pt-0 flex items-start justify-between gap-3 text-xs">
                                    <div class="space-y-1 min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span class="px-2 py-0.5 text-[9px] font-bold rounded-md bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300">{{ $att['source'] }}</span>
                                            <span class="font-bold text-gray-900 dark:text-white truncate">{{ $att['title'] }}</span>
                                        </div>
                                        <p class="text-[11px] text-gray-500 dark:text-gray-400 leading-normal">{{ $att['description'] }}</p>
                                    </div>
                                    @if (!empty($att['action_label']) && !empty($att['action_target']))
                                        <button type="button" wire:click="navigateToInsightAction({{ json_encode($att['action_target']) }})" class="shrink-0 text-[11px] font-semibold text-blue-600 hover:underline">
                                            {{ $att['action_label'] }} &rarr;
                                        </button>
                                    @endif
                                </div>
                            @empty
                                <div class="py-6 text-center text-xs text-gray-400">Herhangi bir kritik uyarı bulunmuyor. Sistem stabil.</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- All Insights Category Accordion --}}
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900 space-y-4">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white border-b border-gray-100 pb-3 dark:border-gray-800">Kategoriye Göre Tüm İçgörüler</h3>

                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                        @foreach ([
                            'content' => ['label' => 'İçerik Performansı', 'icon' => 'document-text', 'items' => $byCategory['content'] ?? []],
                            'search' => ['label' => 'Arama Davranışı', 'icon' => 'magnifying-glass', 'items' => $byCategory['search'] ?? []],
                            'traffic' => ['label' => 'Trafik & Ziyaretçi', 'icon' => 'user-group', 'items' => $byCategory['traffic'] ?? []],
                            'ads' => ['label' => 'Reklam Analizi', 'icon' => 'megaphone', 'items' => $byCategory['ads'] ?? []],
                            'technical' => ['label' => 'Teknik & Sağlık', 'icon' => 'cpu-chip', 'items' => $byCategory['technical'] ?? []],
                        ] as $catKey => $catMeta)
                            <div class="rounded-xl border border-gray-100 bg-gray-50/50 p-4 dark:border-gray-800 dark:bg-gray-800/40">
                                <div class="flex items-center justify-between mb-3">
                                    <span class="text-xs font-bold text-gray-900 dark:text-white">{{ $catMeta['label'] }}</span>
                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-200">{{ count($catMeta['items']) }}</span>
                                </div>

                                <div class="space-y-2 text-xs">
                                    @forelse ($catMeta['items'] as $cItem)
                                        <div class="p-2 rounded-lg bg-white shadow-2xs border border-gray-200/60 dark:bg-gray-900 dark:border-gray-700">
                                            <div class="font-bold text-gray-800 dark:text-gray-200 text-[11px] truncate">{{ $cItem['title'] }}</div>
                                            <div class="text-[10px] text-gray-500 dark:text-gray-400 mt-1 line-clamp-2">{{ $cItem['description'] }}</div>
                                        </div>
                                    @empty
                                        <div class="py-3 text-center text-[11px] text-gray-400">Veri yok.</div>
                                    @endforelse
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        {{-- TAB: AKSİYONLAR (ALERT & ACTION CENTER) --}}
        @if ($activeTab === 'actions')
            <div class="space-y-6">
                {{-- Top KPI Cards --}}
                <div class="grid gap-4 sm:grid-cols-2 md:grid-cols-4">
                    {{-- Açık Uyarılar --}}
                    <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <div class="flex items-center justify-between text-gray-500 dark:text-gray-400">
                            <span class="text-xs font-semibold">Açık Aksiyonlar</span>
                            <div class="p-1.5 rounded-lg bg-rose-50 text-rose-600 dark:bg-rose-950 dark:text-rose-400">
                                <x-heroicon-o-exclamation-triangle class="w-4 h-4" style="width:1rem;height:1rem;" />
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                                {{ $data['alertKpis']['total_open'] ?? 0 }}
                            </div>
                            <div class="mt-1 text-[11px] text-gray-400">Aktif takipte</div>
                        </div>
                    </div>

                    {{-- Kritik --}}
                    <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <div class="flex items-center justify-between text-gray-500 dark:text-gray-400">
                            <span class="text-xs font-semibold">Kritik Uyarılar</span>
                            <div class="p-1.5 rounded-lg bg-red-50 text-red-600 dark:bg-red-950 dark:text-red-400">
                                <x-heroicon-o-fire class="w-4 h-4" style="width:1rem;height:1rem;" />
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="text-2xl font-bold tracking-tight text-red-600 dark:text-red-400">
                                {{ $data['alertKpis']['critical_open'] ?? 0 }}
                            </div>
                            <div class="mt-1 text-[11px] text-gray-400">Yüksek öncelikli</div>
                        </div>
                    </div>

                    {{-- Çözülenler --}}
                    <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <div class="flex items-center justify-between text-gray-500 dark:text-gray-400">
                            <span class="text-xs font-semibold">Çözülenler</span>
                            <div class="p-1.5 rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400">
                                <x-heroicon-o-check-circle class="w-4 h-4" style="width:1rem;height:1rem;" />
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="text-2xl font-bold tracking-tight text-emerald-600 dark:text-emerald-400">
                                {{ $data['alertKpis']['resolved'] ?? 0 }}
                            </div>
                            <div class="mt-1 text-[11px] text-gray-400">Otomatik / Manuel çözülen</div>
                        </div>
                    </div>

                    {{-- Son 24 Saatte Yeni --}}
                    <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <div class="flex items-center justify-between text-gray-500 dark:text-gray-400">
                            <span class="text-xs font-semibold">Son 24 Saatte Yeni</span>
                            <div class="p-1.5 rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-950 dark:text-blue-400">
                                <x-heroicon-o-clock class="w-4 h-4" style="width:1rem;height:1rem;" />
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="text-2xl font-bold tracking-tight text-blue-600 dark:text-blue-400">
                                {{ $data['alertKpis']['new_24h'] ?? 0 }}
                            </div>
                            <div class="mt-1 text-[11px] text-gray-400">Yeni tespitler</div>
                        </div>
                    </div>
                </div>

                {{-- Filter Bar --}}
                <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900 flex flex-wrap items-center justify-between gap-4">
                    <div class="flex flex-wrap items-center gap-3">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-gray-700 dark:text-gray-300">Durum:</span>
                            <select wire:model.live="alertStatusFilter" class="rounded-lg border-gray-200 text-xs font-medium dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                                <option value="open">Açık ({{ $data['alertKpis']['total_open'] ?? 0 }})</option>
                                <option value="resolved">Çözüldü</option>
                                <option value="dismissed">Kapatıldı (Muted)</option>
                                <option value="all">Tümü</option>
                            </select>
                        </div>

                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-gray-700 dark:text-gray-300">Önem:</span>
                            <select wire:model.live="alertSeverityFilter" class="rounded-lg border-gray-200 text-xs font-medium dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                                <option value="all">Tümü</option>
                                <option value="critical">Kritik</option>
                                <option value="warning">Uyarı</option>
                            </select>
                        </div>

                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-gray-700 dark:text-gray-300">Kategori:</span>
                            <select wire:model.live="alertCategoryFilter" class="rounded-lg border-gray-200 text-xs font-medium dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                                <option value="all">Tümü</option>
                                <option value="search">Aramalar</option>
                                <option value="content">İçerik</option>
                                <option value="traffic">Trafik</option>
                                <option value="ads">Reklamlar</option>
                                <option value="technical">Teknik</option>
                            </select>
                        </div>
                    </div>

                    <div class="text-xs font-medium text-gray-400">
                        Gösterilen: {{ count($data['alertsList'] ?? []) }} Kayıt
                    </div>
                </div>

                {{-- Alert Cards List --}}
                <div class="space-y-4">
                    @forelse ($data['alertsList'] ?? [] as $alert)
                        <div class="rounded-2xl border p-5 bg-white shadow-sm dark:bg-gray-900 transition space-y-4 {{ $alert->severity === 'critical' ? 'border-red-200/80 dark:border-red-900/40' : 'border-gray-200 dark:border-gray-800' }}">
                            {{-- Header row --}}
                            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 dark:border-gray-800 pb-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    {{-- Severity Badge --}}
                                    @if ($alert->severity === 'critical')
                                        <span class="px-2.5 py-0.5 text-xs font-bold rounded-full bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300">
                                            Kritik
                                        </span>
                                    @else
                                        <span class="px-2.5 py-0.5 text-xs font-bold rounded-full bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300">
                                            Uyarı
                                        </span>
                                    @endif

                                    {{-- Status Badge --}}
                                    @if ($alert->status === 'open')
                                        <span class="px-2.5 py-0.5 text-xs font-bold rounded-full bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-950 dark:text-rose-300 dark:border-rose-900">
                                            Açık
                                        </span>
                                    @elseif ($alert->status === 'resolved')
                                        <span class="px-2.5 py-0.5 text-xs font-bold rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950 dark:text-emerald-300 dark:border-emerald-900">
                                            Çözüldü
                                        </span>
                                    @else
                                        <span class="px-2.5 py-0.5 text-xs font-bold rounded-full bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                            Kapatıldı (Yok Sayıldı)
                                        </span>
                                    @endif

                                    {{-- Source & Category Badge --}}
                                    <span class="px-2 py-0.5 text-[11px] font-medium text-gray-500 bg-gray-100 dark:bg-gray-800 dark:text-gray-400 rounded-md">
                                        {{ $alert->source }}
                                    </span>
                                </div>

                                <div class="text-[11px] text-gray-400 font-medium">
                                    Son tespit: {{ $alert->last_detected_at->diffForHumans() }}
                                </div>
                            </div>

                            {{-- Content Body --}}
                            <div class="space-y-1">
                                <h4 class="text-sm font-bold text-gray-900 dark:text-white">
                                    {{ $alert->title }}
                                </h4>
                                <p class="text-xs text-gray-600 dark:text-gray-300 leading-relaxed">
                                    {{ $alert->description }}
                                </p>
                            </div>

                            {{-- Metrics and Actions Footer --}}
                            <div class="flex flex-wrap items-center justify-between gap-4 pt-2">
                                <div class="flex items-center gap-4 text-xs">
                                    @if (!empty($alert->metric))
                                        <div><span class="text-gray-400">Metrik:</span> <span class="font-bold text-gray-900 dark:text-white">{{ $alert->metric }}</span></div>
                                    @endif
                                    @if (!empty($alert->change))
                                        <div><span class="text-gray-400">Değişim:</span> <span class="font-bold text-amber-600 dark:text-amber-400">{{ $alert->change }}</span></div>
                                    @endif
                                    <div class="text-[11px] text-gray-400">İlk Tespit: {{ $alert->first_detected_at->format('d.m.Y H:i') }}</div>
                                </div>

                                <div class="flex items-center gap-2">
                                    @if (!empty($alert->action_target))
                                        <button type="button" wire:click="navigateToInsightAction({{ json_encode($alert->action_target) }})" class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-bold text-white shadow-xs hover:bg-blue-700 transition">
                                            İncele &rarr;
                                        </button>
                                    @endif

                                    @if ($alert->status === 'open')
                                        <button type="button" wire:click="resolveAlert({{ $alert->id }})" class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700 hover:bg-emerald-100 transition dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-300">
                                            Çözüldü
                                        </button>
                                        <button type="button" wire:click="dismissAlert({{ $alert->id }})" class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs font-semibold text-gray-600 hover:bg-gray-100 transition dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                            Yok Say / Kapat
                                        </button>
                                    @else
                                        <button type="button" wire:click="reopenAlert({{ $alert->id }})" class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-bold text-amber-700 hover:bg-amber-100 transition dark:border-amber-900 dark:bg-amber-950 dark:text-amber-300">
                                            Tekrar Aç
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-gray-200 bg-white p-12 text-center dark:border-gray-800 dark:bg-gray-900 space-y-3">
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-emerald-50 dark:bg-emerald-950">
                                <x-heroicon-o-check-circle class="h-6 w-6 text-emerald-600 dark:text-emerald-400" />
                            </div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white">Takip edilen aksiyon veya uyarı bulunmuyor</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 max-w-md mx-auto">
                                Seçilen filtre kriterlerine uygun açık aksiyon bulunmamaktadır. Sistem sorunsuz çalışmaktadır.
                            </p>
                        </div>
                    @endforelse
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
            <div class="space-y-6">
                {{-- Source Selector Bar --}}
                <div class="flex items-center gap-3 border-b border-gray-200 pb-3 dark:border-gray-800">
                    <button type="button" wire:click="$set('adsSource', 'google_ads')"
                            class="px-3.5 py-1.5 text-xs font-bold rounded-xl transition flex items-center gap-2 {{ $adsSource === 'google_ads' ? 'bg-blue-600 text-white shadow-xs' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300' }}">
                        <x-heroicon-o-megaphone class="w-4 h-4" style="width:1rem;height:1rem;" />
                        Google Ads
                    </button>
                    <button type="button" wire:click="$set('adsSource', 'meta_ads')"
                            class="px-3.5 py-1.5 text-xs font-bold rounded-xl transition flex items-center gap-2 {{ $adsSource === 'meta_ads' ? 'bg-purple-600 text-white shadow-xs' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300' }}">
                        <x-heroicon-o-share class="w-4 h-4" style="width:1rem;height:1rem;" />
                        Meta Ads (Facebook & Instagram)
                    </button>
                </div>

                {{-- GOOGLE ADS VIEW --}}
                @if ($adsSource === 'google_ads')
                    @if ($hasGads)
                        @php
                            $currencySymbol = match($gadsMetrics['currency'] ?? 'TRY') {
                                'TRY' => '₺',
                                'USD' => '$',
                                'EUR' => '€',
                                default => ($gadsMetrics['currency'] ?? '₺') . ' ',
                            };
                        @endphp
                        <div class="space-y-6">
                            {{-- KPI Cards Row (6 Columns) --}}
                            <div class="grid gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6">
                                {{-- 1. Harcama --}}
                                <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                                    <div class="flex items-center gap-2 text-gray-500 dark:text-gray-400">
                                        <div class="p-1.5 rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-950 dark:text-blue-400">
                                            <x-heroicon-o-banknotes class="w-4 h-4 shrink-0" style="width: 1rem; height: 1rem;" />
                                        </div>
                                        <span class="text-xs font-semibold">Toplam Harcama</span>
                                    </div>
                                    <div class="mt-3">
                                        <div class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">{{ $currencySymbol }}{{ number_format($gadsMetrics['cost'] ?? 0, 2) }}</div>
                                        <div class="mt-1 text-[11px] font-medium text-blue-600">Google Ads Verisi</div>
                                    </div>
                                </div>

                                {{-- 2. Gösterim --}}
                                <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                                    <div class="flex items-center gap-2 text-gray-500 dark:text-gray-400">
                                        <div class="p-1.5 rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-950 dark:text-indigo-400">
                                            <x-heroicon-o-eye class="w-4 h-4 shrink-0" style="width: 1rem; height: 1rem;" />
                                        </div>
                                        <span class="text-xs font-semibold">Gösterim</span>
                                    </div>
                                    <div class="mt-3">
                                        <div class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">{{ number_format($gadsMetrics['impressions'] ?? 0) }}</div>
                                        <div class="mt-1 text-[11px] text-gray-400">Son {{ $data['days'] }} güne göre</div>
                                    </div>
                                </div>

                                {{-- 3. Tıklama --}}
                                <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                                    <div class="flex items-center gap-2 text-gray-500 dark:text-gray-400">
                                        <div class="p-1.5 rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400">
                                            <x-heroicon-o-cursor-arrow-rays class="w-4 h-4 shrink-0" style="width: 1rem; height: 1rem;" />
                                        </div>
                                        <span class="text-xs font-semibold">Tıklama</span>
                                    </div>
                                    <div class="mt-3">
                                        <div class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">{{ number_format($gadsMetrics['clicks'] ?? 0) }}</div>
                                        <div class="mt-1 text-[11px] text-gray-400">Son {{ $data['days'] }} güne göre</div>
                                    </div>
                                </div>

                                {{-- 4. CTR --}}
                                <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                                    <div class="flex items-center gap-2 text-gray-500 dark:text-gray-400">
                                        <div class="p-1.5 rounded-lg bg-purple-50 text-purple-600 dark:bg-purple-950 dark:text-purple-400">
                                            <x-heroicon-o-arrow-trending-up class="w-4 h-4 shrink-0" style="width: 1rem; height: 1rem;" />
                                        </div>
                                        <span class="text-xs font-semibold">CTR (Tıklama Oranı)</span>
                                    </div>
                                    <div class="mt-3">
                                        <div class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">%{{ number_format($gadsMetrics['ctr'] ?? 0, 2) }}</div>
                                        <div class="mt-1 text-[11px] text-gray-400">Ortalama</div>
                                    </div>
                                </div>

                                {{-- 5. Ortalama CPC --}}
                                <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                                    <div class="flex items-center gap-2 text-gray-500 dark:text-gray-400">
                                        <div class="p-1.5 rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-950 dark:text-amber-400">
                                            <x-heroicon-o-calculator class="w-4 h-4 shrink-0" style="width: 1rem; height: 1rem;" />
                                        </div>
                                        <span class="text-xs font-semibold">Ortalama CPC</span>
                                    </div>
                                    <div class="mt-3">
                                        <div class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">{{ $currencySymbol }}{{ number_format($gadsMetrics['average_cpc'] ?? 0, 2) }}</div>
                                        <div class="mt-1 text-[11px] text-gray-400">Tıklama Başına Maliyet</div>
                                    </div>
                                </div>

                                {{-- 6. Dönüşüm --}}
                                <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                                    <div class="flex items-center gap-2 text-gray-500 dark:text-gray-400">
                                        <div class="p-1.5 rounded-lg bg-rose-50 text-rose-600 dark:bg-rose-950 dark:text-rose-400">
                                            <x-heroicon-o-check-badge class="w-4 h-4 shrink-0" style="width: 1rem; height: 1rem;" />
                                        </div>
                                        <span class="text-xs font-semibold">Dönüşüm</span>
                                    </div>
                                    <div class="mt-3">
                                        <div class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">{{ number_format($gadsMetrics['conversions'] ?? 0) }}</div>
                                        <div class="mt-1 text-[11px] text-gray-400">Toplam Dönüşüm</div>
                                    </div>
                                </div>
                            </div>

                            {{-- Reklam Performansı Grafiği --}}
                            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                                <div class="flex items-center justify-between border-b border-gray-100 pb-3 dark:border-gray-800">
                                    <h3 class="text-sm font-bold text-gray-900 dark:text-white">Google Ads Performansı (Günlük Harcama & Tıklama)</h3>
                                    <div class="flex items-center gap-4 text-xs">
                                        <span class="flex items-center gap-1.5 text-blue-600 font-semibold"><span class="w-2.5 h-2.5 rounded-full bg-blue-600 inline-block" style="width:0.625rem;height:0.625rem;"></span> Harcama ({{ $currencySymbol }})</span>
                                        <span class="flex items-center gap-1.5 text-emerald-600 font-semibold"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500 inline-block" style="width:0.625rem;height:0.625rem;"></span> Tıklama</span>
                                    </div>
                                </div>

                                <div class="mt-4 h-48 flex items-center justify-center rounded-xl bg-gray-50/50 p-4 border border-dashed border-gray-200 dark:bg-gray-800/30 dark:border-gray-700 overflow-hidden">
                                    @if (!empty($gadsMetrics['timeseries']))
                                        @php
                                            $gTs = $gadsMetrics['timeseries'];
                                            $gCount = count($gTs);
                                            $maxCost = max(array_column($gTs, 'cost') ?: [1]);
                                            $maxClks = max(array_column($gTs, 'clicks') ?: [1]);
                                            $costPts = [];
                                            $clkPts = [];
                                            foreach ($gTs as $idx => $pt) {
                                                $x = $gCount > 1 ? round(($idx / ($gCount - 1)) * 500) : 250;
                                                $yc = round(90 - (($pt['cost'] / max(0.01, $maxCost)) * 75));
                                                $yk = round(90 - (($pt['clicks'] / max(1, $maxClks)) * 75));
                                                $costPts[] = "{$x},{$yc}";
                                                $clkPts[] = "{$x},{$yk}";
                                            }
                                            $costPath = !empty($costPts) ? 'M' . implode(' L', $costPts) : '';
                                            $clkPath = !empty($clkPts) ? 'M' . implode(' L', $clkPts) : '';
                                        @endphp
                                        <div class="w-full h-full flex flex-col justify-between">
                                            <div class="text-xs font-semibold text-gray-500">Google Ads Zaman Serisi (Gerçek Veri)</div>
                                            <svg class="w-full h-28" viewBox="0 0 500 100" preserveAspectRatio="none" style="max-height: 7rem;">
                                                <path d="{{ $costPath }}" fill="none" stroke="#2563eb" stroke-width="3"/>
                                                <path d="{{ $clkPath }}" fill="none" stroke="#10b981" stroke-width="3"/>
                                            </svg>
                                            <div class="flex justify-between text-[10px] text-gray-400">
                                                <span>{{ $gTs[0]['date'] ?? "Son {$data['days']} Gün Başı" }}</span>
                                                <span>{{ $gTs[$gCount - 1]['date'] ?? 'Dün' }}</span>
                                            </div>
                                        </div>
                                    @else
                                        <div class="text-center text-xs text-gray-400">
                                            Günlük performans verisi bulunmuyor.
                                        </div>
                                    @endif
                                </div>
                            </div>

                            {{-- Kampanya Performansı Tablosu --}}
                            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white border-b border-gray-100 pb-3 dark:border-gray-800">Kampanya Performansı</h3>
                                <div class="mt-4 overflow-x-auto">
                                    <table class="w-full text-left text-xs">
                                        <thead>
                                            <tr class="border-b border-gray-200 text-gray-500 dark:border-gray-800 dark:text-gray-400">
                                                <th class="pb-2 font-semibold">Kampanya Adı</th>
                                                <th class="pb-2 font-semibold">Durum</th>
                                                <th class="pb-2 font-semibold">Gösterim</th>
                                                <th class="pb-2 font-semibold">Tıklama</th>
                                                <th class="pb-2 font-semibold">Harcama</th>
                                                <th class="pb-2 font-semibold">CTR</th>
                                                <th class="pb-2 font-semibold">Ort. CPC</th>
                                                <th class="pb-2 font-semibold">Dönüşüm</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                            @forelse ($gadsMetrics['campaigns'] ?? [] as $camp)
                                                <tr>
                                                    <td class="py-3 font-semibold text-gray-900 dark:text-white">{{ $camp['name'] }}</td>
                                                    <td class="py-3">
                                                        @if (($camp['status'] ?? '') === 'ENABLED')
                                                            <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">Aktif</span>
                                                        @elseif(($camp['status'] ?? '') === 'PAUSED')
                                                            <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300">Duraklatıldı</span>
                                                        @else
                                                            <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300">{{ $camp['status_label'] ?? 'Bilinmiyor' }}</span>
                                                        @endif
                                                    </td>
                                                    <td class="py-3 font-bold text-gray-900 dark:text-white">{{ number_format($camp['impressions']) }}</td>
                                                    <td class="py-3 font-bold text-gray-900 dark:text-white">{{ number_format($camp['clicks']) }}</td>
                                                    <td class="py-3 font-bold text-blue-600 dark:text-blue-400">{{ $currencySymbol }}{{ number_format($camp['cost'], 2) }}</td>
                                                    <td class="py-3 text-gray-600 dark:text-gray-300">%{{ number_format($camp['ctr'], 2) }}</td>
                                                    <td class="py-3 text-gray-600 dark:text-gray-300">{{ $currencySymbol }}{{ number_format($camp['average_cpc'], 2) }}</td>
                                                    <td class="py-3 font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($camp['conversions']) }}</td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="8" class="py-6 text-center text-gray-400">Kampanya kaydı bulunmuyor.</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @else
                        {{-- Empty State when Google Ads is NOT connected --}}
                        <div class="rounded-2xl border border-gray-200 bg-white p-8 text-center dark:border-gray-800 dark:bg-gray-900 space-y-4">
                            <div class="mx-auto w-14 h-14 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center dark:bg-blue-950 dark:text-blue-400" style="width:3.5rem;height:3.5rem;">
                                <x-heroicon-o-megaphone class="w-8 h-8" style="width: 2rem; height: 2rem; min-width: 2rem;" />
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-gray-900 dark:text-white">Google Ads Hesabı Henüz Bağlanmadı</h3>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 max-w-md mx-auto">
                                    Gerçek reklam harcaması, gösterim, tıklama, dönüşüm ve kampanya performans verilerini doğrudan Analiz Merkezi'nde görüntülemek için Google Ads hesabınızı bağlayın.
                                </p>
                            </div>
                            <div>
                                <button type="button" wire:click="$set('activeTab', 'integrations')" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-blue-700 transition">
                                    <x-heroicon-o-cog-6-tooth class="w-4 h-4" style="width:1rem;height:1rem;" />
                                    Google Ads Entegrasyonunu Yapılandır &rarr;
                                </button>
                            </div>
                        </div>
                    @endif
                @endif

                {{-- META ADS VIEW --}}
                @if ($adsSource === 'meta_ads')
                    @if ($hasMeta)
                        @php
                            $metaCurrencySymbol = match($metaMetrics['currency'] ?? 'TRY') {
                                'TRY' => '₺',
                                'USD' => '$',
                                'EUR' => '€',
                                default => ($metaMetrics['currency'] ?? '₺') . ' ',
                            };
                        @endphp
                        <div class="space-y-6">
                            {{-- KPI Cards Row (8 Columns) --}}
                            <div class="grid gap-4 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-8">
                                {{-- 1. Harcama --}}
                                <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                                    <div class="flex items-center gap-2 text-gray-500 dark:text-gray-400">
                                        <div class="p-1.5 rounded-lg bg-purple-50 text-purple-600 dark:bg-purple-950 dark:text-purple-400">
                                            <x-heroicon-o-banknotes class="w-4 h-4 shrink-0" style="width: 1rem; height: 1rem;" />
                                        </div>
                                        <span class="text-[11px] font-semibold truncate">Harcama</span>
                                    </div>
                                    <div class="mt-2">
                                        <div class="text-xl font-bold tracking-tight text-gray-900 dark:text-white truncate">{{ $metaCurrencySymbol }}{{ number_format($metaMetrics['spend'] ?? 0, 2) }}</div>
                                        <div class="mt-0.5 text-[10px] font-medium text-purple-600">Meta Ads Verisi</div>
                                    </div>
                                </div>

                                {{-- 2. Gösterim --}}
                                <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                                    <div class="flex items-center gap-2 text-gray-500 dark:text-gray-400">
                                        <div class="p-1.5 rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-950 dark:text-indigo-400">
                                            <x-heroicon-o-eye class="w-4 h-4 shrink-0" style="width: 1rem; height: 1rem;" />
                                        </div>
                                        <span class="text-[11px] font-semibold truncate">Gösterim</span>
                                    </div>
                                    <div class="mt-2">
                                        <div class="text-xl font-bold tracking-tight text-gray-900 dark:text-white truncate">{{ number_format($metaMetrics['impressions'] ?? 0) }}</div>
                                        <div class="mt-0.5 text-[10px] text-gray-400">Son {{ $data['days'] }} gün</div>
                                    </div>
                                </div>

                                {{-- 3. Erişim (Reach) --}}
                                <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                                    <div class="flex items-center gap-2 text-gray-500 dark:text-gray-400">
                                        <div class="p-1.5 rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-950 dark:text-blue-400">
                                            <x-heroicon-o-user-group class="w-4 h-4 shrink-0" style="width: 1rem; height: 1rem;" />
                                        </div>
                                        <span class="text-[11px] font-semibold truncate">Erişim</span>
                                    </div>
                                    <div class="mt-2">
                                        <div class="text-xl font-bold tracking-tight text-gray-900 dark:text-white truncate">{{ number_format($metaMetrics['reach'] ?? 0) }}</div>
                                        <div class="mt-0.5 text-[10px] text-gray-400">Tekil İzleyici</div>
                                    </div>
                                </div>

                                {{-- 4. Tıklama --}}
                                <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                                    <div class="flex items-center gap-2 text-gray-500 dark:text-gray-400">
                                        <div class="p-1.5 rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400">
                                            <x-heroicon-o-cursor-arrow-rays class="w-4 h-4 shrink-0" style="width: 1rem; height: 1rem;" />
                                        </div>
                                        <span class="text-[11px] font-semibold truncate">Tıklama</span>
                                    </div>
                                    <div class="mt-2">
                                        <div class="text-xl font-bold tracking-tight text-gray-900 dark:text-white truncate">{{ number_format($metaMetrics['clicks'] ?? 0) }}</div>
                                        <div class="mt-0.5 text-[10px] text-gray-400">Toplam Tıklama</div>
                                    </div>
                                </div>

                                {{-- 5. CTR --}}
                                <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                                    <div class="flex items-center gap-2 text-gray-500 dark:text-gray-400">
                                        <div class="p-1.5 rounded-lg bg-violet-50 text-violet-600 dark:bg-violet-950 dark:text-violet-400">
                                            <x-heroicon-o-arrow-trending-up class="w-4 h-4 shrink-0" style="width: 1rem; height: 1rem;" />
                                        </div>
                                        <span class="text-[11px] font-semibold truncate">CTR</span>
                                    </div>
                                    <div class="mt-2">
                                        <div class="text-xl font-bold tracking-tight text-gray-900 dark:text-white truncate">%{{ number_format($metaMetrics['ctr'] ?? 0, 2) }}</div>
                                        <div class="mt-0.5 text-[10px] text-gray-400">Tıklama Oranı</div>
                                    </div>
                                </div>

                                {{-- 6. Ortalama CPC --}}
                                <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                                    <div class="flex items-center gap-2 text-gray-500 dark:text-gray-400">
                                        <div class="p-1.5 rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-950 dark:text-amber-400">
                                            <x-heroicon-o-calculator class="w-4 h-4 shrink-0" style="width: 1rem; height: 1rem;" />
                                        </div>
                                        <span class="text-[11px] font-semibold truncate">Ort. CPC</span>
                                    </div>
                                    <div class="mt-2">
                                        <div class="text-xl font-bold tracking-tight text-gray-900 dark:text-white truncate">{{ $metaCurrencySymbol }}{{ number_format($metaMetrics['cpc'] ?? 0, 2) }}</div>
                                        <div class="mt-0.5 text-[10px] text-gray-400">Tıklama Başına</div>
                                    </div>
                                </div>

                                {{-- 7. CPM --}}
                                <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                                    <div class="flex items-center gap-2 text-gray-500 dark:text-gray-400">
                                        <div class="p-1.5 rounded-lg bg-cyan-50 text-cyan-600 dark:bg-cyan-950 dark:text-cyan-400">
                                            <x-heroicon-o-chart-bar class="w-4 h-4 shrink-0" style="width: 1rem; height: 1rem;" />
                                        </div>
                                        <span class="text-[11px] font-semibold truncate">CPM</span>
                                    </div>
                                    <div class="mt-2">
                                        <div class="text-xl font-bold tracking-tight text-gray-900 dark:text-white truncate">{{ $metaCurrencySymbol }}{{ number_format($metaMetrics['cpm'] ?? 0, 2) }}</div>
                                        <div class="mt-0.5 text-[10px] text-gray-400">1.000 Gösterim</div>
                                    </div>
                                </div>

                                {{-- 8. Frekans / Dönüşüm --}}
                                <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                                    <div class="flex items-center gap-2 text-gray-500 dark:text-gray-400">
                                        <div class="p-1.5 rounded-lg bg-rose-50 text-rose-600 dark:bg-rose-950 dark:text-rose-400">
                                            <x-heroicon-o-check-badge class="w-4 h-4 shrink-0" style="width: 1rem; height: 1rem;" />
                                        </div>
                                        <span class="text-[11px] font-semibold truncate">Frekans</span>
                                    </div>
                                    <div class="mt-2">
                                        <div class="text-xl font-bold tracking-tight text-gray-900 dark:text-white truncate">{{ number_format($metaMetrics['frequency'] ?? 0, 2) }}</div>
                                        <div class="mt-0.5 text-[10px] text-gray-400">Kullanıcı Başı Gösterim</div>
                                    </div>
                                </div>
                            </div>

                            {{-- Meta Reklam Performansı Grafiği --}}
                            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                                <div class="flex items-center justify-between border-b border-gray-100 pb-3 dark:border-gray-800">
                                    <h3 class="text-sm font-bold text-gray-900 dark:text-white">Meta Reklam Performansı (Günlük Harcama & Tıklama)</h3>
                                    <div class="flex items-center gap-4 text-xs">
                                        <span class="flex items-center gap-1.5 text-purple-600 font-semibold"><span class="w-2.5 h-2.5 rounded-full bg-purple-600 inline-block" style="width:0.625rem;height:0.625rem;"></span> Harcama ({{ $metaCurrencySymbol }})</span>
                                        <span class="flex items-center gap-1.5 text-emerald-600 font-semibold"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500 inline-block" style="width:0.625rem;height:0.625rem;"></span> Tıklama</span>
                                    </div>
                                </div>

                                <div class="mt-4 h-48 flex items-center justify-center rounded-xl bg-gray-50/50 p-4 border border-dashed border-gray-200 dark:bg-gray-800/30 dark:border-gray-700 overflow-hidden">
                                    @if (!empty($metaMetrics['timeseries']))
                                        @php
                                            $mTs = $metaMetrics['timeseries'];
                                            $mCount = count($mTs);
                                            $mMaxSpend = max(array_column($mTs, 'spend') ?: [1]);
                                            $mMaxClks = max(array_column($mTs, 'clicks') ?: [1]);
                                            $mSpendPts = [];
                                            $mClkPts = [];
                                            foreach ($mTs as $idx => $pt) {
                                                $x = $mCount > 1 ? round(($idx / ($mCount - 1)) * 500) : 250;
                                                $ys = round(90 - (($pt['spend'] / max(0.01, $mMaxSpend)) * 75));
                                                $yk = round(90 - (($pt['clicks'] / max(1, $mMaxClks)) * 75));
                                                $mSpendPts[] = "{$x},{$ys}";
                                                $mClkPts[] = "{$x},{$yk}";
                                            }
                                            $mSpendPath = !empty($mSpendPts) ? 'M' . implode(' L', $mSpendPts) : '';
                                            $mClkPath = !empty($mClkPts) ? 'M' . implode(' L', $mClkPts) : '';
                                        @endphp
                                        <div class="w-full h-full flex flex-col justify-between">
                                            <div class="text-xs font-semibold text-gray-500">Meta Ads Graph API Zaman Serisi (Gerçek Veri)</div>
                                            <svg class="w-full h-28" viewBox="0 0 500 100" preserveAspectRatio="none" style="max-height: 7rem;">
                                                <path d="{{ $mSpendPath }}" fill="none" stroke="#9333ea" stroke-width="3"/>
                                                <path d="{{ $mClkPath }}" fill="none" stroke="#10b981" stroke-width="3"/>
                                            </svg>
                                            <div class="flex justify-between text-[10px] text-gray-400">
                                                <span>{{ $mTs[0]['date'] ?? "Son {$data['days']} Gün Başı" }}</span>
                                                <span>{{ $mTs[$mCount - 1]['date'] ?? 'Dün' }}</span>
                                            </div>
                                        </div>
                                    @else
                                        <div class="text-center text-xs text-gray-400">
                                            Günlük Meta performans verisi bulunmuyor.
                                        </div>
                                    @endif
                                </div>
                            </div>

                            {{-- Kampanya Performansı Tablosu --}}
                            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white border-b border-gray-100 pb-3 dark:border-gray-800">Meta Kampanya Performansı</h3>
                                <div class="mt-4 overflow-x-auto">
                                    <table class="w-full text-left text-xs">
                                        <thead>
                                            <tr class="border-b border-gray-200 text-gray-500 dark:border-gray-800 dark:text-gray-400">
                                                <th class="pb-2 font-semibold">Kampanya Adı</th>
                                                <th class="pb-2 font-semibold">Durum</th>
                                                <th class="pb-2 font-semibold">Gösterim</th>
                                                <th class="pb-2 font-semibold">Erişim</th>
                                                <th class="pb-2 font-semibold">Tıklama</th>
                                                <th class="pb-2 font-semibold">Harcama</th>
                                                <th class="pb-2 font-semibold">CTR</th>
                                                <th class="pb-2 font-semibold">CPM</th>
                                                <th class="pb-2 font-semibold">Frekans</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                            @forelse ($metaMetrics['campaigns'] ?? [] as $camp)
                                                <tr>
                                                    <td class="py-3 font-semibold text-gray-900 dark:text-white">{{ $camp['name'] }}</td>
                                                    <td class="py-3">
                                                        @if (($camp['status'] ?? '') === 'ACTIVE')
                                                            <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">Aktif</span>
                                                        @elseif(($camp['status'] ?? '') === 'PAUSED')
                                                            <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300">Duraklatıldı</span>
                                                        @else
                                                            <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300">{{ $camp['status_label'] ?? 'Bilinmiyor' }}</span>
                                                        @endif
                                                    </td>
                                                    <td class="py-3 font-bold text-gray-900 dark:text-white">{{ number_format($camp['impressions']) }}</td>
                                                    <td class="py-3 font-bold text-gray-900 dark:text-white">{{ number_format($camp['reach'] ?? 0) }}</td>
                                                    <td class="py-3 font-bold text-gray-900 dark:text-white">{{ number_format($camp['clicks']) }}</td>
                                                    <td class="py-3 font-bold text-purple-600 dark:text-purple-400">{{ $metaCurrencySymbol }}{{ number_format($camp['spend'], 2) }}</td>
                                                    <td class="py-3 text-gray-600 dark:text-gray-300">%{{ number_format($camp['ctr'], 2) }}</td>
                                                    <td class="py-3 text-gray-600 dark:text-gray-300">{{ $metaCurrencySymbol }}{{ number_format($camp['cpm'], 2) }}</td>
                                                    <td class="py-3 font-semibold text-gray-700 dark:text-gray-300">{{ number_format($camp['frequency'], 2) }}</td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="9" class="py-6 text-center text-gray-400">Meta kampanya kaydı bulunmuyor.</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @else
                        {{-- Empty State when Meta Ads is NOT connected --}}
                        <div class="rounded-2xl border border-gray-200 bg-white p-8 text-center dark:border-gray-800 dark:bg-gray-900 space-y-4">
                            <div class="mx-auto w-14 h-14 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center dark:bg-purple-950 dark:text-purple-400" style="width:3.5rem;height:3.5rem;">
                                <x-heroicon-o-share class="w-8 h-8" style="width: 2rem; height: 2rem; min-width: 2rem;" />
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-gray-900 dark:text-white">Meta Ads Hesabı Henüz Bağlanmadı</h3>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 max-w-md mx-auto">
                                    Facebook ve Instagram reklam harcaması, gösterim, erişim, tıklama ve kampanya performans verilerini doğrudan Analiz Merkezi'nde görüntülemek için Meta Ads hesabınızı bağlayın.
                                </p>
                            </div>
                            <div>
                                <button type="button" wire:click="$set('activeTab', 'integrations')" class="inline-flex items-center gap-2 rounded-xl bg-purple-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-purple-700 transition">
                                    <x-heroicon-o-cog-6-tooth class="w-4 h-4" style="width:1rem;height:1rem;" />
                                    Meta Ads Entegrasyonunu Yapılandır &rarr;
                                </button>
                            </div>
                        </div>
                    @endif
                @endif
            </div>
        @endif

        {{-- TAB: ENTEGRASYONLAR --}}
        @if ($activeTab === 'integrations')
            <div class="space-y-8">
                <form wire:submit="saveGa4Settings">
                    {{ $this->form }}

                    <div class="mt-4 flex flex-wrap items-center gap-3">
                        <button type="submit" class="rounded-xl bg-blue-600 px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-blue-700 transition">
                            GA4 Ayarlarını Kaydet
                        </button>

                        <button type="button" wire:click="testGa4Connection" wire:loading.attr="disabled" class="rounded-xl border border-blue-300 bg-blue-50 px-4 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-100 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-300 dark:hover:bg-blue-900 transition flex items-center gap-2">
                            <x-heroicon-o-check-circle class="w-4 h-4" style="width:1rem;height:1rem;" />
                            GA4 Bağlantısını Test Et
                        </button>

                        <button type="button" wire:click="syncGa4Now" wire:loading.attr="disabled" class="rounded-xl border border-gray-300 bg-white px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 transition flex items-center gap-2">
                            <x-heroicon-o-arrow-path class="w-4 h-4" style="width:1rem;height:1rem;" />
                            Şimdi GA4 Senkronize Et
                        </button>
                    </div>
                </form>

                @if ($ga4?->last_error)
                    <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-xs text-rose-700 dark:border-rose-900/50 dark:bg-rose-900/20 dark:text-rose-300">
                        <span class="font-bold">Son GA4 Entegrasyon Hatası:</span> {{ $ga4->last_error }}
                    </div>
                @endif

                {{-- Action Bar for Google Ads --}}
                <div class="border-t border-gray-200 pt-6 dark:border-gray-800 space-y-4">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white">Google Ads İşlemleri</h3>
                    <div class="flex flex-wrap items-center gap-3">
                        <button type="button" wire:click="saveGoogleAdsSettings" class="rounded-xl bg-blue-600 px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-blue-700 transition">
                            Google Ads Ayarlarını Kaydet
                        </button>

                        <button type="button" wire:click="testGoogleAdsConnection" wire:loading.attr="disabled" class="rounded-xl border border-blue-300 bg-blue-50 px-4 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-100 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-300 dark:hover:bg-blue-900 transition flex items-center gap-2">
                            <x-heroicon-o-check-circle class="w-4 h-4" style="width:1rem;height:1rem;" />
                            Google Ads Bağlantısını Test Et
                        </button>

                        <button type="button" wire:click="syncGoogleAdsNow" wire:loading.attr="disabled" class="rounded-xl border border-gray-300 bg-white px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 transition flex items-center gap-2">
                            <x-heroicon-o-arrow-path class="w-4 h-4" style="width:1rem;height:1rem;" />
                            Şimdi Google Ads Senkronize Et
                        </button>
                    </div>

                    @if ($gads?->last_error)
                        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-xs text-rose-700 dark:border-rose-900/50 dark:bg-rose-900/20 dark:text-rose-300">
                            <span class="font-bold">Son Google Ads Entegrasyon Hatası:</span> {{ $gads->last_error }}
                        </div>
                    @endif
                </div>

                {{-- Action Bar for Meta Ads --}}
                <div class="border-t border-gray-200 pt-6 dark:border-gray-800 space-y-4">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white">Meta Ads İşlemleri</h3>
                    <div class="flex flex-wrap items-center gap-3">
                        <button type="button" wire:click="saveMetaAdsSettings" class="rounded-xl bg-purple-600 px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-purple-700 transition">
                            Meta Ads Ayarlarını Kaydet
                        </button>

                        <button type="button" wire:click="testMetaAdsConnection" wire:loading.attr="disabled" class="rounded-xl border border-purple-300 bg-purple-50 px-4 py-2 text-xs font-semibold text-purple-700 hover:bg-purple-100 dark:border-purple-800 dark:bg-purple-950 dark:text-purple-300 dark:hover:bg-purple-900 transition flex items-center gap-2">
                            <x-heroicon-o-check-circle class="w-4 h-4" style="width:1rem;height:1rem;" />
                            Meta Ads Bağlantısını Test Et
                        </button>

                        <button type="button" wire:click="syncMetaAdsNow" wire:loading.attr="disabled" class="rounded-xl border border-gray-300 bg-white px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 transition flex items-center gap-2">
                            <x-heroicon-o-arrow-path class="w-4 h-4" style="width:1rem;height:1rem;" />
                            Şimdi Meta Ads Senkronize Et
                        </button>
                    </div>

                    @if ($meta?->last_error)
                        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-xs text-rose-700 dark:border-rose-900/50 dark:bg-rose-900/20 dark:text-rose-300">
                            <span class="font-bold">Son Meta Ads Entegrasyon Hatası:</span> {{ $meta->last_error }}
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
