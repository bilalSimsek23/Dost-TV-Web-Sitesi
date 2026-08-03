<x-filament-panels::page>
    @php
        $days = \App\Models\Schedule::DAYS;
        $template = $this->selectedTemplate;
        $dayCounts = $this->dayCounts;
        $templates = $this->templates;
    @endphp

    <div class="w-full space-y-4">

        {{-- AKIŞ KONTROL SATIRI: SADECE DROPDOWN & SEKMELER SOLDA, TARİH ARALIĞI & DURUM SAĞDA --}}
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 bg-gray-900 p-4 rounded-xl border border-gray-800 shadow-md text-white">
            
            {{-- SOL BÖLÜM: Akış Seçici Dropdown & Görünüm Sekmeleri --}}
            <div class="flex flex-wrap items-center gap-3">
                {{-- Dropdown --}}
                <div class="relative" x-data="{ open: false }">
                    <button 
                        @click="open = !open" 
                        type="button" 
                        class="inline-flex items-center gap-2 px-3.5 py-2 bg-gray-800 hover:bg-gray-700 text-white font-bold text-xs rounded-lg transition border border-gray-700 shadow-sm"
                    >
                        <span>🎛</span>
                        <span>{{ mb_strtoupper($template?->name ?? 'GENEL YAYIN AKIŞI 2026') }}</span>
                        <span class="text-[10px] text-gray-400">▼</span>
                    </button>

                    <div 
                        x-show="open" 
                        @click.away="open = false" 
                        x-transition
                        class="absolute left-0 mt-2 w-72 bg-gray-900 rounded-xl shadow-2xl border border-gray-800 p-1.5 z-50 space-y-1"
                        style="display: none;"
                    >
                        <div class="px-2 py-1 text-[10px] font-bold text-gray-400 uppercase tracking-wider border-b border-gray-800">
                            Mevcut Yayın Akışları
                        </div>

                        @foreach ($templates as $t)
                            @php
                                $isSelected = $this->selectedTemplateId === $t->id;
                                $isPublished = $t->status === 'published';
                            @endphp
                            <div 
                                wire:click="selectTemplate({{ $t->id }})" 
                                @click="open = false"
                                class="p-2 rounded-lg border transition cursor-pointer flex items-center justify-between gap-2 {{ $isSelected ? 'bg-amber-500/20 border-amber-500/50 text-amber-300 font-bold' : 'bg-gray-800/40 border-transparent hover:bg-gray-800 text-gray-200' }}"
                            >
                                <div class="text-xs font-bold">{{ $t->name }}</div>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $isPublished ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'bg-amber-500/20 text-amber-300 border border-amber-500/30' }}">
                                    {{ $isPublished ? 'Yayında' : 'Taslak' }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Görünüm Sekmeleri --}}
                <div class="flex items-center gap-1 border-l border-gray-800 pl-3">
                    <button 
                        wire:click="setViewMode('daily')" 
                        type="button" 
                        class="px-3 py-1.5 text-xs transition-all rounded-lg {{ $this->viewMode === 'daily' ? 'font-bold bg-amber-600 text-white shadow-sm' : 'font-medium text-gray-400 hover:bg-gray-800 hover:text-white' }}"
                    >
                        Günlük
                    </button>

                    <button 
                        wire:click="setViewMode('weekly')" 
                        type="button" 
                        class="px-3 py-1.5 text-xs transition-all rounded-lg {{ $this->viewMode === 'weekly' ? 'font-bold bg-amber-600 text-white shadow-sm' : 'font-medium text-gray-400 hover:bg-gray-800 hover:text-white' }}"
                    >
                        Haftalık
                    </button>

                    <button 
                        wire:click="setViewMode('monthly')" 
                        type="button" 
                        class="px-3 py-1.5 text-xs transition-all rounded-lg {{ $this->viewMode === 'monthly' ? 'font-bold bg-amber-600 text-white shadow-sm' : 'font-medium text-gray-400 hover:bg-gray-800 hover:text-white' }}"
                    >
                        Aylık
                    </button>
                </div>
            </div>

            {{-- SAĞ BÖLÜM: Tarih Aralığı, Durum Rozeti & Son Güncelleme --}}
            <div class="flex flex-wrap items-center gap-2.5 text-xs">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-gray-800/80 text-gray-300 font-semibold border border-gray-700/60 shadow-sm">
                    <span>🗓</span>
                    @if($template?->valid_from && $template?->valid_until)
                        {{ $template->valid_from->translatedFormat('j F Y') }} — {{ $template->valid_until->translatedFormat('j F Y') }}
                    @else
                        1 Ocak 2026 — 31 Aralık 2026
                    @endif
                </span>

                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg font-bold border {{ $template?->status === 'published' ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40' : 'bg-amber-500/20 text-amber-300 border-amber-500/40' }}">
                    <span class="w-2 h-2 rounded-full {{ $template?->status === 'published' ? 'bg-emerald-400 animate-pulse' : 'bg-amber-400' }}"></span>
                    <span>{{ $template?->status === 'published' ? 'Yayında' : 'Taslak' }}</span>
                </span>

                <span class="text-gray-400 font-medium text-xs">
                    Son güncelleme: {{ $template?->updated_at ? $template->updated_at->translatedFormat('30 Temmuz 2026 H:i') : '30 Temmuz 2026 12:07' }}
                </span>
            </div>
        </div>

        {{-- GÜN SEÇİMİ: PAZARTESİ - PAZAR KOMPAKT BUTONLAR (40px HEIGHT) --}}
        <div class="flex items-center gap-2 overflow-x-auto pb-1 w-full scrollbar-thin">
            @foreach ($days as $idx => $d)
                @php
                    $isActive = (int) $this->selectedDay === (int) $idx;
                @endphp
                <button 
                    wire:click="selectDay({{ $idx }})" 
                    type="button" 
                    class="h-10 px-4 text-xs font-bold rounded-xl transition flex items-center gap-2 whitespace-nowrap border shrink-0 {{ $isActive ? 'bg-amber-500 text-white font-black shadow-md border-amber-600 ring-2 ring-amber-400/50' : 'bg-gray-900 text-gray-300 border-gray-800 hover:bg-gray-800 hover:text-white' }}"
                >
                    <span>{{ $d }}</span>
                    <span class="text-[11px] opacity-80">🗓</span>
                </button>
            @endforeach
        </div>

        {{-- EPG YAYIN AKIŞI SATIR LİSTESİ --}}
        @php
            $templateId = $this->selectedTemplateId ?: 0;
            $items = \App\Models\ScheduleTemplateItem::query()
                ->where('schedule_template_id', $templateId)
                ->where('day_of_week', $this->selectedDay)
                ->with('program')
                ->orderBy('start_time', 'asc')
                ->get();
        @endphp

        <div class="bg-gray-900 border border-gray-800 rounded-xl shadow-lg overflow-hidden divide-y divide-gray-800/80 w-full">
            @forelse ($items as $item)
                @php
                    $prog = $item->program;
                    $coverImage = $prog?->cover_image ?: 'https://dosttv.com/wp-content/uploads/2022/02/dost_logo.png';
                    $description = $prog?->description ? Str::limit(strip_tags(html_entity_decode($prog->description, ENT_QUOTES | ENT_HTML5, 'UTF-8')), 110) : 'Kalpten çıkan, kalbe ulaşan program içeriği...';
                @endphp
                <div class="py-2.5 px-4 flex items-center justify-between gap-4 hover:bg-gray-800/50 transition">
                    
                    {{-- SAAT & RESİM & BAŞLIK & AÇIKLAMA & ROZET --}}
                    <div class="flex items-center gap-4 flex-1 min-w-0">
                        {{-- DİKEY SAAT SÜTUNU (WIDTH 90px) --}}
                        <div class="text-center font-bold text-gray-300 w-24 shrink-0 leading-tight">
                            <div class="text-xs font-black text-white tracking-wide">{{ $item->start_time ? \Carbon\Carbon::parse($item->start_time)->format('H:i') : '00:00' }}</div>
                            <div class="text-gray-500 text-[10px] py-0.5 font-normal">—</div>
                            <div class="text-xs font-semibold text-gray-400 tracking-wide">{{ $item->end_time ? \Carbon\Carbon::parse($item->end_time)->format('H:i') : '00:00' }}</div>
                        </div>

                        {{-- PROGRAM GÖRSELİ THUMBNAIL (112x64px SADECE SATIRDA) --}}
                        <div class="w-[112px] h-[64px] rounded-lg overflow-hidden shrink-0 border border-gray-800 bg-gray-950 shadow-inner">
                            <img 
                                src="{{ $coverImage }}" 
                                alt="{{ $item->display_title }}" 
                                class="w-full h-full object-cover"
                                onerror="this.src='https://dosttv.com/wp-content/uploads/2022/02/dost_logo.png'"
                            />
                        </div>

                        {{-- PROGRAM DETAYLARI --}}
                        <div class="min-w-0 flex-1 space-y-1">
                            <div class="flex items-center gap-2.5">
                                <h4 class="font-bold text-white text-sm truncate leading-snug">{{ $item->display_title }}</h4>
                                <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold tracking-wider uppercase border shrink-0 {{ $item->is_live ? 'bg-red-500/20 text-red-300 border-red-500/40' : ($item->is_repeat ? 'bg-amber-500/20 text-amber-300 border-amber-500/40' : 'bg-gray-800 text-gray-300 border-gray-700') }}">
                                    {{ $item->is_live ? 'CANLI YAYIN' : ($item->is_repeat ? 'TEKRAR YAYIN' : 'NORMAL') }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-400 line-clamp-1 max-w-4xl leading-relaxed">{{ $description }}</p>
                        </div>
                    </div>

                    {{-- SAĞ BÖLÜM: AKTİF TOGGLE & ÜÇ NOKTA MENÜSÜ --}}
                    <div class="flex items-center gap-3 shrink-0 justify-end">
                        {{-- AKTİF / PASİF TOGGLE --}}
                        <button 
                            wire:click="toggleItemActive({{ $item->id }})" 
                            type="button" 
                            class="inline-flex items-center gap-2 transition"
                        >
                            <span class="w-9 h-5 flex items-center rounded-full p-0.5 duration-300 {{ $item->is_active ? 'bg-amber-500 justify-end' : 'bg-gray-700 justify-start' }}">
                                <span class="w-4 h-4 bg-white rounded-full shadow-md transform"></span>
                            </span>
                            <span class="text-xs font-bold {{ $item->is_active ? 'text-white' : 'text-gray-500' }}">
                                {{ $item->is_active ? 'Aktif' : 'Pasif' }}
                            </span>
                        </button>

                        {{-- ÜÇ NOKTA İŞLEM MENÜSÜ DROPDOWN --}}
                        <div class="relative" x-data="{ open: false }">
                            <button 
                                @click="open = !open" 
                                type="button" 
                                class="px-2.5 py-1 text-base font-black text-gray-400 hover:text-white hover:bg-gray-800 rounded-lg transition"
                            >
                                ⋮
                            </button>

                            <div 
                                x-show="open" 
                                @click.away="open = false" 
                                x-transition
                                class="absolute right-0 mt-1 w-44 bg-gray-950 rounded-xl shadow-2xl border border-gray-800 p-1.5 z-50 space-y-1 text-xs"
                                style="display: none;"
                            >
                                <button 
                                    wire:click="mountTableAction('edit', {{ $item->id }})" 
                                    @click="open = false" 
                                    type="button" 
                                    class="w-full text-left px-3 py-1.5 rounded-lg hover:bg-gray-800 text-gray-200 font-medium flex items-center gap-2 transition"
                                >
                                    <span>✏️</span>
                                    <span>Düzenle</span>
                                </button>

                                <button 
                                    wire:click="mountTableAction('copy_item', {{ $item->id }})" 
                                    @click="open = false" 
                                    type="button" 
                                    class="w-full text-left px-3 py-1.5 rounded-lg hover:bg-gray-800 text-gray-200 font-medium flex items-center gap-2 transition"
                                >
                                    <span>📄</span>
                                    <span>Kopyala</span>
                                </button>

                                <button 
                                    wire:click="mountTableAction('move_day', {{ $item->id }})" 
                                    @click="open = false" 
                                    type="button" 
                                    class="w-full text-left px-3 py-1.5 rounded-lg hover:bg-gray-800 text-gray-200 font-medium flex items-center gap-2 transition"
                                >
                                    <span>↪️</span>
                                    <span>Başka Güne Taşı</span>
                                </button>

                                <button 
                                    wire:click="mountTableAction('delete', {{ $item->id }})" 
                                    @click="open = false" 
                                    type="button" 
                                    class="w-full text-left px-3 py-1.5 rounded-lg hover:bg-gray-800 text-red-400 font-medium flex items-center gap-2 transition"
                                >
                                    <span>🗑</span>
                                    <span>Sil</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="py-12 px-4 text-center text-gray-500 space-y-2">
                    <div class="text-2xl">🗓</div>
                    <div class="text-sm font-bold text-gray-400">Bu gün için henüz yayın planlanmamış.</div>
                    <div class="text-xs text-gray-500">Yukarıdaki "+ Yeni Yayın" butonunu kullanarak yayın ekleyebilirsiniz.</div>
                </div>
            @endforelse
        </div>

    </div>
</x-filament-panels::page>
