@php
    $lastSync = $program->last_youtube_sync_at;
    $logs = $program->youtubeSyncLogs()->latest()->take(10)->get();
    $lastLog = $logs->first();
@endphp

@if(filled($program->youtube_playlist_url))
    <div class="p-4 mb-4 bg-white border border-gray-200 rounded-xl dark:bg-gray-900 dark:border-gray-800 space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-3 text-xs">
            <div class="flex items-center gap-2">
                <span class="font-bold text-gray-700 dark:text-gray-300">Son YouTube Kontrolü:</span>
                <span class="font-mono text-gray-600 dark:text-gray-400">
                    {{ $lastSync ? $lastSync->format('d.m.Y H:i') : 'Henüz kontrol edilmedi' }}
                </span>
            </div>

            @if($lastLog)
                <div class="flex items-center gap-2">
                    <span class="px-2 py-0.5 rounded text-[11px] font-semibold {{ $lastLog->status === 'success' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300' }}">
                        {{ $lastLog->status_label }}
                    </span>
                    <span class="text-gray-500">
                        @if($lastLog->status === 'failed')
                            {{ $lastLog->error_message ?? 'Hata oluştu' }}
                        @elseif($lastLog->new_videos > 0)
                            {{ $lastLog->new_videos }} yeni bölüm eklendi
                        @else
                            Yeni video bulunamadı
                        @endif
                    </span>
                </div>
            @endif
        </div>

        @if($logs->isNotEmpty())
            <details class="text-xs group">
                <summary class="cursor-pointer text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 font-medium list-none flex items-center gap-1 select-none">
                    <span>Senkronizasyon Geçmişi (Son {{ $logs->count() }} Kontrol)</span>
                    <span class="transition-transform group-open:rotate-180">▼</span>
                </summary>
                
                <div class="mt-2 overflow-x-auto border border-gray-100 dark:border-gray-800 rounded-lg">
                    <table class="w-full text-[11px] text-left text-gray-600 dark:text-gray-400">
                        <thead class="bg-gray-50 dark:bg-gray-800/50 uppercase text-[10px] text-gray-500">
                            <tr>
                                <th class="px-3 py-1.5">Tarih</th>
                                <th class="px-3 py-1.5 text-center">Sonuç</th>
                                <th class="px-3 py-1.5 text-center">Kontrol</th>
                                <th class="px-3 py-1.5 text-center">Yeni</th>
                                <th class="px-3 py-1.5 text-center">Eklenen</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach($logs as $log)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30">
                                    <td class="px-3 py-1.5 font-mono">
                                        {{ $log->finished_at ? $log->finished_at->format('d.m.Y H:i') : ($log->created_at ? $log->created_at->format('d.m.Y H:i') : '-') }}
                                    </td>
                                    <td class="px-3 py-1.5 text-center">
                                        <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-semibold {{ $log->status === 'success' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300' }}">
                                            {{ $log->status_label }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-1.5 text-center font-mono">{{ $log->checked_videos }}</td>
                                    <td class="px-3 py-1.5 text-center font-mono text-emerald-600 dark:text-emerald-400">{{ $log->new_videos }}</td>
                                    <td class="px-3 py-1.5 text-center font-mono">{{ $log->created_episodes }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </details>
        @endif
    </div>
@endif
