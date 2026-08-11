<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Top Navigation / Actions -->
        <div class="flex flex-wrap items-center justify-between gap-4 p-4 bg-white rounded-xl shadow-sm border border-gray-200 dark:bg-gray-900 dark:border-gray-800">
            <div class="flex items-center gap-2">
                <x-filament::button
                    tag="a"
                    href="{{ url('/admin/schedule-templates') }}"
                    color="gray"
                    icon="heroicon-o-arrow-left"
                    size="sm"
                >
                    Yayın Dönemlerine Dön
                </x-filament::button>
            </div>

            <div class="flex items-center gap-3">
                <x-filament::button
                    tag="a"
                    href="{{ route('admin.schedule.download-template') }}"
                    color="info"
                    icon="heroicon-o-arrow-down-tray"
                    size="sm"
                >
                    DOST TV Excel Şablonunu İndir
                </x-filament::button>
            </div>
        </div>

        <!-- Form Section -->
        <div class="p-6 bg-white rounded-xl shadow-sm border border-gray-200 dark:bg-gray-900 dark:border-gray-800 space-y-6">
            <div>
                <h3 class="text-base font-bold text-gray-900 dark:text-white">
                    Excel Dosyası Yükleme & Önizleme
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    İndirilen standart DOST TV Excel şablonunu doldurduktan sonra buraya yükleyip kontrol ediniz.
                </p>
            </div>

            <form wire:submit.prevent="fetchPreview" class="space-y-4">
                {{ $this->form }}

                <div class="flex items-center gap-3 pt-2">
                    <x-filament::button
                        type="submit"
                        color="primary"
                        icon="heroicon-o-document-magnifying-glass"
                        wire:loading.attr="disabled"
                    >
                        Excel'i Kontrol Et ve Önizle
                    </x-filament::button>
                </div>
            </form>
        </div>

        <!-- Import Completed Success Card -->
        @if($isImported)
            <div class="p-6 bg-emerald-50 border border-emerald-200 rounded-xl dark:bg-emerald-950/30 dark:border-emerald-800/50 space-y-4">
                <div class="flex items-start gap-3">
                    <x-heroicon-o-check-circle class="w-6 h-6 text-emerald-600 dark:text-emerald-400 flex-shrink-0 mt-0.5" />
                    <div>
                        <h4 class="text-base font-bold text-emerald-900 dark:text-emerald-200">
                            Yayın Dönemi ve Akışı Başarıyla Oluşturuldu!
                        </h4>
                        <p class="text-sm text-emerald-700 dark:text-emerald-300 mt-1">
                            <strong>"{{ $createdTemplateName }}"</strong> isimli yayın dönemi <strong>Taslak/Hazır</strong> durumunda kaydedildi ve toplam <strong>{{ $importedItemsCount }}</strong> yayın satırı aktarıldı.
                        </p>
                        <p class="text-xs text-emerald-600 dark:text-emerald-400 mt-1">
                            Mevcut gösterimdeki yayın akışınız korunmuştur. Dilediğiniz zaman Yayın Dönemleri ekranından bu dönemi "Gösterimde Yap" butonu ile yayına alabilirsiniz.
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3 pt-2">
                    <x-filament::button
                        tag="a"
                        href="{{ route('filament.admin.pages.schedule-calendar', ['template' => $createdTemplateId]) }}"
                        color="success"
                        icon="heroicon-o-calendar"
                    >
                        Akışı Takvimde Aç
                    </x-filament::button>

                    <x-filament::button
                        tag="a"
                        href="{{ url('/admin/schedule-templates') }}"
                        color="gray"
                        icon="heroicon-o-rectangle-stack"
                    >
                        Yayın Dönemlerini Gör
                    </x-filament::button>
                </div>
            </div>
        @endif

        <!-- Preview Section -->
        @if($isPreviewLoaded && ! $isImported)
            <!-- Metadata & Stat Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="p-4 bg-white rounded-xl shadow-sm border border-gray-200 dark:bg-gray-900 dark:border-gray-800">
                    <span class="text-xs text-gray-500 dark:text-gray-400 block font-medium">Akış / Dönem Adı</span>
                    <span class="text-sm font-bold text-gray-900 dark:text-white mt-1 block truncate">
                        {{ $period_name ?: 'Belirtilmedi' }}
                    </span>
                </div>

                <div class="p-4 bg-white rounded-xl shadow-sm border border-gray-200 dark:bg-gray-900 dark:border-gray-800">
                    <span class="text-xs text-gray-500 dark:text-gray-400 block font-medium">Geçerlilik Tarihleri</span>
                    <span class="text-sm font-bold text-gray-900 dark:text-white mt-1 block">
                        {{ $valid_from_formatted ?: '-' }} → {{ $valid_until_formatted ?: '-' }}
                    </span>
                </div>

                <div class="p-4 bg-white rounded-xl shadow-sm border border-gray-200 dark:bg-gray-900 dark:border-gray-800">
                    <span class="text-xs text-gray-500 dark:text-gray-400 block font-medium">Toplam Yayın Satırı</span>
                    <span class="text-lg font-black text-gray-900 dark:text-white mt-0.5 block">
                        {{ $total_count }} Satır
                    </span>
                </div>

                <div class="p-4 bg-white rounded-xl shadow-sm border border-gray-200 dark:bg-gray-900 dark:border-gray-800">
                    <span class="text-xs text-gray-500 dark:text-gray-400 block font-medium">Doğrulama Durumu</span>
                    @if($has_errors)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 mt-1 rounded-md text-xs font-bold bg-red-100 text-red-800 dark:bg-red-950/50 dark:text-red-300">
                            ⚠ {{ $error_count }} Hata Tespit Edildi
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 mt-1 rounded-md text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300">
                            ✓ 24 Saat Tamamlandı (Hata Yok)
                        </span>
                    @endif
                </div>
            </div>

            <!-- Error Box -->
            @if($has_errors && ! empty($errorsList))
                <div class="p-5 bg-red-50 border border-red-200 rounded-xl dark:bg-red-950/30 dark:border-red-800/50 space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-red-600 dark:text-red-400" />
                            <h4 class="text-sm font-bold text-red-900 dark:text-red-200">
                                Düzeltilmesi Gereken Hatalar ({{ count($errorsList) }})
                            </h4>
                        </div>

                        <x-filament::button
                            tag="a"
                            href="{{ route('schedule.excel.errors', ['key' => base64_encode(json_encode($errorsList))]) }}"
                            color="danger"
                            icon="heroicon-o-arrow-down-tray"
                            size="xs"
                        >
                            Hata Raporunu İndir
                        </x-filament::button>
                    </div>

                    <div class="max-h-60 overflow-y-auto divide-y divide-red-100 dark:divide-red-900/30 text-xs text-red-800 dark:text-red-300">
                        @foreach($errorsList as $err)
                            <div class="py-2 flex items-start gap-2">
                                <span class="font-bold text-red-900 dark:text-red-100 min-w-[120px]">
                                    {{ $err['row_num'] }}:
                                </span>
                                <span>{{ $err['message'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- 7-Day Overview & Tables -->
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <h4 class="text-sm font-bold text-gray-900 dark:text-white">
                        Haftalık Yayın Akışı Önizlemesi (Pazartesi – Pazar)
                    </h4>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-2">
                    @foreach($days_summary as $dayIdx => $summary)
                        <button
                            type="button"
                            wire:click="$set('selectedDay', '{{ $dayIdx }}')"
                            class="p-3 text-left rounded-lg border transition text-xs select-none {{ ($selectedDay == (string) $dayIdx) ? 'border-amber-500 bg-amber-50/50 dark:bg-amber-950/20' : 'border-gray-200 bg-white dark:bg-gray-900 dark:border-gray-800' }}"
                        >
                            <div class="font-bold text-gray-900 dark:text-white flex items-center justify-between">
                                <span>{{ $summary['day_name'] }}</span>
                                @if($summary['status'] === 'ready')
                                    <span class="text-emerald-600 dark:text-emerald-400 font-bold">✓</span>
                                @else
                                    <span class="text-red-500 font-bold">⚠</span>
                                @endif
                            </div>
                            <span class="text-gray-500 dark:text-gray-400 text-[11px] block mt-1">
                                {{ $summary['count'] }} Yayın
                            </span>
                        </button>
                    @endforeach
                </div>

                <!-- Preview Table -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 dark:bg-gray-900 dark:border-gray-800 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-800/60 border-b border-gray-200 dark:border-gray-800 text-gray-700 dark:text-gray-300 font-semibold">
                                    <th class="py-2.5 px-3">Gün</th>
                                    <th class="py-2.5 px-3">Saat Aralığı</th>
                                    <th class="py-2.5 px-3">Program Adı</th>
                                    <th class="py-2.5 px-3">Yayın Türü</th>
                                    <th class="py-2.5 px-3">Not</th>
                                    <th class="py-2.5 px-3 text-center">Durum</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800/60">
                                @php
                                    $filteredRows = ($selectedDay === 'all')
                                        ? $rows
                                        : array_filter($rows, fn($r) => (string)$r['day_of_week'] === (string)$selectedDay);
                                @endphp

                                @forelse($filteredRows as $row)
                                    <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition {{ $row['status'] === 'error' ? 'bg-red-50/30 dark:bg-red-950/10' : '' }}">
                                        <td class="py-2.5 px-3 font-medium text-gray-900 dark:text-white">
                                            {{ $row['day_name'] }}
                                        </td>
                                        <td class="py-2.5 px-3 font-mono font-bold text-gray-800 dark:text-gray-200 whitespace-nowrap">
                                            {{ $row['start_time'] }} → {{ $row['end_time'] }}
                                            @if($row['is_overnight'] ?? false)
                                                <span class="text-[10px] text-amber-500 font-normal ml-1">(Ertesi Gün)</span>
                                            @endif
                                        </td>
                                        <td class="py-2.5 px-3 font-medium text-gray-900 dark:text-white">
                                            {{ $row['program_name'] }}
                                        </td>
                                        <td class="py-2.5 px-3">
                                            @if($row['is_live'])
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-800 dark:bg-red-950/50 dark:text-red-300">CANLI</span>
                                            @elseif($row['is_repeat'])
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-800 dark:bg-blue-950/50 dark:text-blue-300">TEKRAR</span>
                                            @else
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300">{{ $row['raw_type'] ?: 'Normal' }}</span>
                                            @endif
                                        </td>
                                        <td class="py-2.5 px-3 text-gray-500 dark:text-gray-400">
                                            {{ $row['note'] ?: '-' }}
                                        </td>
                                        <td class="py-2.5 px-3 text-center">
                                            @if($row['status'] === 'ready')
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300">
                                                    Geçerli
                                                </span>
                                            @else
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-800 dark:bg-red-950/50 dark:text-red-300" title="{{ implode(', ', $row['errors'] ?? []) }}">
                                                    Hatalı
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="py-6 text-center text-gray-500 dark:text-gray-400">
                                            Bu gün için gösterilecek yayın satırı bulunamadı.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Create Action Button Container -->
            <div class="p-6 bg-white rounded-xl shadow-sm border border-gray-200 dark:bg-gray-900 dark:border-gray-800 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h4 class="text-sm font-bold text-gray-900 dark:text-white">
                        İşlemi Tamamla
                    </h4>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        @if($has_errors)
                            Excel dosyasında tespit edilen hatalar giderilmeden yayın dönemi oluşturulamaz.
                        @else
                            Tüm kontroller başarıyla tamamlandı. Tek işlemde yayın dönemi ve akışı oluşturabilirsiniz.
                        @endif
                    </p>
                </div>

                <x-filament::button
                    type="button"
                    wire:click="createSchedulePeriod"
                    color="success"
                    icon="heroicon-o-check"
                    :disabled="$has_errors || $total_count === 0"
                    wire:loading.attr="disabled"
                >
                    Yayın Dönemini Oluştur
                </x-filament::button>
            </div>
        @endif
    </div>
</x-filament-panels::page>
