@props([
    'preview' => false,
    'siteSettings' => null,
    'siteName' => null,
    'logo' => null,
    'logoAltText' => null,
    'liveButtonVisible' => null,
    'liveButtonText' => null,
    'headerSticky' => null,
    'searchVisible' => null,
])

@php
    $siteSettings = $siteSettings ?? \App\Models\SiteSetting::current();

    $siteName = $siteName ?? ($siteSettings->site_name ?? 'Dost TV');
    $logo = $logo ?? ($siteSettings->logo ?? null);
    $logoAltText = $logoAltText ?? ($siteSettings->logo_alt_text ?: $siteName);
    $liveButtonVisible = $liveButtonVisible ?? (($siteSettings->live_button_is_visible ?? true) && ($siteSettings->live_tv_is_public ?? true));
    $liveButtonText = $liveButtonText ?? ($siteSettings->live_button_text && $siteSettings->live_button_text !== 'Canlı İzle' ? $siteSettings->live_button_text : 'Canlı');
    $headerSticky = $headerSticky ?? ($siteSettings->header_is_sticky ?? true);
    $searchVisible = $searchVisible ?? ($siteSettings->search_is_visible ?? true);

    $logoUrl = null;
    if (!empty($logo)) {
        $rawLogo = is_array($logo) ? reset($logo) : $logo;
        if (is_object($rawLogo) && method_exists($rawLogo, 'temporaryUrl')) {
            try {
                $logoUrl = $rawLogo->temporaryUrl();
            } catch (\Throwable $e) {
                $logoUrl = null;
            }
        } elseif (is_string($rawLogo) && !empty(trim($rawLogo))) {
            $logoUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($rawLogo);
        }
    }
@endphp

<header class="{{ ($headerSticky && !$preview) ? 'sticky top-0' : 'relative' }} z-40 border-b border-white/5 bg-slate-950/80 backdrop-blur rounded-xl">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
        {{-- Logo Section --}}
        <a href="{{ $preview ? 'javascript:void(0)' : route('home') }}" class="flex shrink-0 items-center gap-2">
            @if(!empty($logoUrl))
                <img src="{{ $logoUrl }}" alt="{{ $logoAltText }}" class="h-9 w-auto object-contain">
            @else
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-gradient-to-br from-rose-500 to-amber-400 font-black text-slate-950">D</span>
                <span class="text-lg font-bold tracking-tight text-white">Dost<span class="text-rose-500">TV</span></span>
            @endif
        </a>

        {{-- Navigation Menu Section --}}
        <x-site.menu location="header_primary" />

        {{-- Right Section: Search & Live Dropdown CTA --}}
        <div class="flex items-center gap-3">
            @if($searchVisible)
                <button type="button" class="p-1.5 text-slate-400 hover:text-white transition" aria-label="Arama">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                    </svg>
                </button>
            @endif

            @if($liveButtonVisible)
                <a href="{{ $preview ? 'javascript:void(0)' : route('live.tv') }}"
                   class="group inline-flex shrink-0 items-center gap-2 rounded-full bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-rose-600/30 transition hover:bg-rose-500">
                    <span class="relative flex h-2 w-2">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-white opacity-75"></span>
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-white"></span>
                    </span>
                    <span>{{ $liveButtonText }}</span>
                </a>
            @endif
        </div>
    </div>
</header>
