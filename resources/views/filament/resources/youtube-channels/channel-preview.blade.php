@php
    $logoUrl = filled($logo)
        ? ((str_starts_with($logo, 'http://') || str_starts_with($logo, 'https://')) ? $logo : \Illuminate\Support\Facades\Storage::disk('public')->url($logo))
        : null;
@endphp

<div class="flex items-center gap-4 p-4 rounded-xl bg-slate-900/90 border border-emerald-500/40 text-white shadow-lg">
    @if (! empty($logoUrl))
        <img src="{{ $logoUrl }}" alt="{{ $name }}" class="w-16 h-16 rounded-full object-cover border-2 border-emerald-500/50 shadow-md" />
    @else
        <div class="w-16 h-16 rounded-full bg-slate-800 border border-slate-700 flex items-center justify-center text-slate-400 font-bold text-lg">
            {{ mb_substr($name ?: 'Y', 0, 1) }}
        </div>
    @endif

    <div class="flex flex-col">
        <span class="font-bold text-base text-slate-100">{{ $name ?: 'Kanal Adı' }}</span>
        @if (! empty($handle))
            <span class="text-xs text-slate-400 font-mono mt-0.5">{{ $handle }}</span>
        @endif
        <span class="inline-flex items-center gap-1.5 text-xs font-bold text-emerald-400 mt-1.5 bg-emerald-500/10 px-2.5 py-0.5 rounded-full border border-emerald-500/20 w-fit">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
            Kanal bulundu
        </span>
    </div>
</div>
