@props([
    'block' => [],
    'items' => collect(),
])

@php
    $shelfType = $block['shelf_type'] ?? 'program';
    $title = $block['title'] ?? ($shelfType === 'program' ? 'Program Rafı' : 'Video Rafı');
    $showTitle = $block['show_title'] ?? true;
    $showAllLink = $block['show_all_link'] ?? true;
    $titleAlignment = $block['title_alignment'] ?? 'left';
    $subtitle = $block['subtitle'] ?? null;
    $desktopColumns = \App\Services\Home\HomepageBlockRegistry::resolveDesktopColumns($block);
    $rowCount = (int) ($block['row_count'] ?? 1);
    $displayVariant = $block['display_variant'] ?? 'horizontal_carousel';
    $showArrows = ! empty($block['show_arrows'] ?? true);
    $gapSize = $block['gap_size'] ?? 'md';
    $bgStyle = $block['bg_style'] ?? 'dark';

    $responsive = \App\Services\Home\HomepageBlockRegistry::resolveResponsiveSettings($block);

    $bgClass = match ($bgStyle) {
        'transparent' => 'bg-transparent',
        'light' => 'bg-gradient-to-b from-slate-900/40 via-slate-950 to-slate-950',
        default => 'bg-slate-950',
    };

    $categoryId = $block['category_id'] ?? null;
    $category = $categoryId ? \App\Models\Category::find($categoryId) : null;
    
    $ctaUrl = null;
    if ($showAllLink) {
        if ($category) {
            $ctaUrl = route('programs.index', ['kategori' => $category->slug]);
        } elseif ($shelfType === 'program') {
            $ctaUrl = route('programs.index');
        }
    }

    $layout = \App\Services\Home\HomepageBlockRegistry::resolveSectionLayout($block);
    $colors = \App\Services\Home\HomepageBlockRegistry::resolveSectionColors($block);

    $cardComponent = $shelfType === 'video' ? 'site.video-card' : 'site.program-card';
    $sectionId = 'content-shelf-' . ($block['uuid'] ?? \Illuminate\Support\Str::random(6));

    $m = $responsive['mobile'];
    $t = $responsive['tablet'];
    $d = $responsive['desktop'];
@endphp

@if ($items->isNotEmpty())
    <style>
        #{{ $sectionId }} .dost-shelf-inner {
            padding-top: {{ $m['section_padding_top'] }}px;
            padding-bottom: {{ $m['section_padding_bottom'] }}px;
            padding-left: {{ $m['section_padding_x'] }}px;
            padding-right: {{ $m['section_padding_x'] }}px;
        }
        #{{ $sectionId }} .dost-heading {
            font-size: {{ $m['heading_size'] }}px;
        }
        #{{ $sectionId }} .dost-heading-wrapper {
            margin-bottom: {{ $m['heading_margin_bottom'] }}px;
        }
        #{{ $sectionId }} .dost-subtitle {
            font-size: {{ $m['subtitle_size'] }}px;
        }
        #{{ $sectionId }} .dost-cta-link {
            font-size: {{ $m['cta_size'] }}px;
            margin-top: {{ $m['cta_margin_top'] }}px;
        }
        @media (min-width: 640px) {
            #{{ $sectionId }} .dost-shelf-inner {
                padding-top: {{ $t['section_padding_top'] }}px;
                padding-bottom: {{ $t['section_padding_bottom'] }}px;
                padding-left: {{ $t['section_padding_x'] }}px;
                padding-right: {{ $t['section_padding_x'] }}px;
            }
            #{{ $sectionId }} .dost-heading {
                font-size: {{ $t['heading_size'] }}px;
            }
            #{{ $sectionId }} .dost-heading-wrapper {
                margin-bottom: {{ $t['heading_margin_bottom'] }}px;
            }
            #{{ $sectionId }} .dost-subtitle {
                font-size: {{ $t['subtitle_size'] }}px;
            }
            #{{ $sectionId }} .dost-cta-link {
                font-size: {{ $t['cta_size'] }}px;
                margin-top: {{ $t['cta_margin_top'] }}px;
            }
        }
        @media (min-width: 1024px) {
            #{{ $sectionId }} .dost-shelf-inner {
                padding-top: {{ $d['section_padding_top'] }}px;
                padding-bottom: {{ $d['section_padding_bottom'] }}px;
                padding-left: {{ $d['section_padding_x'] }}px;
                padding-right: {{ $d['section_padding_x'] }}px;
            }
            #{{ $sectionId }} .dost-heading {
                font-size: {{ $d['heading_size'] }}px;
            }
            #{{ $sectionId }} .dost-heading-wrapper {
                margin-bottom: {{ $d['heading_margin_bottom'] }}px;
            }
            #{{ $sectionId }} .dost-subtitle {
                font-size: {{ $d['subtitle_size'] }}px;
            }
            #{{ $sectionId }} .dost-cta-link {
                font-size: {{ $d['cta_size'] }}px;
                margin-top: {{ $d['cta_margin_top'] }}px;
            }
        }
    </style>

    <section id="{{ $sectionId }}" class="{{ $layout['shell_class'] }}" style="{{ $layout['shell_style'] }} {{ $colors['shell_style'] }}">
        <div class="dost-shelf-inner {{ $layout['inner_class'] }}" style="{{ $layout['inner_style'] }}">
            @if ($showTitle && filled($title))
                <div class="dost-heading-wrapper flex flex-col sm:flex-row sm:items-end justify-between gap-3 pb-3.5 {{ $titleAlignment === 'center' ? 'text-center sm:text-center' : '' }}">
                    <div class="{{ $titleAlignment === 'center' ? 'mx-auto' : '' }}">
                        <div class="flex items-center gap-2.5 {{ $titleAlignment === 'center' ? 'justify-center' : '' }}">
                            <span class="inline-block h-4 w-1 rounded-full" style="background-color: {{ $colors['accent'] }};"></span>
                            <h2 class="dost-heading font-extrabold text-white tracking-tight">
                                {{ $title }}
                            </h2>
                        </div>
                        @if ($subtitle)
                            <p class="dost-subtitle text-slate-400 mt-1 font-normal">{{ $subtitle }}</p>
                        @endif
                    </div>

                    @if ($ctaUrl)
                        <a href="{{ $ctaUrl }}" class="dost-cta-link inline-flex items-center font-semibold transition-colors group self-start sm:self-auto min-h-[36px]" style="color: {{ $colors['accent'] }};">
                            <span>Tümünü Gör</span>
                            <svg class="ml-1.5 h-4 w-4 transform group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    @endif
                </div>
            @endif

            <x-site.blocks.shelf-grid
                :items="$items"
                :block="$block"
                :display-variant="$displayVariant"
                :desktop-columns="$desktopColumns"
                :row-count="$rowCount"
                :show-arrows="$showArrows"
                :gap-size="$gapSize"
                :id="$block['uuid'] ?? null"
                :card-component="$cardComponent"
            />
        </div>
    </section>
@endif
