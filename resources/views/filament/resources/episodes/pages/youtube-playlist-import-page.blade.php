<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Form Section -->
        <div class="p-6 bg-white rounded-xl shadow-sm border border-gray-200 dark:bg-gray-900 dark:border-gray-800 space-y-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                YouTube Playlist İçe Aktarma Ayarları
            </h3>
            
            <form wire:submit.prevent="fetchPreview" class="space-y-6">
                {{ $this->form }}

                <div class="flex items-center gap-4 pt-4 border-t border-gray-100 dark:border-gray-800">
                    <x-filament::button
                        type="submit"
                        color="danger"
                        wire:loading.attr="disabled"
                    >
                        Playlist’i Kontrol Et
                    </x-filament::button>

                    @if($isPreviewLoaded)
                        <span class="text-sm text-gray-500 dark:text-gray-400">
                            Kontrol tamamlandı. Önizleme tablosunu aşağıda inceleyebilirsiniz.
                        </span>
                    @endif
                </div>
            </form>
        </div>

        <!-- Import Completed Card -->
        @if($isImported)
            <div class="p-6 bg-emerald-50 border border-emerald-200 rounded-xl dark:bg-emerald-950/30 dark:border-emerald-800/50 space-y-4">
                <div class="flex items-start gap-3">
                    <x-heroicon-o-check-circle class="w-7 h-7 text-emerald-600 dark:text-emerald-400 flex-shrink-0 mt-0.5" />
                    <div>
                        <h4 class="text-lg font-bold text-emerald-900 dark:text-emerald-200">
                            Bölümler Başarıyla Oluşturuldu!
                        </h4>
                        <p class="text-sm text-emerald-700 dark:text-emerald-300 mt-1">
                            Toplam {{ $importedCount }} yeni bölüm programa eklendi. {{ $skippedCount }} mevcut video atlandı.
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3 pt-2">
                    <x-filament::button
                        tag="a"
                        href="{{ url('/admin/episodes?tableFilters[program_id][value]=' . $program_id) }}"
                        color="success"
                        icon="heroicon-m-film"
                    >
                        Bölümleri Gör
                    </x-filament::button>

                    <x-filament::button
                        tag="a"
                        href="{{ url('/admin/programs/' . $program_id . '/edit') }}"
                        color="gray"
                        icon="heroicon-m-rectangle-stack"
                    >
                        Programa Git
                    </x-filament::button>
                </div>
            </div>
        @endif

        <!-- Preview Results Section -->
        @if($isPreviewLoaded && !empty($previewItems))
            <div class="p-6 bg-white rounded-xl shadow-sm border border-gray-200 dark:bg-gray-900 dark:border-gray-800 space-y-6">
                <!-- Compact Header Summary -->
                <div class="flex items-center justify-between gap-4 pb-4 border-b border-gray-100 dark:border-gray-800">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">
                        Playlist Önizlemesi
                    </h3>

                    <div class="flex items-center gap-2 text-xs font-semibold">
                        <span class="px-2.5 py-1 rounded-md bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                            Toplam: <strong>{{ $totalItemsCount }}</strong>
                        </span>
                        <span class="px-2.5 py-1 rounded-md bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                            Yeni: <strong>{{ $newItemsCount }}</strong>
                        </span>
                        <span class="px-2.5 py-1 rounded-md bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                            Mevcut: <strong>{{ $existingItemsCount }}</strong>
                        </span>
                    </div>
                </div>

                <!-- Compact Preview Table -->
                <div class="overflow-x-auto border border-gray-200 rounded-lg dark:border-gray-800">
                    <table class="w-full text-sm text-left text-gray-600 dark:text-gray-300">
                        <thead class="text-xs uppercase bg-gray-50 text-gray-700 dark:bg-gray-800 dark:text-gray-400 border-b border-gray-200 dark:border-gray-800">
                            <tr>
                                <th scope="col" class="px-3 py-3 w-12 text-center">#</th>
                                <th scope="col" class="px-3 py-3 w-[130px]">Thumbnail</th>
                                <th scope="col" class="px-4 py-3">Bölüm Başlığı</th>
                                <th scope="col" class="px-3 py-3 text-center w-32">Yayın Tarihi</th>
                                <th scope="col" class="px-3 py-3 text-center w-24">Durum</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                            @foreach($previewItems as $idx => $item)
                                <tr class="{{ $item['is_duplicate'] ? 'bg-gray-50/50 dark:bg-gray-900/40 opacity-75' : 'hover:bg-gray-50 dark:hover:bg-gray-800/50' }}">
                                    <td class="px-3 py-3 text-center font-mono text-xs font-bold text-gray-500">
                                        {{ $idx + 1 }}
                                    </td>
                                    <td class="px-3 py-3">
                                        <div class="w-[120px] h-[68px] flex-shrink-0 bg-gray-100 dark:bg-gray-800 rounded overflow-hidden border border-gray-200 dark:border-gray-700">
                                            @if(!empty($item['thumbnail_url']))
                                                <img src="{{ $item['thumbnail_url'] }}" alt="{{ $item['processed_title'] }}" class="w-full h-full object-cover rounded" loading="lazy" />
                                            @else
                                                <div class="w-full h-full flex items-center justify-center text-[10px] text-gray-400 font-mono">
                                                    Görsel Yok
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-900 dark:text-white line-clamp-2 break-words">
                                            {{ $item['processed_title'] }}
                                        </div>
                                        @if($item['processed_title'] !== $item['raw_title'])
                                            <div class="text-xs text-gray-400 line-through line-clamp-1 mt-0.5">
                                                {{ $item['raw_title'] }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3 text-center text-xs font-mono text-gray-500 whitespace-nowrap">
                                        {{ $item['published_at_formatted'] ?? ($item['published_at'] ?? '-') }}
                                    </td>
                                    <td class="px-3 py-3 text-center whitespace-nowrap">
                                        @if($item['is_duplicate'])
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                                Mevcut
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                                                Yeni
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Bottom Action -->
                <div class="flex items-center justify-end pt-4 border-t border-gray-100 dark:border-gray-800">
                    <x-filament::button
                        wire:click="importEpisodes"
                        color="success"
                        :disabled="$newItemsCount === 0"
                        wire:loading.attr="disabled"
                    >
                        Bölümleri Oluştur
                    </x-filament::button>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
