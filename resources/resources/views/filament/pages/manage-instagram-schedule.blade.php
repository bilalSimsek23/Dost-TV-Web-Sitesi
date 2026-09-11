<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6 flex items-center justify-end gap-x-4">
            <x-filament::button type="submit" size="lg" color="rose">
                Yayın Planını Kaydet
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
