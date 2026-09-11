@props([
    'title' => '',
    'content' => '',
    'page' => null,
    'preview' => false,
    'fixedSettings' => null,
])

@php
    $siteSettings = \App\Models\SiteSetting::current();

    $mapEmbedUrl = $page?->google_maps_embed_url;
    $isMapEnabled = $page?->isMapEnabled() ?? false;
    $mapTitle = $page?->getMapTitle() ?? 'DOST TV Genel Merkezi';
    $mapHeight = $page?->getMapHeight() ?? 380;
    $showMap = $page && $page->slug === 'iletisim' && $isMapEnabled && !empty($mapEmbedUrl);
    $isContactPage = $page && $page->slug === 'iletisim';

    $pageSettings = $page?->settings ?? [];
    $phone = trim((string) ($pageSettings['phone'] ?? ''));
    $email = trim((string) ($pageSettings['email'] ?? ''));
    $address = trim((string) ($pageSettings['address'] ?? ''));

    // Remove duplicate Adres & Telefon HTML from content for public presentation layer
    $cleanContent = $content;
    if ($isContactPage && !empty($cleanContent)) {
        $cleanContent = preg_replace('/<h2>\s*(Adres|Telefon)\s*<\/h2>\s*<p>.*?<\/p>/is', '', $cleanContent);
        $cleanContent = trim($cleanContent);
    }

    if ($fixedSettings === null) {
        $activeLayout = \App\Models\HomepageLayout::query()->where('is_active', true)->first();
        $publishedSections = $activeLayout?->published_sections ?? [];
        $fixedSettings = $publishedSections['_fixed_settings'] ?? [];
    }

    $footerConfig = $fixedSettings['footer'] ?? [];
    $contactCardsGap = (int) ($footerConfig['contact_cards_gap'] ?? 24);
    $contactCardsToMapGap = (int) ($footerConfig['contact_cards_to_map_gap'] ?? 28);
    $mapTitleGap = (int) ($footerConfig['map_title_gap'] ?? 14);
    $mapHeight = (int) ($footerConfig['map_height'] ?? ($page?->getMapHeight() ?? 320));
    $mapRadius = (int) ($footerConfig['map_radius'] ?? 16);
    $mapFormGap = (int) ($footerConfig['map_form_gap'] ?? 56);
    $formFieldGap = (int) ($footerConfig['form_field_gap'] ?? 20);
    $inputHeight = (int) ($footerConfig['input_height'] ?? 48);
    $inputPaddingX = (int) ($footerConfig['input_padding_x'] ?? 16);
    $inputPaddingY = (int) ($footerConfig['input_padding_y'] ?? 12);
    $textareaHeight = (int) ($footerConfig['textarea_height'] ?? 140);
    $cardRadius = (int) ($footerConfig['card_radius'] ?? 16);
    $formRadius = (int) ($footerConfig['form_radius'] ?? 16);
    $headingSize = (int) ($footerConfig['heading_size'] ?? 24);
    $labelSize = (int) ($footerConfig['label_size'] ?? 12);
    $paddingTop = (int) ($footerConfig['section_padding_top'] ?? 48);
    $paddingBottom = (int) ($footerConfig['section_padding_bottom'] ?? 56);
    $paddingX = (int) ($footerConfig['section_padding_x'] ?? 32);

    $cardBorderMode = $footerConfig['card_border'] ?? 'light';
    $cardBorderStyle = match($cardBorderMode) {
        'none' => 'border-color: transparent !important;',
        'prominent' => 'border-color: rgba(51, 65, 85, 0.8) !important;',
        default => 'border-color: rgba(30, 41, 59, 0.4) !important;', // light
    };

    $cardSurfaceMode = $footerConfig['card_surface'] ?? 'soft';
    $cardSurfaceStyle = match($cardSurfaceMode) {
        'transparent' => 'background-color: transparent !important;',
        'prominent' => 'background-color: rgba(15, 23, 42, 0.85) !important;',
        default => 'background-color: rgba(15, 23, 42, 0.4) !important;', // soft
    };
@endphp

