<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', $siteSettings->site_name ?? 'Dost TV')</title>
    <meta name="description" content="@yield('description', 'Dost TV - Uydu üzerinden yayın yapan Türkçe TV kanalı. Canlı TV, canlı radyo, program arşivi ve yayın akışı.')">
    @if(!empty($siteSettings->favicon))
        <link rel="icon" href="{{ asset('storage/' . $siteSettings->favicon) }}">
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 antialiased">
    <div class="pointer-events-none fixed inset-0 -z-10 bg-[radial-gradient(ellipse_80%_50%_at_50%_-20%,rgba(244,63,94,0.18),rgba(2,6,23,0))]"></div>

    <x-site.header />

    <main>
        @yield('content')
    </main>

    <x-site.footer />

    <x-site.announcement-popup />
</body>
</html>
