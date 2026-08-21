<x-filament-widgets::widget>
    <div class="fi-wi-card p-6 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm h-full flex flex-col justify-between">
        {{-- Header --}}
        <div>
            <div class="flex items-center justify-between gap-3 pb-4 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-slate-500/10 text-slate-500">
                        <x-heroicon-o-clock class="w-5 h-5" />
                    </span>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Son İşlemler</h3>
                </div>

                <a href="{{ $auditLogsUrl }}" class="text-xs font-semibold text-rose-600 dark:text-rose-400 hover:underline inline-flex items-center gap-1">
                    Tüm İşlem Geçmişi &rarr;
                </a>
            </div>

            {{-- Audit List --}}
            @if($hasLogs)
                <div class="mt-3 divide-y divide-slate-100 dark:divide-slate-800/80">
                    @foreach($logs as $log)
                        <div class="py-2.5 flex items-start justify-between gap-3 text-xs">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-semibold text-slate-900 dark:text-white">
                                        {{ $log->user?->name ?? $log->user_name_snapshot ?? 'Kullanıcı' }}
                                    </span>
                                    <span @class([
                                        'px-1.5 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wider',
                                        'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20' => in_array($log->action, ['created', 'published', 'activated', 'restored']),
                                        'bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20' => in_array($log->action, ['updated', 'synced', 'imported', 'role_changed', 'invited']),
                                        'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20' => in_array($log->action, ['deactivated', 'unpublished', 'archived', 'invitation_resent']),
                                        'bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20' => $log->action === 'deleted' || $log->is_destructive,
                                        'bg-slate-500/10 text-slate-600 dark:text-slate-400 border border-slate-500/20' => !in_array($log->action, ['created', 'published', 'activated', 'restored', 'updated', 'synced', 'imported', 'role_changed', 'invited', 'deactivated', 'unpublished', 'archived', 'invitation_resent', 'deleted']),
                                    ])>
                                        {{ $log->action_label }}
                                    </span>
                                </div>
                                <p class="text-slate-600 dark:text-slate-400 truncate mt-0.5">
                                    {{ $log->subject_label ?: $log->message }}
                                </p>
                            </div>

                            <span class="text-[11px] text-slate-400 shrink-0 whitespace-nowrap">
                                {{ $log->created_at?->diffForHumans() }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="py-6 text-center text-slate-500 dark:text-slate-400 text-xs">
                    Henüz kayıtlı bir kullanıcı işlemi bulunmuyor.
                </div>
            @endif
        </div>
    </div>
</x-filament-widgets::widget>