@if($isContactPage)
    {{-- Dedicated Premium Layout for İletişim Page --}}
    @php
        $contactDesign = \App\Support\PageDesignResolver::resolve($page);
        $contactWrapperStyle = $contactDesign['use_custom_design'] ? $contactDesign['wrapper_style'] : '';
        $contactMaxWidth = $contactDesign['use_custom_design'] ? $contactDesign['content_max_width'] : 1152;
        $contactPaddingTop = $contactDesign['use_custom_design'] ? $contactDesign['padding_top'] : $paddingTop;
        $contactPaddingBottom = $contactDesign['use_custom_design'] ? $contactDesign['padding_bottom'] : $paddingBottom;
    @endphp
    <div class="w-full" style="{{ $contactWrapperStyle }}">
        <div class="mx-auto space-y-0" style="max-width: {{ $contactMaxWidth }}px; padding-top: {{ $contactPaddingTop }}px; padding-bottom: {{ $contactPaddingBottom }}px; padding-left: {{ $paddingX }}px; padding-right: {{ $paddingX }}px;">
        
        {{-- Header Section --}}
        <div class="space-y-2 mb-8">
            <h1 class="font-black text-white tracking-tight" style="font-size: {{ max(24, $headingSize + 8) }}px;">İletişim</h1>
            <p class="text-xs sm:text-sm text-slate-400 leading-relaxed max-w-2xl">
                Bize aşağıdaki iletişim bilgilerinden ulaşabilir veya genel merkezimizi harita üzerinden inceleyebilirsiniz.
            </p>
        </div>

        {{-- 3 Equal Info Cards Grid (Desktop 3-col, Mobile 1-col) --}}
        <div class="grid grid-cols-1 sm:grid-cols-3" style="gap: {{ $contactCardsGap }}px;">
            
            {{-- 1. ADRES KARTI --}}
            @if(!empty($address))
                <div class="p-5 border transition duration-200 flex flex-col justify-between min-h-[145px] space-y-3" style="border-radius: {{ $cardRadius }}px; {{ $cardBorderStyle }} {{ $cardSurfaceStyle }}">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg bg-rose-500/10 text-rose-400 flex items-center justify-center shrink-0">
                            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">ADRES</span>
                    </div>
                    <div>
                        <p class="text-xs sm:text-sm text-slate-200 font-medium leading-relaxed line-clamp-3">
                            {{ $address }}
                        </p>
                    </div>
                </div>
            @endif

            {{-- 2. TELEFON KARTI --}}
            @if(!empty($phone))
                <div class="p-5 border transition duration-200 flex flex-col justify-between min-h-[145px] space-y-3" style="border-radius: {{ $cardRadius }}px; {{ $cardBorderStyle }} {{ $cardSurfaceStyle }}">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg bg-emerald-500/10 text-emerald-400 flex items-center justify-center shrink-0">
                            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                        </div>
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">TELEFON</span>
                    </div>
                    <div>
                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}" class="text-xs sm:text-sm text-slate-200 font-semibold hover:text-white transition block">
                            {{ $phone }}
                        </a>
                    </div>
                </div>
            @endif

            {{-- 3. E-POSTA KARTI --}}
            @if(!empty($email))
                <div class="p-5 border transition duration-200 flex flex-col justify-between min-h-[145px] space-y-3" style="border-radius: {{ $cardRadius }}px; {{ $cardBorderStyle }} {{ $cardSurfaceStyle }}">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg bg-sky-500/10 text-sky-400 flex items-center justify-center shrink-0">
                            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">E-POSTA</span>
                    </div>
                    <div>
                        <a href="mailto:{{ $email }}" class="text-xs sm:text-sm text-slate-200 font-semibold hover:text-white transition truncate block">
                            {{ $email }}
                        </a>
                    </div>
                </div>
            @endif

        </div>

        {{-- Additional Custom Content if provided (excluding stripped duplicates) --}}
        @if(!empty($cleanContent) && !str_contains($cleanContent, 'İçerik henüz girilmedi'))
            <div class="prose prose-invert max-w-none text-xs text-slate-400 pt-2">
                {!! $cleanContent !!}
            </div>
        @endif

        {{-- Google Maps Section --}}
        @if($showMap)
            <div class="space-y-0" style="margin-top: {{ $contactCardsToMapGap }}px;">
                @if(!empty($mapTitle))
                    <div class="flex items-center justify-between px-1" style="margin-bottom: {{ $mapTitleGap }}px;">
                        <div class="flex items-center gap-2 text-xs font-bold text-slate-300 uppercase tracking-wider">
                            <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                            <span>{{ $mapTitle }}</span>
                        </div>
                        <span class="text-[11px] text-slate-500 font-medium">Google Maps</span>
                    </div>
                @endif

                <div class="w-full overflow-hidden shadow-xl" style="height: {{ max($mapHeight, 200) }}px; border-radius: {{ $mapRadius }}px;">
                    <iframe
                        src="{{ $mapEmbedUrl }}"
                        width="100%"
                        height="{{ max($mapHeight, 200) }}"
                        style="border:0; height: {{ max($mapHeight, 200) }}px; border-radius: {{ $mapRadius }}px;"
                        allowfullscreen=""
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                        title="{{ $mapTitle }}"
                        class="w-full h-full"
                    ></iframe>
                </div>
            </div>
        @else
            <div class="p-8 text-center text-xs text-slate-500 space-y-2 border transition duration-200" style="border-radius: {{ $cardRadius }}px; margin-top: {{ $contactCardsToMapGap }}px; {{ $cardBorderStyle }} {{ $cardSurfaceStyle }}">
                <p class="font-medium text-slate-400">DOST TV Genel Merkezi</p>
                <p>Harita görünümü şu an aktif değil.</p>
            </div>
        @endif

        {{-- Contact Form Section ("BİZE ULAŞIN") --}}
        <div class="p-6 sm:p-8 space-y-6 border transition duration-200" style="border-radius: {{ $formRadius }}px; margin-top: {{ $mapFormGap }}px; {{ $cardBorderStyle }} {{ $cardSurfaceStyle }}">
            <div>
                <h2 class="font-black text-white tracking-tight" style="font-size: {{ $headingSize }}px;">BİZE ULAŞIN</h2>
                <p class="mt-1 text-xs sm:text-sm text-slate-400">
                    Sorularınız ve görüşleriniz için formu doldurabilirsiniz.
                </p>
            </div>

            @if(session('contact_success'))
                <div class="p-4 rounded-xl bg-emerald-950/80 border border-emerald-500/30 text-emerald-300 text-sm font-medium flex items-center gap-3">
                    <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>{{ session('contact_success') }}</span>
                </div>
            @endif

            @if($preview)
                <div class="flex flex-col" style="gap: {{ $formFieldGap }}px;">
            @else
                <form action="{{ route('contact.store') }}" method="POST" class="flex flex-col" style="gap: {{ $formFieldGap }}px;">
                    @csrf
            @endif

                {{-- Honeypot Spam Protection --}}
                <div style="display:none;" aria-hidden="true">
                    <input type="text" name="website" tabindex="-1" autocomplete="off">
                </div>

                {{-- 1. Ad Soyad (Tam Genişlik) --}}
                <div>
                    <label for="contact_name" class="block font-bold uppercase tracking-wider text-slate-300 mb-1.5" style="font-size: {{ $labelSize }}px;">
                        Ad Soyad <span class="text-rose-500">*</span>
                    </label>
                    <input
                        type="text"
                        id="contact_name"
                        name="name"
                        value="{{ old('name') }}"
                        required
                        placeholder="Adınız ve Soyadınız"
                        style="height: {{ $inputHeight }}px; padding-left: {{ $inputPaddingX }}px; padding-right: {{ $inputPaddingX }}px; padding-top: {{ $inputPaddingY }}px; padding-bottom: {{ $inputPaddingY }}px;"
                        class="w-full bg-slate-950/80 border @error('name') border-rose-500 @else border-slate-800/60 @enderror text-slate-100 text-sm rounded-xl focus:border-rose-500/80 focus:ring-1 focus:ring-rose-500/80 placeholder-slate-500 outline-none transition"
                    >
                    @error('name')
                        <p class="mt-1 text-xs text-rose-400 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                {{-- 2. E-posta + Telefon (Masaüstü Yan Yana, Mobil Tek Kolon) --}}
                <div class="grid grid-cols-1 sm:grid-cols-2" style="gap: {{ $formFieldGap }}px;">
                    <div>
                        <label for="contact_email" class="block font-bold uppercase tracking-wider text-slate-300 mb-1.5" style="font-size: {{ $labelSize }}px;">
                            E-Posta <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="email"
                            id="contact_email"
                            name="email"
                            value="{{ old('email') }}"
                            required
                            placeholder="ornek@domain.com"
                            style="height: {{ $inputHeight }}px; padding-left: {{ $inputPaddingX }}px; padding-right: {{ $inputPaddingX }}px; padding-top: {{ $inputPaddingY }}px; padding-bottom: {{ $inputPaddingY }}px;"
                            class="w-full bg-slate-950/80 border @error('email') border-rose-500 @else border-slate-800/60 @enderror text-slate-100 text-sm rounded-xl focus:border-rose-500/80 focus:ring-1 focus:ring-rose-500/80 placeholder-slate-500 outline-none transition"
                        >
                        @error('email')
                            <p class="mt-1 text-xs text-rose-400 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="contact_phone" class="block font-bold uppercase tracking-wider text-slate-300 mb-1.5" style="font-size: {{ $labelSize }}px;">
                            Telefon <span class="text-slate-500 font-normal text-[11px] uppercase ml-1">(İsteğe Bağlı)</span>
                        </label>
                        <input
                            type="tel"
                            id="contact_phone"
                            name="phone"
                            value="{{ old('phone') }}"
                            placeholder="05XX XXX XX XX"
                            style="height: {{ $inputHeight }}px; padding-left: {{ $inputPaddingX }}px; padding-right: {{ $inputPaddingX }}px; padding-top: {{ $inputPaddingY }}px; padding-bottom: {{ $inputPaddingY }}px;"
                            class="w-full bg-slate-950/80 border @error('phone') border-rose-500 @else border-slate-800/60 @enderror text-slate-100 text-sm rounded-xl focus:border-rose-500/80 focus:ring-1 focus:ring-rose-500/80 placeholder-slate-500 outline-none transition"
                        >
                        @error('phone')
                            <p class="mt-1 text-xs text-rose-400 font-medium">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- 3. Konu (Tam Genişlik - Normal Text Input) --}}
                <div>
                    <label for="contact_subject" class="block font-bold uppercase tracking-wider text-slate-300 mb-1.5" style="font-size: {{ $labelSize }}px;">
                        Konu <span class="text-rose-500">*</span>
                    </label>
                    <input
                        type="text"
                        id="contact_subject"
                        name="subject"
                        value="{{ old('subject') }}"
                        required
                        placeholder="Mesajınızın konusu"
                        style="height: {{ $inputHeight }}px; padding-left: {{ $inputPaddingX }}px; padding-right: {{ $inputPaddingX }}px; padding-top: {{ $inputPaddingY }}px; padding-bottom: {{ $inputPaddingY }}px;"
                        class="w-full bg-slate-950/80 border @error('subject') border-rose-500 @else border-slate-800/60 @enderror text-slate-100 text-sm rounded-xl focus:border-rose-500/80 focus:ring-1 focus:ring-rose-500/80 placeholder-slate-500 outline-none transition"
                    >
                    @error('subject')
                        <p class="mt-1 text-xs text-rose-400 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                {{-- 4. Mesajınız (Tam Genişlik Textarea) --}}
                <div>
                    <label for="contact_message" class="block font-bold uppercase tracking-wider text-slate-300 mb-1.5" style="font-size: {{ $labelSize }}px;">
                        Mesajınız <span class="text-rose-500">*</span>
                    </label>
                    <textarea
                        id="contact_message"
                        name="message"
                        rows="5"
                        required
                        placeholder="Bize iletmek istediğiniz mesajınızı yazınız..."
                        style="height: {{ $textareaHeight }}px; padding-left: {{ $inputPaddingX }}px; padding-right: {{ $inputPaddingX }}px; padding-top: {{ $inputPaddingY }}px; padding-bottom: {{ $inputPaddingY }}px;"
                        class="w-full bg-slate-950/80 border @error('message') border-rose-500 @else border-slate-800/60 @enderror text-slate-100 text-sm rounded-xl focus:border-rose-500/80 focus:ring-1 focus:ring-rose-500/80 placeholder-slate-500 outline-none transition resize-y"
                    >{{ old('message') }}</textarea>
                    @error('message')
                        <p class="mt-1 text-xs text-rose-400 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                {{-- 5. Gönder Butonu --}}
                <div class="flex justify-end pt-2">
                    <button
                        type="{{ $preview ? 'button' : 'submit' }}"
                        class="w-full sm:w-auto bg-rose-600 hover:bg-rose-500 text-white font-bold py-3 px-8 rounded-xl transition duration-150 shadow-lg shadow-rose-600/20 cursor-pointer text-sm flex items-center justify-center gap-2"
                    >
                        <span>Mesajı Gönder</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </button>
                </div>
            @if($preview)
                </div>
            @else
                </form>
            @endif
        </div>
    </div>
