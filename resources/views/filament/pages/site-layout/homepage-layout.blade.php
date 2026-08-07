<x-filament-panels::page>
    <form wire:submit.prevent="save" class="space-y-6">
        {{ $this->form }}

        <div class="flex justify-end pt-4">
            <x-filament::button type="submit" color="primary">
                Değişiklikleri Kaydet
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
