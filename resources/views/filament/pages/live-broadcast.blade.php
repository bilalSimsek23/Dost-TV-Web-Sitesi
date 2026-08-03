<x-filament-panels::page>
    <div class="w-full space-y-6">

        {{-- SEKMELER (TABS) --}}
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-3 shadow-lg flex items-center justify-center gap-3">
            <button 
                type="button" 
                wire:click="$set('activeTab', 'tv')" 
                class="px-6 py-3 text-sm font-bold rounded-lg transition duration-200 flex items-center gap-2 cursor-pointer select-none {{ $activeTab === 'tv' ? 'bg-amber-500 text-white shadow-md ring-2 ring-amber-400/50' : 'bg-gray-800 text-gray-300 hover:bg-gray-700 hover:text-white' }}"
            >
                <span>📺</span>
                <span>Dost TV</span>
            </button>

            <button 
                type="button" 
                wire:click="$set('activeTab', 'fm')" 
                class="px-6 py-3 text-sm font-bold rounded-lg transition duration-200 flex items-center gap-2 cursor-pointer select-none {{ $activeTab === 'fm' ? 'bg-amber-500 text-white shadow-md ring-2 ring-amber-400/50' : 'bg-gray-800 text-gray-300 hover:bg-gray-700 hover:text-white' }}"
            >
                <span>📻</span>
                <span>Dost FM</span>
            </button>
        </div>

        {{-- DOST TV SEKMESİ İÇERİĞİ --}}
        @if ($activeTab === 'tv')
            <div class="space-y-6">
                <div class="flex items-center justify-between bg-gray-900 border border-gray-800 rounded-xl p-4 shadow-sm">
                    <div class="flex items-center gap-2">
                        <span class="text-xl">📺</span>
                        <h2 class="text-lg font-bold text-white">Dost TV Canlı Yayın Yönetimi</h2>
                    </div>

                    <div class="flex items-center gap-3">
                        <x-filament::button 
                            type="button" 
                            wire:click="testTvConnection" 
                            color="gray" 
                            icon="heroicon-o-arrow-path"
                        >
                            Bağlantıyı Test Et
                        </x-filament::button>

                        <x-filament::button 
                            tag="a" 
                            href="{{ route('live.tv') }}" 
                            target="_blank" 
                            color="amber" 
                            icon="heroicon-o-arrow-top-right-on-square"
                        >
                            Public Sayfayı Aç
                        </x-filament::button>
                    </div>
                </div>

                <form wire:submit.prevent="saveTv" class="space-y-6">
                    {{ $this->tvForm }}

                    <div class="flex justify-end pt-2">
                        <x-filament::button type="submit" color="amber" icon="heroicon-o-check" size="lg">
                            Değişiklikleri Kaydet
                        </x-filament::button>
                    </div>
                </form>
            </div>
        @endif

        {{-- DOST FM SEKMESİ İÇERİĞİ --}}
        @if ($activeTab === 'fm')
            <div class="space-y-6">
                <div class="flex items-center justify-between bg-gray-900 border border-gray-800 rounded-xl p-4 shadow-sm">
                    <div class="flex items-center gap-2">
                        <span class="text-xl">📻</span>
                        <h2 class="text-lg font-bold text-white">Dost FM Canlı Radyo Yönetimi</h2>
                    </div>

                    <div class="flex items-center gap-3">
                        <x-filament::button 
                            type="button" 
                            wire:click="testFmConnection" 
                            color="gray" 
                            icon="heroicon-o-arrow-path"
                        >
                            Bağlantıyı Test Et
                        </x-filament::button>

                        <x-filament::button 
                            tag="a" 
                            href="{{ route('live.radio') }}" 
                            target="_blank" 
                            color="amber" 
                            icon="heroicon-o-arrow-top-right-on-square"
                        >
                            Public Sayfayı Aç
                        </x-filament::button>
                    </div>
                </div>

                <form wire:submit.prevent="saveFm" class="space-y-6">
                    {{ $this->fmForm }}

                    <div class="flex justify-end pt-2">
                        <x-filament::button type="submit" color="amber" icon="heroicon-o-check" size="lg">
                            Değişiklikleri Kaydet
                        </x-filament::button>
                    </div>
                </form>
            </div>
        @endif

    </div>
</x-filament-panels::page>