</div>
@else
    {{-- Standard Layout for Other Corporate Pages --}}
    @php
        $resolvedDesign = \App\Support\PageDesignResolver::resolve($page);
        $wrapperStyle = $resolvedDesign['wrapper_style'] ?? '';
        $surfaceStyle = $resolvedDesign['surface_style'] ?? '';
        $useCustom = $resolvedDesign['use_custom_design'] ?? false;
        $textColorStyle = $useCustom ? "color: {$resolvedDesign['text_color']};" : '';
        $headingColorStyle = $useCustom ? "color: {$resolvedDesign['text_color']};" : 'color: #ffffff;';
    @endphp
    <div class="w-full" style="padding-top: {{ $resolvedDesign['padding_top'] }}px; padding-bottom: {{ $resolvedDesign['padding_bottom'] }}px; {{ $wrapperStyle }}">
        <div class="mx-auto px-4 py-8 border border-white/10 shadow-xl space-y-6" style="{{ $surfaceStyle }} {{ $textColorStyle }}">
            <div>
                <h1 class="text-3xl font-black tracking-tight" style="{{ $headingColorStyle }}">{{ $title ?: 'Sayfa Başlığı' }}</h1>
                <div class="prose prose-invert mt-6 max-w-none text-slate-300" style="{{ $textColorStyle }}">
                    {!! $content ?: '<p class="text-slate-500 italic">İçerik henüz girilmedi.</p>' !!}
                </div>
            </div>
        </div>
    </div>
@endif
