<x-filament-panels::page>
    <div class="w-full space-y-6">

        {{-- 1. DOST TV / DOST FM TAB KARTI --}}
        <x-filament::tabs>
            <x-filament::tabs.item
                :active="$activeTab === 'tv'"
                wire:click="$set('activeTab', 'tv')"
                icon="heroicon-o-tv"
            >
                Dost TV
            </x-filament::tabs.item>

            <x-filament::tabs.item
                :active="$activeTab === 'fm'"
                wire:click="$set('activeTab', 'fm')"
                icon="heroicon-o-radio"
            >
                Dost FM
            </x-filament::tabs.item>
        </x-filament::tabs>

        {{-- 2. YÖNETİM ÖZET KARTI --}}
        <x-filament::section>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h2 class="text-base font-bold text-gray-950 dark:text-white">
                        {{ $activeTab === 'tv' ? 'Dost TV Canlı Yayın Yönetimi' : 'Dost FM Canlı Radyo Yönetimi' }}
                    </h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ $activeTab === 'tv' ? 'Televizyon yayını akış bağlantısı ve genel yayın parametreleri.' : 'Radyo ses akışı bağlantısı ve radyo kanalı parametreleri.' }}
                    </p>
                </div>

                <div class="flex items-center gap-3 shrink-0">
                    <x-filament::button 
                        type="button" 
                        wire:click="{{ $activeTab === 'tv' ? 'testTvConnection' : 'testFmConnection' }}" 
                        color="gray"
                        outlined
                        icon="heroicon-o-arrow-path"
                    >
                        Bağlantıyı Test Et
                    </x-filament::button>

                    <x-filament::button 
                        tag="a" 
                        href="{{ route($activeTab === 'tv' ? 'live.tv' : 'live.radio') }}" 
                        target="_blank" 
                        color="primary" 
                        icon="heroicon-o-arrow-top-right-on-square"
                    >
                        Public Sayfayı Aç
                    </x-filament::button>
                </div>
            </div>
        </x-filament::section>

        {{-- 3. ANA AYARLAR FORMU --}}
        @if ($activeTab === 'tv')
            <form wire:submit.prevent="saveTv" class="space-y-6">
                {{ $this->tvForm }}

                <div class="flex justify-end pt-2">
                    <x-filament::button type="submit" color="primary" icon="heroicon-o-check" size="lg">
                        Değişiklikleri Kaydet
                    </x-filament::button>
                </div>
            </form>
        @else
            <form wire:submit.prevent="saveFm" class="space-y-6">
                {{ $this->fmForm }}

                <div class="flex justify-end pt-2">
                    <x-filament::button type="submit" color="primary" icon="heroicon-o-check" size="lg">
                        Değişiklikleri Kaydet
                    </x-filament::button>
                </div>
            </form>
        @endif

    </div>
</x-filament-panels::page>
