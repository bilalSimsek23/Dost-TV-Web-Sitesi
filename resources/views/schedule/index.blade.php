@extends('layouts.app')

@section('title', 'Yayın Akışı - Dost TV')

@section('content')
    <section class="mx-auto max-w-5xl px-4 py-16 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-white/10 pb-6">
            <div>
                <h1 class="text-3xl font-black text-white">Haftalık Yayın Akışı</h1>
                <p class="mt-2 text-slate-400">Dost TV güncel televizyon yayın akış planı.</p>
            </div>
            @if ($activeTemplate)
                <div class="inline-flex items-center gap-2 rounded-xl border border-rose-500/30 bg-rose-500/10 px-4 py-2 text-sm text-rose-300">
                    <span class="relative flex h-2 w-2">
                      <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
                      <span class="relative inline-flex rounded-full h-2 w-2 bg-rose-500"></span>
                    </span>
                    <span>Aktif Sezon: <strong>{{ $activeTemplate->name }}</strong> (v{{ $activeTemplate->version }})</span>
                </div>
            @endif
        </div>

        <div class="mt-10 space-y-8">
            @foreach ($days as $dayIndex => $dayName)
                <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-6 shadow-xl backdrop-blur-md">
                    <h2 class="text-xl font-bold text-white flex items-center gap-2">
                        <span class="inline-block w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                        {{ $dayName }}
                    </h2>
                    <ul class="mt-4 divide-y divide-white/5">
                        @forelse ($scheduleByDay->get($dayIndex, collect()) as $item)
                            <li class="flex flex-col sm:flex-row sm:items-center justify-between py-3.5 gap-2">
                                <div class="flex items-center gap-3">
                                    @if ($item->program)
                                        <a href="{{ route('programs.show', $item->program) }}" class="font-semibold text-white hover:text-rose-400 transition-colors">
                                            {{ $item->display_title ?? $item->program->name }}
                                        </a>
                                    @else
                                        <span class="font-semibold text-white">{{ $item->display_title ?? 'Yayın' }}</span>
                                    @endif

                                    @if ($item->is_live)
                                        <span class="rounded bg-red-600 px-2 py-0.5 text-xs font-bold uppercase tracking-wider text-white">CANLI</span>
                                    @endif

                                    @if ($item->is_repeat)
                                        <span class="rounded bg-amber-500/20 text-amber-300 border border-amber-500/30 px-2 py-0.5 text-xs font-medium">TEKRAR</span>
                                    @endif
                                </div>

                                <span class="self-start sm:self-auto rounded-full bg-white/10 px-3.5 py-1 text-sm font-bold text-rose-300 tracking-wide border border-white/10">
                                    {{ \Illuminate\Support\Carbon::parse($item->start_time)->format('H:i') }}
                                    @if ($item->end_time)
                                        - {{ \Illuminate\Support\Carbon::parse($item->end_time)->format('H:i') }}
                                    @endif
                                </span>
                            </li>
                        @empty
                            <li class="py-4 text-sm text-slate-500 italic">Bu gün için planlanmış yayın bulunmamaktadır.</li>
                        @endforelse
                    </ul>
                </div>
            @endforeach
        </div>
    </section>
@endsection
