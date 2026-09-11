<x-filament-panels::page>
    {{-- Professional Segmented Tab Bar Container --}}
    <div class="mb-6 p-1.5 bg-gray-100 dark:bg-gray-900/90 rounded-xl border border-gray-200/80 dark:border-gray-800 flex items-center gap-1.5 overflow-x-auto scrollbar-none whitespace-nowrap shadow-inner">
        {{-- Tab 1: REELS --}}
        <button type="button"
                wire:click="$set('activeTab', 'reels')"
                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-semibold transition-all duration-200 whitespace-nowrap shrink-0 {{ $activeTab === 'reels' ? 'bg-white dark:bg-rose-600 text-rose-600 dark:text-white shadow-sm border border-gray-200/80 dark:border-rose-500 ring-1 ring-black/5 dark:ring-white/10' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-200/60 dark:hover:bg-gray-800/60' }}">
            <x-heroicon-o-video-camera class="w-5 h-5 shrink-0" style="width: 1.25rem; height: 1.25rem; min-width: 1.25rem;" />
            <span>Reels</span>
        </button>

        {{-- Tab 2: KATEGORİLER --}}
        <button type="button"
                wire:click="$set('activeTab', 'categories')"
                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-semibold transition-all duration-200 whitespace-nowrap shrink-0 {{ $activeTab === 'categories' ? 'bg-white dark:bg-rose-600 text-rose-600 dark:text-white shadow-sm border border-gray-200/80 dark:border-rose-500 ring-1 ring-black/5 dark:ring-white/10' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-200/60 dark:hover:bg-gray-800/60' }}">
            <x-heroicon-o-tag class="w-5 h-5 shrink-0" style="width: 1.25rem; height: 1.25rem; min-width: 1.25rem;" />
            <span>Kategoriler</span>
        </button>

        {{-- Tab 3: HAFTALIK PLAN --}}
        <button type="button"
                wire:click="$set('activeTab', 'schedule')"
                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-semibold transition-all duration-200 whitespace-nowrap shrink-0 {{ $activeTab === 'schedule' ? 'bg-white dark:bg-rose-600 text-rose-600 dark:text-white shadow-sm border border-gray-200/80 dark:border-rose-500 ring-1 ring-black/5 dark:ring-white/10' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-200/60 dark:hover:bg-gray-800/60' }}">
            <x-heroicon-o-calendar class="w-5 h-5 shrink-0" style="width: 1.25rem; height: 1.25rem; min-width: 1.25rem;" />
            <span>Haftalık Plan</span>
        </button>

        {{-- Tab 4: GÖSTERİM AYARLARI --}}
        <button type="button"
                wire:click="$set('activeTab', 'settings')"
                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-semibold transition-all duration-200 whitespace-nowrap shrink-0 {{ $activeTab === 'settings' ? 'bg-white dark:bg-rose-600 text-rose-600 dark:text-white shadow-sm border border-gray-200/80 dark:border-rose-500 ring-1 ring-black/5 dark:ring-white/10' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-200/60 dark:hover:bg-gray-800/60' }}">
            <x-heroicon-o-adjustments-horizontal class="w-5 h-5 shrink-0" style="width: 1.25rem; height: 1.25rem; min-width: 1.25rem;" />
            <span>Gösterim Ayarları</span>
        </button>
    </div>

    {{-- Tab 1: REELS --}}
    @if ($activeTab === 'reels')
        <div class="space-y-4">
            {{ $this->table }}
        </div>
    @endif

    {{-- Tab 2: KATEGORİLER --}}
    @if ($activeTab === 'categories')
        <div class="space-y-4">
            <livewire:instagram-categories-table />
        </div>
    @endif

    {{-- Tab 3: HAFTALIK PLAN --}}
    @if ($activeTab === 'schedule')
        <form wire:submit="saveSchedule" class="space-y-6">
            {{ $this->scheduleForm }}

            <div class="flex items-center justify-end">
                <x-filament::button type="submit" size="lg" color="rose">
                    Haftalık Yayın Planını Kaydet
                </x-filament::button>
            </div>
        </form>
    @endif

    {{-- Tab 4: GÖSTERİM AYARLARI --}}
    @if ($activeTab === 'settings')
        <form wire:submit="saveSettings" class="space-y-6">
            {{ $this->settingsForm }}

            <div class="flex items-center justify-end">
                <x-filament::button type="submit" size="lg" color="rose">
                    Gösterim Ayarlarını Kaydet
                </x-filament::button>
            </div>
        </form>
    @endif
</x-filament-panels::page>
