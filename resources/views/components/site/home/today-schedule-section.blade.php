@props([
    'todaySchedule' => collect(),
])

<section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
    <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-6 shadow-2xl">
        <h2 class="flex items-center gap-2 text-sm font-semibold uppercase tracking-wider text-slate-400">
            <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
            Bugünün Yayın Akışı
        </h2>
        <ul class="mt-4 divide-y divide-white/5">
            @forelse ($todaySchedule as $item)
                <li class="flex items-center justify-between py-3">
                    <div>
                        <p class="font-medium text-white">{{ $item->program->name }}</p>
                        <p class="text-xs text-slate-500">{{ $item->program->categories->pluck('name')->join(', ') }}</p>
                    </div>
                    <span class="rounded-full bg-white/5 px-3 py-1 text-sm font-semibold text-rose-300">
                        {{ \Illuminate\Support\Carbon::parse($item->start_time)->format('H:i') }}
                    </span>
                </li>
            @empty
                <li class="py-6 text-center text-sm text-slate-500">Bugün için planlanmış bir yayın bulunmuyor.</li>
            @endforelse
        </ul>
        <a href="{{ route('schedule.index') }}" class="mt-4 inline-block text-sm font-semibold text-rose-400 hover:text-rose-300">
            Tüm yayın akışını görüntüle &rarr;
        </a>
    </div>
</section>
