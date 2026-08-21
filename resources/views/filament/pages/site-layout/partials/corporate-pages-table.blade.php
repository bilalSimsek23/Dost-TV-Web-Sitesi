<div class="space-y-4">
    {{-- Üst Arama ve Yeni Ekle Barı --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="w-full sm:max-w-md">
            <x-filament::input.wrapper prefix-icon="heroicon-m-magnifying-glass">
                <x-filament::input
                    type="search"
                    wire:model.live.debounce.250ms="search"
                    placeholder="Kurumsal bilgi ara..."
                />
            </x-filament::input.wrapper>
        </div>

        <div class="shrink-0">
            <x-filament::button
                tag="a"
                href="{{ \App\Filament\Resources\Pages\PageResource::getUrl('create') }}"
                color="warning"
                icon="heroicon-o-plus"
                size="md"
            >
                Yeni Kurumsal Bilgi
            </x-filament::button>
        </div>
    </div>

    {{-- Native Filament Tablo Yapısı --}}
    <div class="fi-ta-ctn divide-y divide-gray-200 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:divide-white/10 dark:bg-gray-900 dark:ring-white/10">
        <table class="fi-ta-table w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5">
            <thead class="bg-gray-50 dark:bg-white/5">
                <tr>
                    <th class="fi-ta-header-cell w-12 px-3 py-3.5 text-center"></th>
                    <th class="fi-ta-header-cell px-4 py-3.5 text-start">
                        <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Sayfa</span>
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
                                    $wire.reorderCorporatePages(items);
                                }
                            });
                        }
                    }
                }"
                class="divide-y divide-gray-200 dark:divide-white/5"
            >
                @forelse ($corporatePages as $page)
                    @php
                        $editUrl = \App\Filament\Resources\Pages\PageResource::getUrl('edit', ['record' => $page]);
                        $publicUrl = route('pages.show', $page->slug);
                    @endphp
                    <tr data-id="{{ $page->id }}" class="fi-ta-row hover:bg-gray-50 dark:hover:bg-white/5 transition duration-75">
                        {{-- Drag Handle --}}
                        <td class="px-3 py-3.5 text-center align-middle">
                            <button type="button" class="drag-handle cursor-grab active:cursor-grabbing text-gray-400 hover:text-amber-500 dark:hover:text-amber-400 p-1.5 rounded transition" title="Sıralamak için sürükleyin">
                                <x-filament::icon icon="heroicon-m-bars-2" class="h-5 w-5" />
                            </button>
                        </td>

                        {{-- Sayfa Adı --}}
                        <td class="px-4 py-3.5 align-middle">
                            <a href="{{ $editUrl }}" class="font-semibold text-sm text-gray-950 dark:text-white hover:text-amber-500 dark:hover:text-amber-400 transition">
                                {{ $page->title }}
                            </a>
                        </td>

                        {{-- İşlemler --}}
                        <td class="px-4 py-3.5 text-end align-middle whitespace-nowrap">
                            <div class="flex items-center justify-end gap-2">
                                <x-filament::button
                                    tag="a"
                                    href="{{ $editUrl }}"
                                    color="gray"
                                    outlined
                                    size="sm"
                                    icon="heroicon-m-pencil-square"
                                >
                                    Düzenle
                                </x-filament::button>

                                <x-filament::icon-button
                                    tag="a"
                                    href="{{ $publicUrl }}"
                                    target="_blank"
                                    color="gray"
                                    icon="heroicon-m-arrow-top-right-on-square"
                                    size="sm"
                                    tooltip="Public Sayfayı Aç"
                                />
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="p-8 text-center text-sm text-gray-500 dark:text-gray-400">
                            Arama kriterine uygun kurumsal bilgi bulunamadı.
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
