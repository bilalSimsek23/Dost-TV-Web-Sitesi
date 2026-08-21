<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6 flex flex-col sm:flex-row items-center justify-between gap-4 p-4 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
            <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                <x-filament::icon icon="heroicon-m-check-circle" class="h-5 w-5 text-emerald-500" />
                <span>Header ayarlarını kaydedin</span>
            </div>
            <div class="flex items-center gap-3">
                <x-filament::button type="submit" color="warning" icon="heroicon-o-check" size="md">
                    Header Ayarlarını Kaydet
                </x-filament::button>
            </div>
        </div>
    </form>

    <x-filament-actions::modals />
</x-filament-panels::page>
