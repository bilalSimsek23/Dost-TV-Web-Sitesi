@props([
    'settings' => null,
    'todaySchedule' => collect(),
])

@php
    $settings = $settings ?? \App\Models\SiteSetting::current();
@endphp

<section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-24">
    <div class="grid items-center gap-12 lg:grid-cols-2">
        <div>
            <span class="inline-flex items-center gap-2 rounded-full bg-rose-500/10 px-3 py-1 text-xs font-semibold text-rose-400 ring-1 ring-inset ring-rose-500/20">
                Uydu üzerinden 7/24 yayın
            </span>
            <h1 class="mt-6 text-4xl font-black leading-tight text-white sm:text-5xl lg:text-6xl">
                {{ $settings->site_name }}
                <span class="block bg-gradient-to-r from-rose-400 to-amber-300 bg-clip-text text-transparent">her an yanınızda</span>
            </h1>
            <p class="mt-6 max-w-xl text-lg text-slate-400">
                Diziler, haberler ve belgesellerle dolu yayın akışımızı takip edin; canlı TV ve canlı radyomuzu
                dilediğiniz an, dilediğiniz yerden izleyin, dinleyin.
            </p>
            <div class="mt-8 flex flex-wrap gap-4">
                <a href="{{ route('live.tv') }}"
                   class="inline-flex items-center gap-2 rounded-full bg-rose-600 px-6 py-3 font-semibold text-white shadow-lg shadow-rose-600/30 transition hover:bg-rose-500">
                    Canlı TV İzle
                </a>
                <a href="{{ route('live.radio') }}"
                   class="inline-flex items-center gap-2 rounded-full bg-white/5 px-6 py-3 font-semibold text-white ring-1 ring-inset ring-white/10 transition hover:bg-white/10">
                    Canlı Radyo Dinle
                </a>
            </div>
        </div>

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
    </div>
</section>
