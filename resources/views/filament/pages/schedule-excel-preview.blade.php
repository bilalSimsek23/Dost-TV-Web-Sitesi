<div class="space-y-4 rounded-xl border border-slate-700/60 bg-slate-900/50 p-4">
    @if(empty($previewData))
        <div class="flex flex-col items-center justify-center p-6 text-center">
            <div class="mb-3 rounded-full bg-slate-800 p-3 text-amber-400">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
            </div>
            <h4 class="text-sm font-semibold text-slate-200">Excel Dosyası Yükleyin</h4>
            <p class="mt-1 text-xs text-slate-400">Haftalık veya günlük yayın akışınızı toplu aktarmak için şablon formatına uygun .xlsx, .xls veya .csv dosyası yükleyin.</p>
        </div>
    @else
        <!-- Summary Badges -->
        <div class="grid grid-cols-3 gap-3">
            <div class="rounded-lg border border-slate-700/50 bg-slate-800/80 p-3 text-center">
                <span class="block text-xs text-slate-400">Toplam Satır</span>
                <span class="text-lg font-bold text-slate-100">{{ $previewData['total_count'] ?? 0 }}</span>
            </div>
            <div class="rounded-lg border border-emerald-500/30 bg-emerald-950/30 p-3 text-center">
                <span class="block text-xs text-emerald-400">Aktarılmaya Hazır</span>
                <span class="text-lg font-bold text-emerald-300">{{ $previewData['valid_count'] ?? 0 }}</span>
            </div>
            <div class="rounded-lg border {{ ($previewData['error_count'] ?? 0) > 0 ? 'border-rose-500/40 bg-rose-950/40' : 'border-slate-700/50 bg-slate-800/80' }} p-3 text-center">
                <span class="block text-xs {{ ($previewData['error_count'] ?? 0) > 0 ? 'text-rose-400' : 'text-slate-400' }}">Hatalı / Engellenen</span>
                <span class="text-lg font-bold {{ ($previewData['error_count'] ?? 0) > 0 ? 'text-rose-300' : 'text-slate-100' }}">{{ $previewData['error_count'] ?? 0 }}</span>
            </div>
        </div>

        @if(!empty($previewData['general_error']))
            <div class="rounded-lg border border-rose-500/50 bg-rose-950/50 p-3 text-xs text-rose-200">
                <strong>Hata:</strong> {{ $previewData['general_error'] }}
            </div>
        @endif

        <!-- Preview Table -->
        @if(!empty($previewData['rows']))
            <div class="max-h-64 overflow-y-auto rounded-lg border border-slate-700/80 bg-slate-950/60">
                <table class="w-full text-left text-xs">
                    <thead class="sticky top-0 bg-slate-800 text-slate-300">
                        <tr>
                            <th class="px-3 py-2">Satır</th>
                            <th class="px-3 py-2">Gün</th>
                            <th class="px-3 py-2">Saat</th>
                            <th class="px-3 py-2">Program</th>
                            <th class="px-3 py-2">Yayın Türü</th>
                            <th class="px-3 py-2">Durum</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800 text-slate-200">
                        @foreach($previewData['rows'] as $row)
                            <tr class="{{ $row['status'] === 'error' ? 'bg-rose-950/20' : 'hover:bg-slate-800/40' }}">
                                <td class="px-3 py-2 font-mono text-slate-400">#{{ $row['row_num'] }}</td>
                                <td class="px-3 py-2 font-medium">{{ $row['day_name'] }}</td>
                                <td class="px-3 py-2 font-mono text-amber-400">{{ $row['start_time'] ?: $row['raw_start'] }} - {{ $row['end_time'] ?: $row['raw_end'] }}</td>
                                <td class="px-3 py-2 font-semibold {{ empty($row['program_id']) ? 'text-rose-400' : 'text-slate-100' }}">
                                    {{ $row['program_name'] }}
                                </td>
                                <td class="px-3 py-2">
                                    @if($row['is_live'])
                                        <span class="rounded bg-rose-500/20 px-1.5 py-0.5 text-[10px] font-bold text-rose-300">CANLI</span>
                                    @elseif($row['is_repeat'])
                                        <span class="rounded bg-sky-500/20 px-1.5 py-0.5 text-[10px] font-bold text-sky-300">TEKRAR</span>
                                    @else
                                        <span class="rounded bg-slate-700/60 px-1.5 py-0.5 text-[10px] text-slate-300">NORMAL</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2">
                                    @if($row['status'] === 'ready')
                                        <span class="inline-flex items-center gap-1 text-emerald-400">
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                            Hazır
                                        </span>
                                    @else
                                        <div class="text-rose-400">
                                            @foreach($row['errors'] as $err)
                                                <div class="text-[11px]">• {{ $err }}</div>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @endif
</div>
