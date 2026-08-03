<x-filament-panels::page>
    @php
        $days = \App\Models\Schedule::DAYS;
        $templates = $this->templates;
    @endphp

    <div class="w-full space-y-5">

        {{-- 1. YAYIN AKIŞI SEÇİMİ (SADECE SELECT KUTUSU) --}}
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-5 shadow-lg space-y-3">
            <label for="template-select" class="block text-sm font-bold text-white uppercase tracking-wider">
                Yayın Akışı
            </label>
            <div class="max-w-xl">
                <select 
                    id="template-select"
                    wire:model.live="selectedTemplateId"
                    class="w-full bg-gray-800 border border-gray-700 text-white font-bold text-sm rounded-lg px-4 py-2.5 shadow-sm focus:border-amber-500 focus:ring-amber-500 cursor-pointer transition"
                >
                    @foreach ($templates as $t)
                        <option value="{{ $t->id }}">
                            {{ $t->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- 2. GÜN PANELİ (ORTALANMIŞ, EŞİT BOŞLUKLU SEGMENTED DÜĞME KARTI) --}}
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-6 shadow-lg space-y-4 w-full">
            <div class="flex items-center justify-center border-b border-gray-800/80 pb-3">
                <h3 class="text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
                    <span class="text-amber-500 text-base">📅</span>
                    <span>Yayın Günü</span>
                </h3>
            </div>

            <div class="flex flex-wrap items-center justify-center gap-3 w-full">
                @foreach ($days as $idx => $d)
                    @php
                        $isActive = (int) $this->selectedDay === (int) $idx;
                    @endphp
                    <button 
                        wire:click="selectDay({{ $idx }})" 
                        type="button" 
                        class="px-5 py-2.5 text-xs font-bold rounded-xl transition-all duration-200 flex items-center justify-center min-w-[110px] border shadow-sm cursor-pointer select-none {{ $isActive ? 'bg-amber-500 text-white font-black border-amber-600 shadow-md ring-2 ring-amber-400/50 scale-[1.02]' : 'bg-gray-800 text-gray-300 border-gray-700 hover:bg-gray-700 hover:text-white hover:border-gray-600' }}"
                    >
                        {{ $d }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- 3. KOMPAKT YAYIN TABLOSU --}}
        <div class="w-full">
            {{ $this->table }}
        </div>

    </div>
</x-filament-panels::page>
