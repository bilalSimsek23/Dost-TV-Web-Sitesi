@php
    $siteSettings = $siteSettings ?? \App\Models\SiteSetting::current();
    $rawTitle = trim(View::yieldContent('title'));
    $titleSuffix = $siteSettings->title_suffix ?? '| DOST TV';

    if (!empty($rawTitle)) {
        if (preg_match('/^(.*?)\s*[-|]\s*Dost TV$/ui', $rawTitle, $matches)) {
            $cleanTitle = trim($matches[1]);
            $pageTitle = $cleanTitle . (filled($titleSuffix) ? ' ' . trim($titleSuffix) : '');
        } elseif (filled($titleSuffix) && !str_ends_with($rawTitle, trim($titleSuffix))) {
            $pageTitle = $rawTitle . ' ' . trim($titleSuffix);
        } else {
            $pageTitle = $rawTitle;
        }
    } else {
        $pageTitle = ($siteSettings->site_name ?? 'Dost TV') . (filled($titleSuffix) ? ' ' . trim($titleSuffix) : '');
    }

    $metaDescription = trim(View::yieldContent('description'));
    if (empty($metaDescription)) {
        $metaDescription = $siteSettings->default_meta_description ?: 'Dost TV - Uydu üzerinden yayın yapan Türkçe TV kanalı. Canlı TV, canlı radyo, program arşivi ve yayın akışı.';
    }

    $ogImage = trim(View::yieldContent('og_image'));
    if (empty($ogImage)) {
        if (!empty($siteSettings->default_og_image)) {
            $ogImage = asset('storage/' . $siteSettings->default_og_image);
        } elseif (!empty($siteSettings->logo)) {
            $ogImage = asset('storage/' . $siteSettings->logo);
        } else {
            $ogImage = null;
        }
    }

    $isIndexingAllowed = $siteSettings->search_engine_indexing ?? true;
    $robotsDirective = $isIndexingAllowed ? 'index, follow' : 'noindex, nofollow';
@endphp
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $metaDescription }}">
    <meta name="robots" content="{{ $robotsDirective }}">
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- OpenGraph Meta Tags --}}
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    @if(!empty($ogImage))
        <meta property="og:image" content="{{ $ogImage }}">
    @endif

    {{-- Favicon --}}
    @if(!empty($siteSettings->favicon))
        <link rel="icon" href="{{ asset('storage/' . $siteSettings->favicon) }}">
    @endif

    {{-- Google Site Verification --}}
    @if(!empty($siteSettings->google_site_verification))
        @if(str_starts_with(trim($siteSettings->google_site_verification), '<meta'))
            {!! $siteSettings->google_site_verification !!}
        @else
            <meta name="google-site-verification" content="{{ $siteSettings->google_site_verification }}">
        @endif
    @endif

    {{-- Google Tag Manager --}}
    @if(!empty($siteSettings->google_tag_manager_id))
        <!-- Google Tag Manager -->
        <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
        new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
        j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
        'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
        })(window,document,'script','dataLayer','{{ $siteSettings->google_tag_manager_id }}');</script>
        <!-- End Google Tag Manager -->
    @endif

    {{-- Google Analytics (gtag.js) --}}
    @if(!empty($siteSettings->google_analytics_id))
        <!-- Google Analytics (gtag.js) -->
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $siteSettings->google_analytics_id }}"></script>
        <script>
          window.dataLayer = window.dataLayer || [];
          function gtag(){dataLayer.push(arguments);}
          gtag('js', new Date());
          gtag('config', '{{ $siteSettings->google_analytics_id }}');
        </script>
        <!-- End Google Analytics -->
    @endif

    {{-- Custom Head Code --}}
    @if(!empty($siteSettings->custom_head_code))
        {!! $siteSettings->custom_head_code !!}
    @endif

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 antialiased">
    {{-- Google Tag Manager (noscript) --}}
    @if(!empty($siteSettings->google_tag_manager_id))
        <!-- Google Tag Manager (noscript) -->
        <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $siteSettings->google_tag_manager_id }}"
        height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
        <!-- End Google Tag Manager (noscript) -->
    @endif

    <div class="pointer-events-none fixed inset-0 -z-10 bg-[radial-gradient(ellipse_80%_50%_at_50%_-20%,rgba(244,63,94,0.18),rgba(2,6,23,0))]"></div>

    <x-site.header />

    <main>
        @yield('content')
    </main>

    <x-site.footer />

    <x-site.announcement-popup />

    {{-- Custom Body Code --}}
    @if(!empty($siteSettings->custom_body_code))
        {!! $siteSettings->custom_body_code !!}
    @endif
</body>
</html>
