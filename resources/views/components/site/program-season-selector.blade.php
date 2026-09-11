@props([
    'hasSeasons' => false,
    'seasonItems' => collect(),
    'selectedSeasonItem' => null,
])

@if (!empty($hasSeasons) && $seasonItems->isNotEmpty())
    <div class="space-y-4">
        {{-- Section Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 pb-4 border-b border-white/10">
            <div>
                <h2 class="text-xl md:text-2xl font-bold text-white tracking-tight">Sezonlar</h2>
                <p class="text-xs md:text-sm text-slate-400 mt-0.5">İzlemek istediğiniz sezonu seçin.</p>
            </div>
            @if ($selectedSeasonItem)
                <div class="hidden sm:inline-flex text-xs font-semibold text-rose-300 bg-rose-950/40 border border-rose-800/40 px-3 py-1.5 rounded-full self-start sm:self-auto">
                    Seçili: {{ $selectedSeasonItem->label }} · {{ $selectedSeasonItem->total_episodes }} Bölüm
                </div>
            @endif
        </div>

        {{-- MOBILE SELECTOR (< 768px) --}}
        <div class="block md:hidden" x-data="{ open: false }">
            @if ($seasonItems->count() > 1)
                <div class="relative w-full">
                    {{-- Compact Trigger Button --}}
                    <button type="button"
                            @click="open = !open"
                            @click.outside="open = false"
                            @keydown.escape.window="open = false"
                            :aria-expanded="open.toString()"
                            aria-controls="mobile-season-dropdown"
                            class="w-full flex items-center justify-between p-3.5 rounded-xl bg-slate-900/90 border border-white/15 text-left transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-rose-500/50 shadow-lg active:scale-[0.99]">
                        <div class="flex items-center gap-3 min-w-0 pr-2">
                            <div class="w-2.5 h-2.5 rounded-full bg-rose-500 shrink-0 animate-pulse"></div>
                            <div class="min-w-0">
                                <div class="font-bold text-sm text-white truncate">
                                    {{ $selectedSeasonItem?->label ?? 'Sezon Seçin' }}
                                </div>
                                <div class="text-xs text-rose-300 font-medium mt-0.5">
                                    {{ $selectedSeasonItem?->total_episodes ?? 0 }} Bölüm
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 bg-white/5 px-2 py-1 rounded-md border border-white/10">
                                Değiştir
                            </span>
                            <svg xmlns="http://www.w3.org/2000/svg"
                                 viewBox="0 0 20 20"
                                 fill="currentColor"
                                 class="w-5 h-5 text-slate-400 transition-transform duration-200"
                                 :class="{ 'rotate-180 text-rose-400': open }">
                                <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </button>

                    {{-- Dropdown Menu Panel --}}
                    <div id="mobile-season-dropdown"
                         x-show="open"
                         x-cloak
                         style="display: none;"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
                         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                         x-transition:leave-end="opacity-0 scale-95 -translate-y-2"
                         class="absolute left-0 right-0 top-full mt-2 z-40 rounded-xl bg-slate-900/98 border border-white/15 shadow-2xl p-2 max-h-72 overflow-y-auto space-y-1 backdrop-blur-md">
                        @foreach ($seasonItems as $item)
                            @php
                                $isActive = ($selectedSeasonItem && $selectedSeasonItem->key === $item->key);
                            @endphp
                            <a href="{{ $item->url }}"
                               @click="open = false"
                               class="flex items-center justify-between p-3 rounded-lg transition-all duration-150 text-left text-sm {{ $isActive ? 'bg-gradient-to-r from-rose-900/80 to-rose-950/90 border border-rose-500/60 font-bold text-white shadow-md' : 'bg-white/[0.02] hover:bg-white/[0.08] text-slate-300 hover:text-white border border-transparent' }}">
                                <div class="flex items-center gap-2.5 min-w-0 pr-2">
                                    @if ($isActive)
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 text-rose-400 shrink-0">
                                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" />
                                        </svg>
                                    @else
                                        <div class="w-1.5 h-1.5 rounded-full bg-slate-600 shrink-0"></div>
                                    @endif
                                    <span class="truncate">{{ $item->label }}</span>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <span class="text-xs px-2 py-0.5 rounded-md font-normal {{ $isActive ? 'bg-rose-500/30 text-rose-200 border border-rose-400/30' : 'bg-white/5 text-slate-400' }}">
                                        {{ $item->total_episodes }} Bölüm
                                    </span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @else
                @php
                    $singleItem = $seasonItems->first();
                @endphp
                <div class="flex items-center justify-between p-3.5 rounded-xl bg-slate-900/90 border border-white/10 text-sm">
                    <div class="flex items-center gap-2.5">
                        <div class="w-2 h-2 rounded-full bg-rose-500"></div>
                        <span class="font-bold text-white">{{ $singleItem->label }}</span>
                    </div>
                    <span class="text-xs text-rose-300 font-semibold bg-white/5 border border-white/10 px-2.5 py-1 rounded-md">
                        {{ $singleItem->total_episodes }} Bölüm
                    </span>
                </div>
            @endif
        </div>

        {{-- DESKTOP / TABLET GRID (>= 768px) --}}
        <div class="hidden md:block">
            @if ($seasonItems->count() > 1)
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3.5">
                    @foreach ($seasonItems as $item)
                        @php
                            $isActive = ($selectedSeasonItem && $selectedSeasonItem->key === $item->key);
                        @endphp
                        <a href="{{ $item->url }}"
                           class="group relative flex items-center justify-between p-4 rounded-xl transition-all duration-200 {{ $isActive ? 'bg-gradient-to-r from-rose-900/60 to-rose-950/80 border-rose-500/60 shadow-lg shadow-rose-950/50 ring-1 ring-rose-500/50' : 'bg-white/[0.03] hover:bg-white/[0.07] border-white/10 text-slate-300 hover:text-white' }} border">
                            <div class="flex items-center gap-3">
                                <div class="w-2.5 h-2.5 rounded-full {{ $isActive ? 'bg-rose-500 animate-pulse' : 'bg-slate-600 group-hover:bg-slate-400' }}"></div>
                                <div>
                                    <div class="font-bold text-base text-white group-hover:text-rose-300 transition-colors">
                                        {{ $item->label }}
                                    </div>
                                    <div class="text-xs text-slate-400 mt-0.5">
                                        {{ $item->total_episodes }} Bölüm
                                    </div>
                                </div>
                            </div>
                            <span class="text-xs font-semibold px-2.5 py-1 rounded-lg transition-colors {{ $isActive ? 'bg-rose-500/30 text-rose-200 border border-rose-400/30' : 'bg-white/5 text-slate-400 group-hover:bg-white/10 group-hover:text-slate-200' }}">
                                {{ $isActive ? 'Seçili' : 'Görüntüle' }}
                            </span>
                        </a>
                    @endforeach
                </div>
            @else
                @php
                    $singleItem = $seasonItems->first();
                @endphp
                <div class="inline-flex items-center gap-3 px-4 py-2 rounded-xl bg-white/[0.03] border border-white/10 text-sm">
                    <span class="font-semibold text-white">{{ $singleItem->label }}</span>
                    <span class="text-xs text-slate-400 bg-white/10 px-2 py-0.5 rounded-md">{{ $singleItem->total_episodes }} Bölüm</span>
                </div>
            @endif
        </div>
    </div>
@endif
