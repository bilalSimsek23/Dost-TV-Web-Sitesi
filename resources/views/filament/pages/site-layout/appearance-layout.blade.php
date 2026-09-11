<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6 flex items-center justify-end gap-3">
            <x-filament::button type="button" color="amber" icon="heroicon-o-eye" wire:click="livePreview" wire:loading.attr="disabled" wire:target="livePreview">
                <span wire:loading.remove wire:target="livePreview">Canlı Test Et</span>
                <span wire:loading wire:target="livePreview">Test Başlatılıyor...</span>
            </x-filament::button>

            <x-filament::button type="submit" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">Görünüm Ayarlarını Kaydet</span>
                <span wire:loading wire:target="save">Kaydediliyor...</span>
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
