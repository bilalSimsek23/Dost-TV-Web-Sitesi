<div class="space-y-4">
    {{-- Üst Bar: Arama ve Yeni Menü Öğesi Ekle Butonu --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="w-full sm:max-w-md">
            <x-filament::input.wrapper prefix-icon="heroicon-m-magnifying-glass">
                <x-filament::input
                    type="search"
                    wire:model.live.debounce.250ms="menuSearch"
                    placeholder="Menü ara..."
                />
            </x-filament::input.wrapper>
        </div>

        <div class="shrink-0">
            {{ $this->createMenuItemAction }}
        </div>
    </div>

    {{-- Native Filament Tablo Yapısı --}}
    <div class="fi-ta-ctn divide-y divide-gray-200 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:divide-white/10 dark:bg-gray-900 dark:ring-white/10">
        <table class="fi-ta-table w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5">
            <thead class="bg-gray-50 dark:bg-white/5">
                <tr>
                    <th class="fi-ta-header-cell w-12 px-3 py-3.5 text-center"></th>
                    <th class="fi-ta-header-cell px-4 py-3.5 text-start">
                        <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Menü Öğesi</span>
                    </th>
                    <th class="fi-ta-header-cell px-4 py-3.5 text-start">
                        <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Tür</span>
                    </th>
                    <th class="fi-ta-header-cell w-28 px-4 py-3.5 text-center">
                        <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Durum</span>
                    </th>
                    <th class="fi-ta-header-cell w-48 px-4 py-3.5 text-end">
                        <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">İşlem</span>
                    </th>
                </tr>
            </thead>
            <tbody
                x-data="{
                    init() {
                        if (typeof Sortable !== 'undefined') {
                            Sortable.create(this.$el, {
                                handle: '.drag-handle',
                                animation: 150,
                                ghostClass: 'bg-amber-500/10',
                                onEnd: (evt) => {
                                    const items = Array.from(this.$el.querySelectorAll('[data-id]')).map(el => el.getAttribute('data-id'));
                                    $wire.reorderMenuItems(items);
                                }
                            });
                        }
                    }
                }"
                class="divide-y divide-gray-200 dark:divide-white/5"
            >
                @forelse ($menuItems as $item)
                    <tr data-id="{{ $item->id }}" class="fi-ta-row hover:bg-gray-50 dark:hover:bg-white/5 transition duration-75">
                        {{-- Drag Handle --}}
                        <td class="px-3 py-3.5 text-center align-middle">
                            <button type="button" class="drag-handle cursor-grab active:cursor-grabbing text-gray-400 hover:text-amber-500 dark:hover:text-amber-400 p-1.5 rounded transition" title="Sıralamak için sürükleyin">
                                <x-filament::icon icon="heroicon-m-bars-2" class="h-5 w-5" />
                            </button>
                        </td>

                        {{-- Menü Başlığı & Detayı --}}
                        <td class="px-4 py-3.5 align-middle">
                            <div class="flex items-center gap-2">
                                @if ($item->icon)
                                    <span class="text-gray-400"><i class="{{ $item->icon }}"></i></span>
                                @endif
                                <span class="font-semibold text-sm text-gray-950 dark:text-white">
                                    {{ $item->title }}
                                </span>
                                @if ($item->badge_text)
                                    <span class="inline-flex items-center rounded-md bg-rose-500/10 px-2 py-0.5 text-xs font-medium text-rose-400 ring-1 ring-inset ring-rose-500/20">
                                        {{ $item->badge_text }}
                                    </span>
                                @endif
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                {{ $item->resolved_url ?? '—' }}
                            </div>
                        </td>

                        {{-- Menü Türü --}}
                        <td class="px-4 py-3.5 align-middle whitespace-nowrap">
                            <span class="inline-flex items-center rounded-md bg-slate-800 px-2.5 py-1 text-xs font-medium text-slate-300 ring-1 ring-inset ring-slate-700">
                                {{ \App\Models\MenuItem::ITEM_TYPES[$item->item_type] ?? $item->item_type }}
                            </span>
                        </td>

                        {{-- Durum --}}
                        <td class="px-4 py-3.5 text-center align-middle whitespace-nowrap">
                            @if ($item->is_active)
                                <span class="inline-flex items-center rounded-md bg-emerald-500/10 px-2 py-1 text-xs font-medium text-emerald-400 ring-1 ring-inset ring-emerald-500/20">
                                    Yayında
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-md bg-gray-500/10 px-2 py-1 text-xs font-medium text-gray-400 ring-1 ring-inset ring-gray-500/20">
                                    Pasif
                                </span>
                            @endif
                        </td>

                        {{-- İşlemler --}}
                        <td class="px-4 py-3.5 text-end align-middle whitespace-nowrap">
                            <div class="flex items-center justify-end gap-2">
                                {{ ($this->editMenuItemAction)(['item' => $item->id]) }}
                                {{ ($this->deleteMenuItemAction)(['item' => $item->id]) }}
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-8 text-center text-sm text-gray-500 dark:text-gray-400">
                            Arama kriterine uygun menü öğesi bulunamadı.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Sıralama Bilgi Notu --}}
    <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 pt-1">
        <x-filament::icon icon="heroicon-m-information-circle" class="h-4 w-4 text-gray-400" />
        <span>Sıralamayı değiştirmek için sürükleyip bırakabilirsiniz.</span>
    </div>
</div>
