<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dost TV')</title>
    <meta name="description" content="@yield('description', 'Dost TV - Uydu üzerinden yayın yapan Türkçe TV kanalı. Canlı TV, canlı radyo, program arşivi ve yayın akışı.')">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 antialiased">
    <div class="pointer-events-none fixed inset-0 -z-10 bg-[radial-gradient(ellipse_80%_50%_at_50%_-20%,rgba(244,63,94,0.18),rgba(2,6,23,0))]"></div>

    @php
        $kurumsalPages = $menuPages->whereIn('slug', ['yayinci-kunye-bilgisi', 'dost-tv-yayin-ilkeleri', 'neden-dost-tv']);
        $vakifPage = $menuPages->firstWhere('slug', 'dost-vakfi-hesap-numaralari');
        $iletisimPage = $menuPages->firstWhere('slug', 'iletisim');
    @endphp

    <header class="sticky top-0 z-40 border-b border-white/5 bg-slate-950/80 backdrop-blur">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2">
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-gradient-to-br from-rose-500 to-amber-400 font-black text-slate-950">D</span>
                <span class="text-lg font-bold tracking-tight text-white">Dost<span class="text-rose-500">TV</span></span>
            </a>

            <x-site.menu location="header_primary" />

            <a href="{{ route('live.tv') }}"
               class="group inline-flex shrink-0 items-center gap-2 rounded-full bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-rose-600/30 transition hover:bg-rose-500">
                <span class="relative flex h-2 w-2">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-white opacity-75"></span>
                    <span class="relative inline-flex h-2 w-2 rounded-full bg-white"></span>
                </span>
                Canlı İzle
            </a>
        </div>
    </header>

    <main>
        @yield('content')
    </main>

    <footer class="mt-24 border-t border-white/5 bg-slate-950/60">
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <div class="flex flex-col items-center justify-between gap-4 md:flex-row">
                <div class="flex items-center gap-2">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-rose-500 to-amber-400 font-black text-slate-950">D</span>
                    <span class="font-bold text-white">Dost<span class="text-rose-500">TV</span></span>
                </div>
                <p class="text-sm text-slate-400">&copy; {{ now()->year }} Dost TV. Tüm hakları saklıdır.</p>
                <div class="flex flex-wrap justify-center gap-4 text-sm text-slate-400">
                    <a href="{{ route('live.tv') }}" class="hover:text-white">Canlı TV</a>
                    <a href="{{ route('live.radio') }}" class="hover:text-white">Canlı Radyo</a>
                    <a href="{{ route('schedule.index') }}" class="hover:text-white">Yayın Akışı</a>
                    @foreach ($menuPages as $page)
                        <a href="{{ route('pages.show', $page) }}" class="hover:text-white">{{ $page->title }}</a>
                    @endforeach
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
