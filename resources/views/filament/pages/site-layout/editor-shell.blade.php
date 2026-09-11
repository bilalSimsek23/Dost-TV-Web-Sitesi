<div>
    @php
        $blockTypes = \App\Services\Home\HomepageBlockRegistry::getBlockTypesForPageType($record->page_type ?? 'home');
        $displayVariants = \App\Services\Home\HomepageBlockRegistry::getDisplayVariants();
        $desktopColumns = \App\Services\Home\HomepageBlockRegistry::getDesktopColumns();
        $paddingYOptions = \App\Services\Home\HomepageBlockRegistry::getPaddingYOptions();
        $gapOptions = \App\Services\Home\HomepageBlockRegistry::getGapOptions();
        $cardRatioOptions = \App\Services\Home\HomepageBlockRegistry::getCardRatioOptions();
        $cardRadiusOptions = \App\Services\Home\HomepageBlockRegistry::getCardRadiusOptions();
        $sectionWidthOptions = \App\Services\Home\HomepageBlockRegistry::getSectionWidthOptions();
        $titleSizeOptions = \App\Services\Home\HomepageBlockRegistry::getTitleSizeOptions();
        $videoSourceModes = \App\Services\Home\HomepageBlockRegistry::getVideoSourceModes();
        $programSourceModes = \App\Services\Home\HomepageBlockRegistry::getProgramSourceModes();
        $simpleDisplayVariants = \App\Services\Home\HomepageBlockRegistry::getSimpleDisplayVariants();
        $rowCountOptions = \App\Services\Home\HomepageBlockRegistry::getRowCountOptions();
        $contentLimitOptions = \App\Services\Home\HomepageBlockRegistry::getContentLimitOptions();
    @endphp

    {{-- Scoped Explicit CSS for DOST TV Elementor Editor Shell --}}
    <style>
        .fi-main, .fi-main-content, .fi-page, .fi-body {
            transform: none !important;
            filter: none !important;
            perspective: none !important;
            contain: none !important;
            padding: 0 !important;
            margin: 0 !important;
            max-width: 100% !important;
            width: 100% !important;
            height: 100% !important;
        }

        #dost-homepage-editor {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
            height: 100dvh !important;
            z-index: 999999 !important;
            display: flex !important;
            flex-direction: column !important;
            overflow: hidden !important;
            background: #0b0d12 !important;
            color: #f8fafc !important;
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
            font-size: 13px !important;
            box-sizing: border-box !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        #dost-homepage-editor * {
            box-sizing: border-box !important;
        }

        #dost-homepage-editor svg {
            width: 16px !important;
            height: 16px !important;
            max-width: 16px !important;
            max-height: 16px !important;
            flex: 0 0 16px !important;
            display: inline-block !important;
        }

        .dost-editor-toolbar {
            height: 56px !important;
            min-height: 56px !important;
            max-height: 56px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            padding: 0 16px !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
            background: #11141b !important;
            z-index: 20 !important;
            flex-shrink: 0 !important;
        }

        .dost-editor-toolbar-left,
        .dost-editor-toolbar-right {
            display: flex !important;
            align-items: center !important;
            gap: 10px !important;
        }

        .dost-editor-body {
            flex: 1 1 auto !important;
            min-height: 0 !important;
            display: flex !important;
            flex-direction: row !important;
            flex-wrap: nowrap !important;
            overflow: hidden !important;
            width: 100% !important;
            height: calc(100vh - 56px) !important;
            height: calc(100dvh - 56px) !important;
        }

        .dost-editor-sidebar {
            width: 340px !important;
            min-width: 340px !important;
            max-width: 340px !important;
            height: 100% !important;
            overflow-y: auto !important;
            overflow-x: hidden !important;
            background: #151820 !important;
            border-right: 1px solid rgba(255, 255, 255, 0.1) !important;
            padding: 16px !important;
            display: flex !important;
            flex-direction: column !important;
            gap: 14px !important;
            flex-shrink: 0 !important;
        }

        .dost-editor-canvas {
            flex: 1 1 auto !important;
            min-width: 0 !important;
            min-height: 0 !important;
            width: 100% !important;
            height: 100% !important;
            overflow: hidden !important;
            background: #090b10 !important;
            position: relative !important;
        }

        .dost-editor-preview-frame {
            display: block !important;
            width: 100% !important;
            height: 100% !important;
            border: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
            background: #090b10 !important;
        }

        /* Elementor Control Widgets Styling */
        .dost-btn {
            display: inline-flex !important;
            align-items: center !important;
            gap: 6px !important;
            padding: 6px 12px !important;
            border-radius: 6px !important;
            font-size: 12px !important;
            font-weight: 600 !important;
            cursor: pointer !important;
            border: 1px solid transparent !important;
            text-decoration: none !important;
            line-height: 1.2 !important;
            transition: all 0.15s ease !important;
            white-space: nowrap !important;
        }

        .dost-btn-slate {
            background: #1e2430 !important;
            color: #cbd5e1 !important;
            border-color: rgba(255, 255, 255, 0.1) !important;
        }
        .dost-btn-slate:hover {
            background: #283040 !important;
            color: #ffffff !important;
        }

        .dost-btn-amber {
            background: #d97706 !important;
            color: #ffffff !important;
        }
        .dost-btn-amber:hover {
            background: #b45309 !important;
        }

        .dost-btn-rose {
            background: #e11d48 !important;
            color: #ffffff !important;
        }
        .dost-btn-rose:hover {
            background: #be123c !important;
        }

        .dost-badge {
            display: inline-flex !important;
            align-items: center !important;
            gap: 4px !important;
            padding: 2px 8px !important;
            border-radius: 9999px !important;
            font-size: 10px !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
        }
        .dost-badge-emerald {
            background: rgba(16, 185, 129, 0.15) !important;
            color: #34d399 !important;
            border: 1px solid rgba(16, 185, 129, 0.3) !important;
        }
        .dost-badge-slate {
            background: rgba(100, 116, 139, 0.2) !important;
            color: #94a3b8 !important;
            border: 1px solid rgba(100, 116, 139, 0.3) !important;
        }

        .dost-select, .dost-input {
            width: 100% !important;
            background: #0f1219 !important;
            color: #f8fafc !important;
            border: 1px solid rgba(255, 255, 255, 0.15) !important;
            border-radius: 6px !important;
            padding: 6px 10px !important;
            font-size: 12px !important;
            outline: none !important;
        }
        .dost-select:focus, .dost-input:focus {
            border-color: #e11d48 !important;
        }

        .dost-card {
            background: rgba(15, 18, 25, 0.8) !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            border-radius: 8px !important;
            padding: 12px !important;
            display: flex !important;
            flex-direction: column !important;
            gap: 12px !important;
        }

        .dost-accordion-header {
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            padding: 10px 12px !important;
            background: rgba(255, 255, 255, 0.04) !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            border-radius: 6px !important;
            cursor: pointer !important;
            user-select: none !important;
            transition: all 0.15s ease !important;
        }
        .dost-accordion-header:hover {
            background: rgba(255, 255, 255, 0.08) !important;
            border-color: rgba(244, 63, 94, 0.4) !important;
        }

        .dost-section-item {
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            background: rgba(15, 18, 25, 0.9) !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            border-radius: 6px !important;
            padding: 8px 10px !important;
            gap: 8px !important;
        }
        .dost-section-item:hover {
            border-color: rgba(225, 29, 72, 0.5) !important;
            background: #1a1e29 !important;
        }

        .dost-icon-btn {
            background: transparent !important;
            border: 0 !important;
            color: #94a3b8 !important;
            cursor: pointer !important;
            padding: 4px !important;
            border-radius: 4px !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
        }
        .dost-icon-btn:hover {
            color: #ffffff !important;
            background: rgba(255, 255, 255, 0.1) !important;
        }
    </style>

    {{-- Fullscreen 100vh Root Shell Element --}}
    <div id="dost-homepage-editor"
         x-data="{
             activeDevice: 'desktop',
             refreshPreview() {
                 const iframe = document.getElementById('editor-preview-frame');
                 if (iframe) {
                     try {
                         const url = new URL(iframe.src);
                         url.searchParams.set('_t', Date.now());
                         iframe.src = url.toString();
                     } catch (e) {
                         if (iframe.contentWindow) {
                             iframe.contentWindow.location.reload(true);
                         }
                     }
                 }
             }
         }"
         x-on:preview-updated.window="refreshPreview()">

        {{-- 56px Header Toolbar --}}
        <header class="dost-editor-toolbar">
            <div class="dost-editor-toolbar-left">
                <a href="/admin/site-layout/homepage-layout-resource/homepage-layouts"
                   class="dost-btn dost-btn-slate"
                   title="Yönetim Paneline Dön">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                    <span>&larr; Yönetim Paneline Dön</span>
                </a>

                <div style="display: flex; align-items: center; gap: 8px; margin-left: 8px;">
                    <strong style="font-size: 14px; font-weight: 700; color: #ffffff;">{{ $record->name }}</strong>

                    @if (($record->page_type ?? 'home') === 'program_detail')
                        <span class="dost-badge" style="background: rgba(14, 165, 233, 0.2); color: #38bdf8; border: 1px solid rgba(14, 165, 233, 0.4); padding: 3px 10px; font-size: 11px;">
                            Program Detay
                        </span>
                    @else
                        <span class="dost-badge" style="background: rgba(225, 29, 72, 0.2); color: #fb7185; border: 1px solid rgba(225, 29, 72, 0.4); padding: 3px 10px; font-size: 11px;">
                            Ana Sayfa
                        </span>
                    @endif

                    @if ($record->is_active)
                        <span class="dost-badge dost-badge-emerald">
                            {{ ($record->page_type ?? 'home') === 'program_detail' ? 'CANLI PROGRAM DETAY' : 'CANLI ANA SAYFA' }}
                        </span>
                    @else
                        <span class="dost-badge dost-badge-slate">TASLAK</span>
                    @endif

                    @if ($this->hasUnpublishedChanges)
                        <span class="dost-badge" style="background: rgba(217, 119, 6, 0.2); color: #fbbf24; border: 1px solid rgba(217, 119, 6, 0.4); padding: 3px 10px; font-size: 11px;">
                            ● Yayınlanmamış değişiklikler var
                        </span>
                    @else
                        <span class="dost-badge" style="background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); padding: 3px 10px; font-size: 11px;">
                            ✓ Site güncel
                        </span>
                    @endif
                </div>

                @if (($record->page_type ?? 'home') === 'program_detail')
                    <div style="display: flex; align-items: center; gap: 6px; margin-left: 12px; background: rgba(15, 23, 42, 0.8); border: 1px solid rgba(255, 255, 255, 0.12); padding: 4px 10px; border-radius: 8px;">
                        <span style="font-size: 11px; color: #94a3b8; font-weight: 600;">Önizleme Programı:</span>
                        <select wire:model.live="previewProgramId" class="dost-select" style="width: auto; padding: 3px 8px; font-size: 12px;">
                            @foreach (\App\Models\Program::query()->where('is_active', true)->where('show_on_public', true)->orderBy('name')->get() as $progItem)
                                <option value="{{ $progItem->id }}">{{ $progItem->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <select onchange="window.location.href = '/admin/site-layout/homepage-layout-resource/homepage-layouts/' + this.value + '/edit'"
                        class="dost-select"
                        style="width: auto; margin-left: 8px;">
                    @foreach (\App\Models\HomepageLayout::query()->where('page_type', $record->page_type ?? 'home')->orderBy('name')->get() as $layoutItem)
                        <option value="{{ $layoutItem->id }}" @selected($layoutItem->id === $record->id)>
                            {{ $layoutItem->name }} {{ $layoutItem->is_active ? '(CANLI)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="dost-editor-toolbar-right">
                <a href="{{ route('admin.site-layout.preview-frame', $record) }}"
                   target="_blank"
                   class="dost-btn dost-btn-slate">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                    </svg>
                    <span>Yeni Sekmede Önizle</span>
                </a>

                <button type="button"
                        wire:click="saveDraft(true)"
                        class="dost-btn dost-btn-slate">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                    </svg>
                    <span>Taslağı Kaydet</span>
                </button>

                <button type="button"
                        wire:click="publish(false)"
                        wire:confirm="Taslak değişikliklerini yayınlamak istediğinize emin misiniz?"
                        class="dost-btn dost-btn-amber">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                    <span>Yayınla</span>
                </button>

                <button type="button"
                        wire:click="publish(true)"
                        wire:confirm="Bu düzeni yayınlayıp canlı ana sayfa yapmak istediğinize emin misiniz?"
                        class="dost-btn dost-btn-rose">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    <span>Yayınla & Canlı Yap</span>
                </button>
            </div>
        </header>

        {{-- 2-Column Split Editor Body --}}
        <div class="dost-editor-body">

            {{-- 340px Sidebar Panel --}}
            <aside class="dost-editor-sidebar">

                @if ($contextMode === 'list')
                    {{-- Context A: Overview & Block List --}}
                    <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px;">
                        <h3 style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: #94a3b8; margin: 0;">
                            Ana Sayfa Blokları
                        </h3>
                        <span class="dost-badge dost-badge-slate">
                            {{ count($draftSections) }} Bölüm
                        </span>
                    </div>

                    {{-- Add New Block Menu --}}
                    <div class="dost-card">
                        <label style="font-size: 12px; font-weight: 600; color: #cbd5e1; display: block;">+ Yeni Bölüm Ekle</label>
                        <div style="display: flex; gap: 6px;">
                            <select wire:model.live="newBlockType" class="dost-select">
                                <option value="">-- Bölüm Türü Seçin --</option>
                                @foreach ($blockTypes as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <button type="button"
                                    wire:click="addBlock"
                                    class="dost-btn dost-btn-rose">
                                Ekle
                            </button>
                        </div>
                    </div>

                    {{-- Dynamic Sections List with Drag & Drop & Locked Areas --}}
                    <div style="display: flex; flex-direction: column; gap: 6px;"
                         x-data="{
                             draggedIndex: null,
                             handleDragStart(e, index) {
                                 this.draggedIndex = index;
                                 e.dataTransfer.effectAllowed = 'move';
                                 e.dataTransfer.setData('text/plain', index);
                             },
                             handleDragOver(e) {
                                 e.preventDefault();
                                 e.dataTransfer.dropEffect = 'move';
                             },
                             handleDrop(e, targetIndex) {
                                 e.preventDefault();
                                 if (this.draggedIndex !== null && this.draggedIndex !== targetIndex) {
                                     $wire.reorderBlocks(this.draggedIndex, targetIndex);
                                 }
                                 this.draggedIndex = null;
                             }
                         }">

                        {{-- Locked Pinned Area 1: Header --}}
                        <div class="dost-card" style="padding: 8px 10px; display: flex; align-items: center; justify-content: space-between; border-left: 3px solid #eab308; background: #0f1219; cursor: pointer;" wire:click="selectFixedArea('header')">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span style="font-size: 11px;">🔒</span>
                                <div style="display: flex; flex-direction: column;">
                                    <span style="font-size: 11px; font-weight: 700; color: #cbd5e1;">Üst Alan (Header)</span>
                                    <span style="font-size: 9px; color: #64748b;">Sabit Bölüm • Tasarım Ayarları</span>
                                </div>
                            </div>
                            <button type="button" wire:click="selectFixedArea('header')" class="dost-btn dost-btn-slate" style="padding: 3px 8px; font-size: 10px;">
                                Düzenle
                            </button>
                        </div>

                        {{-- Locked Pinned Area 2: Hero Banner --}}
                        <div class="dost-card" style="padding: 8px 10px; display: flex; align-items: center; justify-content: space-between; border-left: 3px solid #eab308; background: #0f1219; margin-bottom: 4px; cursor: pointer;" wire:click="selectFixedArea('hero')">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span style="font-size: 11px;">🔒</span>
                                <div style="display: flex; flex-direction: column;">
                                    <span style="font-size: 11px; font-weight: 700; color: #cbd5e1;">Manşet (Hero Banner)</span>
                                    <span style="font-size: 9px; color: #64748b;">Sabit Bölüm • Tasarım Ayarları</span>
                                </div>
                            </div>
                            <button type="button" wire:click="selectFixedArea('hero')" class="dost-btn dost-btn-slate" style="padding: 3px 8px; font-size: 10px;">
                                Düzenle
                            </button>
                        </div>

                        {{-- Reorderable Dynamic Blocks --}}
                        @forelse ($draftSections as $index => $section)
                            @php
                                $uuid = $section['uuid'] ?? ($section['key'] ?? '');
                                $blockType = $section['block_type'] ?? '';
                                $typeLabel = $blockTypes[$blockType] ?? 'Blok';
                                $titleStr = ! empty($section['title']) ? $section['title'] : $typeLabel;
                                $isVisible = ! empty($section['visible'] ?? true);

                                $summaryDetail = '';
                                if ($blockType === 'content_shelf') {
                                    $shelfTypeLabel = ($section['shelf_type'] ?? 'program') === 'program' ? '📺 Program Rafı' : '🎬 Video Rafı';
                                    $srcMode = $section['source_mode'] ?? 'manual';
                                    $srcLabel = match($srcMode) {
                                        'category' => 'Kategori',
                                        'hybrid' => 'Hibrit',
                                        default => 'Manuel',
                                    };
                                    $catName = ! empty($section['category_id']) ? ($this->categoryOptions[$section['category_id']] ?? '') : '';
                                    $summaryDetail = $shelfTypeLabel . ' • ' . $srcLabel . ($catName ? ' (' . $catName . ')' : '');
                                } elseif ($blockType === 'today_schedule') {
                                    $summaryDetail = '📺 Yayın Akışı • Otomatik Saate Odaklı';
                                } elseif ($blockType === 'program_showcase') {
                                    $pColId = $section['program_collection_id'] ?? null;
                                    $pColName = $pColId ? ($this->programCollectionOptions[$pColId] ?? '') : '';
                                    $summaryDetail = '📺 Program Vitrini' . ($pColName ? ' • ' . $pColName : ' • Koleksiyon Seçilmedi');
                                } elseif ($blockType === 'video_collection') {
                                    $colId = $section['collection_id'] ?? ($section['video_collection_id'] ?? null);
                                    $colName = $colId ? ($this->videoCollectionOptions[$colId] ?? '') : '';
                                    $summaryDetail = '🎬 Video Vitrini' . ($colName ? ' • ' . $colName : ' • Koleksiyon Seçilmedi');
                                } elseif ($blockType === 'category_shelf') {
                                    $catName = ! empty($section['category_id']) ? ($this->categoryOptions[$section['category_id']] ?? '') : '';
                                    $summaryDetail = '📁 Kategori Rafı' . ($catName ? ' • ' . $catName : '');
                                } else {
                                    $summaryDetail = $typeLabel;
                                }
                            @endphp

                            <div class="dost-section-item"
                                 draggable="true"
                                 @dragstart="handleDragStart($event, {{ $index }})"
                                 @dragover="handleDragOver($event)"
                                 @drop="handleDrop($event, {{ $index }})"
                                 style="display: flex; align-items: center; justify-content: space-between; background: #0f1219; border: 1px solid rgba(255,255,255,0.12); border-radius: 8px; padding: 8px 10px; cursor: move; transition: all 0.2s;"
                                 :style="draggedIndex === {{ $index }} ? 'opacity: 0.4; border-style: dashed;' : ''">

                                <div style="display: flex; align-items: center; gap: 8px; flex: 1; min-width: 0;">
                                    {{-- Drag Handle & Arrows --}}
                                    <div style="display: flex; align-items: center; gap: 4px;">
                                        <span style="font-size: 13px; color: #64748b; cursor: grab;" title="Sürükle Taşımak İçin Basılı Tutun">☰</span>
                                        <div style="display: flex; flex-direction: column; gap: 1px;">
                                            <button type="button" wire:click="moveBlock('{{ $uuid }}', 'up')" @disabled($index === 0) class="dost-icon-btn" style="padding:0; font-size:8px; height:12px;" title="Yukarı Taşı">▲</button>
                                            <button type="button" wire:click="moveBlock('{{ $uuid }}', 'down')" @disabled($index === count($draftSections) - 1) class="dost-icon-btn" style="padding:0; font-size:8px; height:12px;" title="Aşağı Taşı">▼</button>
                                        </div>
                                    </div>

                                    {{-- Visibility --}}
                                    <button type="button"
                                            wire:click="toggleBlockVisibility('{{ $uuid }}')"
                                            class="dost-icon-btn"
                                            title="Göster / Gizle">
                                        @if ($isVisible)
                                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="color:#34d399; width:15px; height:15px;">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        @else
                                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="color:#64748b; width:15px; height:15px;">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                            </svg>
                                        @endif
                                    </button>

                                    {{-- Title & Summary --}}
                                    <div style="display: flex; flex-direction: column; min-width: 0; flex: 1; cursor: pointer;" wire:click="selectBlock('{{ $uuid }}')">
                                        <span style="font-size: 12px; font-weight: 700; color: #ffffff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; {{ $isVisible ? '' : 'text-decoration: line-through; opacity: 0.5;' }}">
                                            {{ $titleStr }}
                                        </span>
                                        <span style="font-size: 10px; color: #94a3b8; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                            {{ $summaryDetail }}
                                        </span>
                                    </div>
                                </div>

                                {{-- Action Buttons --}}
                                <div style="display: flex; align-items: center; gap: 4px;">
                                    <button type="button"
                                            wire:click="selectBlock('{{ $uuid }}')"
                                            class="dost-btn dost-btn-slate"
                                            style="padding: 3px 8px; font-size: 10px; height: auto;"
                                            title="Düzenle">
                                        Düzenle
                                    </button>

                                    <button type="button"
                                            wire:click="deleteBlock('{{ $uuid }}')"
                                            wire:confirm="Bu bölümü silmek istediğinize emin misiniz?\n\n(Silme işlemi yalnızca bu şablon bloğunu kaldırır. Bağlı videolar veya programlar veritabanından silinmez.)"
                                            class="dost-icon-btn"
                                            style="color: #f43f5e; padding: 4px;"
                                            title="Sil">
                                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width:14px; height:14px;">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        @empty
                            <div style="border: 1px dashed rgba(255,255,255,0.1); border-radius: 8px; padding: 20px; text-align: center; color: #64748b; font-size: 12px;">
                                Henüz eklenmiş dinamik bölüm bulunmuyor. Yukarıda bulunan "+ Yeni Bölüm Ekle" menüsünden ekleme yapabilirsiniz.
                            </div>
                        @endforelse

                        {{-- Locked Pinned Area 3: Footer --}}
                        <div class="dost-card" style="padding: 8px 10px; display: flex; align-items: center; justify-content: space-between; border-left: 3px solid #eab308; background: #0f1219; margin-top: 4px; cursor: pointer;" wire:click="selectFixedArea('footer')">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span style="font-size: 11px;">🔒</span>
                                <div style="display: flex; flex-direction: column;">
                                    <span style="font-size: 11px; font-weight: 700; color: #cbd5e1;">Alt Bilgi & İletişim (Footer)</span>
                                    <span style="font-size: 9px; color: #64748b;">Sabit Bölüm • Tasarım Ayarları</span>
                                </div>
                            </div>
                            <button type="button" wire:click="selectFixedArea('footer')" class="dost-btn dost-btn-slate" style="padding: 3px 8px; font-size: 10px;">
                                Düzenle
                            </button>
                        </div>
                    </div>

                @elseif (in_array($contextMode, ['edit', 'block']) && $this->selectedBlock)
                    {{-- Context B: Selected Block Editor Shell (Clean 4-Tab Structure) --}}
                    @php
                        $block = $this->selectedBlock;
                        $blockType = $block['block_type'] ?? '';
                        $typeLabel = $blockTypes[$blockType] ?? 'Blok';
                        $uuid = $block['uuid'] ?? '';

                        $sIndex = null;
                        foreach ($draftSections as $idx => $sec) {
                            if (($sec['uuid'] ?? ($sec['key'] ?? '')) === $uuid) {
                                $sIndex = $idx;
                                break;
                            }
                        }
                    @endphp

                    {{-- Top Header Context Card --}}
                    <div style="display: flex; flex-direction: column; gap: 8px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px; margin-bottom: 4px;">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <button type="button"
                                    wire:click="selectListContext"
                                    style="background: none; border: none; color: #f43f5e; font-size: 12px; font-weight: 600; cursor: pointer; padding: 0; display: flex; align-items: center; gap: 4px;">
                                <span>&larr; Bölüm Listesine Dön</span>
                            </button>
                            @if (in_array($blockType, ['program_showcase', 'video_collection'], true) && $editTargetMode === 'collection')
                                <span class="dost-badge dost-badge-emerald" style="background: rgba(225,29,72,0.15); color: #f43f5e; border-color: rgba(225,29,72,0.3); font-size: 9px;">
                                    🔗 Koleksiyon Sayfası
                                </span>
                            @else
                                <span class="dost-badge dost-badge-emerald" style="font-size: 9px;">
                                    🏠 Ana Sayfa
                                </span>
                            @endif
                        </div>

                        <div style="background: rgba(15, 18, 25, 0.6); padding: 8px 10px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.08); display: flex; align-items: center; justify-content: space-between;">
                            <div style="display: flex; flex-direction: column;">
                                <span style="font-size: 9px; text-transform: uppercase; color: #64748b; font-weight: 700;">Düzenlenen</span>
                                <strong style="font-size: 12px; font-weight: 700; color: #ffffff; line-height: 1.2;">
                                    {{ $draftSections[$sIndex]['title'] ?? $typeLabel }}
                                </strong>
                            </div>
                            <div style="text-align: right;">
                                <span style="font-size: 9px; text-transform: uppercase; color: #64748b; font-weight: 700;">Tür</span>
                                <span style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block; line-height: 1.2;">
                                    {{ $typeLabel }}
                                </span>
                            </div>
                        </div>

                        @if (in_array($blockType, ['program_showcase', 'video_collection'], true) && (!empty($block['program_collection_id']) || !empty($block['collection_id']) || !empty($block['video_collection_id'])))
                            <button type="button"
                                    wire:click="editTargetDetailPage"
                                    class="dost-btn dost-btn-amber"
                                    style="width: 100%; justify-content: center; font-size: 11px; margin-top: 4px;">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width: 14px; height: 14px;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                                <span>🔗 Detay Sayfasını Visual Builder'da Düzenle</span>
                            </button>
                        @endif

                        {{-- Device Context Sync Badge --}}
                        <div x-show="activeDevice !== 'desktop'"
                             style="background: rgba(225, 29, 72, 0.12); border: 1px solid rgba(225, 29, 72, 0.35); padding: 6px 10px; border-radius: 6px; display: flex; align-items: center; justify-content: space-between;">
                            <div style="display: flex; align-items: center; gap: 6px;">
                                <span style="font-size: 12px;" x-text="activeDevice === 'tablet' ? '▣' : '📱'"></span>
                                <strong style="font-size: 11px; font-weight: 700; color: #f43f5e;" x-text="activeDevice === 'tablet' ? 'Tablet Önizleme (768px)' : 'Mobil Önizleme (390px)'"></strong>
                            </div>
                            <span style="font-size: 9px; color: #cbd5e1;"
                                  x-text="{{ ! empty($draftSections[$sIndex]['custom_responsive'] ?? false) ? 'true' : 'false' }} ? (activeDevice === 'tablet' ? 'Tablet ayarları etkin' : 'Mobil ayarları etkin') : 'Otomatik responsive kullanılıyor'"></span>
                        </div>

                        @if (in_array($blockType, ['program_showcase', 'video_collection'], true))
                            @php
                                $hasColSelected = $blockType === 'program_showcase'
                                    ? filled($draftSections[$sIndex]['program_collection_id'] ?? null)
                                    : filled($draftSections[$sIndex]['collection_id'] ?? ($draftSections[$sIndex]['video_collection_id'] ?? null));
                            @endphp
                            <div style="display: flex; gap: 4px; margin-top: 2px;">
                                <button type="button"
                                        wire:click="setEditTargetMode('homepage')"
                                        class="dost-btn {{ $editTargetMode === 'homepage' ? 'dost-btn-rose' : 'dost-btn-slate' }}"
                                        style="flex: 1; padding: 5px 0; justify-content: center; font-size: 10px;">
                                    Ana Sayfa Vitrini
                                </button>
                                <button type="button"
                                        wire:click="setEditTargetMode('collection')"
                                        @if (! $hasColSelected) title="Önce bir Koleksiyon seçin" @endif
                                        class="dost-btn {{ $editTargetMode === 'collection' ? 'dost-btn-rose' : 'dost-btn-slate' }}"
                                        style="flex: 1; padding: 5px 0; justify-content: center; font-size: 10px; {{ ! $hasColSelected ? 'opacity: 0.5;' : '' }}">
                                    Tümünü Gör Sayfası
                                </button>
                            </div>
                        @endif
                    </div>

                    @if ($sIndex !== null)
                        @if ($editTargetMode === 'collection')
                            <div style="display: flex; flex-direction: column; gap: 12px; background: rgba(15, 23, 42, 0.6); padding: 12px; border-radius: 8px; border: 1px solid rgba(225, 29, 72, 0.3);">
                                <div style="display: flex; flex-direction: column; gap: 4px; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 8px;">
                                    <span style="font-size: 10px; font-weight: 800; text-transform: uppercase; color: #f43f5e; letter-spacing: 0.05em;">
                                        🔗 TÜMÜNÜ GÖR SAYFASI DÜZENLENİYOR
                                    </span>
                                    <span style="font-size: 11px; color: #94a3b8;">
                                        Bu ekrandaki değişiklikler doğrudan seçili koleksiyonun public detay sayfasına kaydolur. Ana sayfa vitrini etkilenmez.
                                    </span>
                                </div>

                                @if (empty($collectionPublicSettings))
                                    <div style="background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.3); padding: 10px; border-radius: 6px; color: #fef08a; font-size: 11px;">
                                        Lütfen önce <strong>Ana Sayfa Vitrini</strong> sekmesinde bir koleksiyon seçiniz.
                                    </div>
                                @else
                                    {{-- Masaüstü Kolon Sayısı --}}
                                    <div>
                                        <label style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Masaüstü Kolon Sayısı</label>
                                        <select wire:change="updateCollectionPublicSetting('desktop_columns', $event.target.value)" class="dost-select">
                                            @foreach ([3 => '3 Kolon', 4 => '4 Kolon', 5 => '5 Kolon', 6 => '6 Kolon', 7 => '7 Kolon', 8 => '8 Kolon'] as $cVal => $cLabel)
                                                <option value="{{ $cVal }}" {{ ((int)($collectionPublicSettings['desktop_columns'] ?? 4)) === $cVal ? 'selected' : '' }}>
                                                    {{ $cLabel }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- Tablet Kolon Sayısı --}}
                                    <div>
                                        <label style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Tablet Kolon Sayısı</label>
                                        <select wire:change="updateCollectionPublicSetting('tablet_columns', $event.target.value)" class="dost-select">
                                            @foreach ([1 => '1 Kolon', 2 => '2 Kolon', 3 => '3 Kolon', 4 => '4 Kolon', 5 => '5 Kolon'] as $cVal => $cLabel)
                                                <option value="{{ $cVal }}" {{ ((int)($collectionPublicSettings['tablet_columns'] ?? 3)) === $cVal ? 'selected' : '' }}>
                                                    {{ $cLabel }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- Mobil Kolon Sayısı --}}
                                    <div>
                                        <label style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Mobil Kolon Sayısı</label>
                                        <select wire:change="updateCollectionPublicSetting('mobile_columns', $event.target.value)" class="dost-select">
                                            @foreach ([1 => '1 Kolon', 2 => '2 Kolon', 3 => '3 Kolon', 4 => '4 Kolon'] as $cVal => $cLabel)
                                                <option value="{{ $cVal }}" {{ ((int)($collectionPublicSettings['mobile_columns'] ?? 1)) === $cVal ? 'selected' : '' }}>
                                                    {{ $cLabel }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- Kart Aralığı (Gap) --}}
                                    <div>
                                        <label style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Kart Aralığı (Gap)</label>
                                        <select wire:change="updateCollectionPublicSetting('gap_size', $event.target.value)" class="dost-select">
                                            <option value="small" {{ ($collectionPublicSettings['gap_size'] ?? 'medium') === 'small' ? 'selected' : '' }}>Dar (Small)</option>
                                            <option value="medium" {{ ($collectionPublicSettings['gap_size'] ?? 'medium') === 'medium' ? 'selected' : '' }}>Normal (Medium)</option>
                                            <option value="large" {{ ($collectionPublicSettings['gap_size'] ?? 'medium') === 'large' ? 'selected' : '' }}>Geniş (Large)</option>
                                        </select>
                                    </div>

                                    {{-- Sayfa Başına İçerik --}}
                                    <div>
                                        <label style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Sayfa Başına İçerik Limiti</label>
                                        <select wire:change="updateCollectionPublicSetting('page_size', $event.target.value)" class="dost-select">
                                            <option value="all" {{ ($collectionPublicSettings['page_size'] ?? 'all') === 'all' ? 'selected' : '' }}>Tümü (Sayfalamasız)</option>
                                            <option value="12" {{ ($collectionPublicSettings['page_size'] ?? '') == '12' ? 'selected' : '' }}>12 İçerik</option>
                                            <option value="24" {{ ($collectionPublicSettings['page_size'] ?? '') == '24' ? 'selected' : '' }}>24 İçerik</option>
                                            <option value="48" {{ ($collectionPublicSettings['page_size'] ?? '') == '48' ? 'selected' : '' }}>48 İçerik</option>
                                        </select>
                                    </div>

                                    {{-- Görünüm Modu --}}
                                    <div>
                                        <label style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Görünüm Modu</label>
                                        <select wire:change="updateCollectionPublicSetting('display_variant', $event.target.value)" class="dost-select">
                                            <option value="grid" {{ ($collectionPublicSettings['display_variant'] ?? 'grid') === 'grid' ? 'selected' : '' }}>Izgara (Grid)</option>
                                            <option value="carousel" {{ ($collectionPublicSettings['display_variant'] ?? 'grid') === 'carousel' ? 'selected' : '' }}>Kaydırmalı (Carousel)</option>
                                        </select>
                                    </div>

                                    {{-- Açıklamayı Göster --}}
                                    <div style="display: flex; align-items: center; justify-content: space-between;">
                                        <label style="font-size: 11px; font-weight: 600; color: #cbd5e1;">Açıklamayı Göster</label>
                                        <button type="button"
                                                wire:click="updateCollectionPublicSetting('show_description', {{ ! ($collectionPublicSettings['show_description'] ?? true) ? 'true' : 'false' }})"
                                                style="padding: 3px 8px; border-radius: 9999px; font-size: 10px; font-weight: 700; border: none; cursor: pointer; {{ ! empty($collectionPublicSettings['show_description'] ?? true) ? 'background: #34d399; color: #0f172a;' : 'background: #475569; color: #cbd5e1;' }}">
                                            {{ ! empty($collectionPublicSettings['show_description'] ?? true) ? 'AÇIK' : 'KAPALI' }}
                                        </button>
                                    </div>

                                    <hr style="border: 0; border-top: 1px solid rgba(255,255,255,0.08); margin: 4px 0;">

                                    {{-- Cihaz Bazlı Ayarları Özelleştir Toggle --}}
                                    <div style="display: flex; align-items: center; justify-content: space-between;">
                                        <div>
                                            <label style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block;">Cihaz Bazlı Ayarları Özelleştir</label>
                                            <span style="font-size: 9px; color: #94a3b8;">Tablet ve Mobil için ayrı detay ayarları</span>
                                        </div>
                                        <button type="button"
                                                wire:click="updateCollectionPublicSetting('custom_responsive', {{ ! ($collectionPublicSettings['custom_responsive'] ?? false) ? 'true' : 'false' }})"
                                                style="padding: 3px 8px; border-radius: 9999px; font-size: 10px; font-weight: 700; border: none; cursor: pointer; {{ ! empty($collectionPublicSettings['custom_responsive'] ?? false) ? 'background: #34d399; color: #0f172a;' : 'background: #475569; color: #cbd5e1;' }}">
                                            {{ ! empty($collectionPublicSettings['custom_responsive'] ?? false) ? 'AÇIK' : 'KAPALI (OTOMATİK)' }}
                                        </button>
                                    </div>

                                    @if (! empty($collectionPublicSettings['custom_responsive'] ?? false))
                                        <div x-data="{ deviceSubTab: 'tablet' }" style="background: rgba(15, 18, 25, 0.8); padding: 10px; border-radius: 8px; border: 1px solid rgba(244,63,94,0.3); display: flex; flex-direction: column; gap: 10px;">
                                            <div style="display: flex; background: #0f1219; padding: 2px; border-radius: 6px; gap: 2px;">
                                                <button type="button" @click="deviceSubTab = 'tablet'"
                                                        :style="deviceSubTab === 'tablet' ? 'background: #e11d48; color: #ffffff;' : 'color: #94a3b8;'"
                                                        style="flex: 1; padding: 4px 0; border: none; border-radius: 4px; font-size: 10px; font-weight: 700; cursor: pointer; text-align: center;">
                                                    ▣ TABLET (768px)
                                                </button>
                                                <button type="button" @click="deviceSubTab = 'mobile'"
                                                        :style="deviceSubTab === 'mobile' ? 'background: #e11d48; color: #ffffff;' : 'color: #94a3b8;'"
                                                        style="flex: 1; padding: 4px 0; border: none; border-radius: 4px; font-size: 10px; font-weight: 700; cursor: pointer; text-align: center;">
                                                    📱 MOBİL (390px)
                                                </button>
                                            </div>

                                            <div x-show="deviceSubTab === 'tablet'" style="display: flex; flex-direction: column; gap: 8px;">
                                                <div>
                                                    <label style="font-size: 10px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 2px;">Tablet Kart Sayısı</label>
                                                    <select wire:change="updateCollectionPublicSetting('responsive_settings.tablet.columns', $event.target.value)" class="dost-select">
                                                        @foreach ([1 => '1 Kart', 2 => '2 Kart', 3 => '3 Kart', 4 => '4 Kart', 5 => '5 Kart', 6 => '6 Kart'] as $cVal => $cLabel)
                                                            <option value="{{ $cVal }}" {{ ((int)($collectionPublicSettings['responsive_settings']['tablet']['columns'] ?? $collectionPublicSettings['tablet_columns'] ?? 3)) === $cVal ? 'selected' : '' }}>
                                                                {{ $cLabel }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>

                                            <div x-show="deviceSubTab === 'mobile'" style="display: flex; flex-direction: column; gap: 8px;">
                                                <div>
                                                    <label style="font-size: 10px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 2px;">Mobil Kart Sayısı</label>
                                                    <select wire:change="updateCollectionPublicSetting('responsive_settings.mobile.columns', $event.target.value)" class="dost-select">
                                                        @foreach ([1 => '1 Kart', 2 => '2 Kart', 3 => '3 Kart', 4 => '4 Kart'] as $cVal => $cLabel)
                                                            <option value="{{ $cVal }}" {{ ((int)($collectionPublicSettings['responsive_settings']['mobile']['columns'] ?? $collectionPublicSettings['mobile_columns'] ?? 1)) === $cVal ? 'selected' : '' }}>
                                                                {{ $cLabel }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                @endif
                            </div>
                        @else
                            <div x-data="{ activeTab: 'content' }" style="display: flex; flex-direction: column; gap: 12px;">
                                <div style="display: flex; flex-direction: column; gap: 4px; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 8px;">
                                    <span style="font-size: 10px; font-weight: 800; text-transform: uppercase; color: #38bdf8; letter-spacing: 0.05em;">
                                        🏠 ANA SAYFA VİTRİNİ DÜZENLENİYOR
                                    </span>
                                    <span style="font-size: 11px; color: #94a3b8;">
                                        Bu ekrandaki değişiklikler yalnız ana sayfadaki Vitrin bloğuna kaydolur.
                                    </span>
                                </div>

                            {{-- Segmented 4-Tab Switcher --}}
                            <div style="display: flex; background: rgba(15, 18, 25, 0.95); padding: 3px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.12); gap: 2px;">
                                <button type="button" @click="activeTab = 'content'"
                                        :style="activeTab === 'content' ? 'background: #e11d48; color: #ffffff;' : 'color: #94a3b8;'"
                                        style="flex: 1; padding: 6px 0; border: none; border-radius: 5px; font-size: 10px; font-weight: 700; cursor: pointer; text-align: center;">
                                    İÇERİK
                                </button>
                                <button type="button" @click="activeTab = 'layout'"
                                        :style="activeTab === 'layout' ? 'background: #e11d48; color: #ffffff;' : 'color: #94a3b8;'"
                                        style="flex: 1; padding: 6px 0; border: none; border-radius: 5px; font-size: 10px; font-weight: 700; cursor: pointer; text-align: center;">
                                    YERLEŞİM
                                </button>
                                <button type="button" @click="activeTab = 'dimensions'"
                                        :style="activeTab === 'dimensions' ? 'background: #e11d48; color: #ffffff;' : 'color: #94a3b8;'"
                                        style="flex: 1; padding: 6px 0; border: none; border-radius: 5px; font-size: 10px; font-weight: 700; cursor: pointer; text-align: center;">
                                    ÖLÇÜLER
                                </button>
                                <button type="button" @click="activeTab = 'advanced'"
                                        :style="activeTab === 'advanced' ? 'background: #e11d48; color: #ffffff;' : 'color: #94a3b8;'"
                                        style="flex: 1; padding: 6px 0; border: none; border-radius: 5px; font-size: 10px; font-weight: 700; cursor: pointer; text-align: center;">
                                    GELİŞMİŞ
                                </button>
                            </div>

                            {{-- TAB 1: İÇERİK SEKMESİ --}}
                            <div x-show="activeTab === 'content'" style="display: flex; flex-direction: column; gap: 10px;">
                                <div class="dost-card">
                                    {{-- Program Showcase --}}
                                    @if ($blockType === 'program_showcase')
                                        <div>
                                            <label style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Program Koleksiyonu</label>
                                            <select wire:model.live="draftSections.{{ $sIndex }}.program_collection_id" class="dost-select">
                                                <option value="">-- Koleksiyon Seçin --</option>
                                                @foreach ($this->programCollectionOptions as $colId => $colName)
                                                    <option value="{{ $colId }}">{{ $colName }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                    {{-- Video Collection --}}
                                    @elseif ($blockType === 'video_collection')
                                        <div>
                                            <label style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Video Koleksiyonu</label>
                                            <select wire:model.live="draftSections.{{ $sIndex }}.collection_id" class="dost-select">
                                                <option value="">-- Video Koleksiyonu Seçin --</option>
                                                @foreach ($this->videoCollectionOptions as $colId => $colName)
                                                    <option value="{{ $colId }}">{{ $colName }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                    {{-- Category Shelf --}}
                                    @elseif ($blockType === 'category_shelf')
                                        <div>
                                            <label style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Kategori Seçin</label>
                                            <select wire:model.live="draftSections.{{ $sIndex }}.category_id" class="dost-select">
                                                <option value="">-- Kategori Seçin --</option>
                                                @foreach ($this->categoryOptions as $catId => $catName)
                                                    <option value="{{ $catId }}">{{ $catName }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                    {{-- Dynamic Content Shelf --}}
                                    @elseif ($blockType === 'content_shelf')
                                        @php $blockUuid = $uuid; @endphp
                                        <div>
                                            <label style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Raf Türü</label>
                                            <div style="display: flex; gap: 4px; background: #0f1219; padding: 3px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.15);">
                                                <button type="button" wire:click="setShelfType('{{ $blockUuid }}', 'program')"
                                                        class="dost-btn {{ ($draftSections[$sIndex]['shelf_type'] ?? 'program') === 'program' ? 'dost-btn-rose' : 'dost-btn-slate' }}"
                                                        style="flex: 1; padding: 5px 0; justify-content: center; font-size: 10px;">
                                                    Program Rafı
                                                </button>
                                                <button type="button" wire:click="setShelfType('{{ $blockUuid }}', 'video')"
                                                        class="dost-btn {{ ($draftSections[$sIndex]['shelf_type'] ?? 'program') === 'video' ? 'dost-btn-rose' : 'dost-btn-slate' }}"
                                                        style="flex: 1; padding: 5px 0; justify-content: center; font-size: 10px;">
                                                    Video Rafı
                                                </button>
                                            </div>
                                        </div>
                                        <div>
                                            <label style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Kaynak Modu</label>
                                            <div style="display: flex; gap: 4px; background: #0f1219; padding: 3px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.15);">
                                                <button type="button" wire:click="setShelfSourceMode('{{ $blockUuid }}', 'manual')"
                                                        class="dost-btn {{ ($draftSections[$sIndex]['source_mode'] ?? 'manual') === 'manual' ? 'dost-btn-rose' : 'dost-btn-slate' }}"
                                                        style="flex: 1; padding: 4px 0; justify-content: center; font-size: 10px;">
                                                    Manuel
                                                </button>
                                                <button type="button" wire:click="setShelfSourceMode('{{ $blockUuid }}', 'category')"
                                                        class="dost-btn {{ ($draftSections[$sIndex]['source_mode'] ?? 'manual') === 'category' ? 'dost-btn-rose' : 'dost-btn-slate' }}"
                                                        style="flex: 1; padding: 4px 0; justify-content: center; font-size: 10px;">
                                                    Kategori
                                                </button>
                                                <button type="button" wire:click="setShelfSourceMode('{{ $blockUuid }}', 'hybrid')"
                                                        class="dost-btn {{ ($draftSections[$sIndex]['source_mode'] ?? 'manual') === 'hybrid' ? 'dost-btn-rose' : 'dost-btn-slate' }}"
                                                        style="flex: 1; padding: 4px 0; justify-content: center; font-size: 10px;">
                                                    Hibrit
                                                </button>
                                            </div>
                                        </div>
                                        @if (in_array($draftSections[$sIndex]['source_mode'] ?? '', ['category', 'hybrid']))
                                            <div>
                                                <label style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Kategori Seçin</label>
                                                <select wire:model.live="draftSections.{{ $sIndex }}.category_id" class="dost-select">
                                                    <option value="">-- Kategori Seçin --</option>
                                                    @foreach ($this->categoryOptions as $catId => $catName)
                                                        <option value="{{ $catId }}">{{ $catName }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        @endif
                                    @endif

                                    @if (in_array($blockType, ['live_intro', 'live_stream'], true))
                                        <div>
                                            <label style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Üst Rozet Metni</label>
                                            <input type="text"
                                                   wire:model.live.debounce.300ms="draftSections.{{ $sIndex }}.badge_text"
                                                   class="dost-input"
                                                   placeholder="Uydu üzerinden 7/24 yayın">
                                        </div>
                                        <div>
                                            <label style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Ana Başlık (Boş ise Site Adı)</label>
                                            <input type="text"
                                                   wire:model.live.debounce.300ms="draftSections.{{ $sIndex }}.title"
                                                   class="dost-input"
                                                   placeholder="Dost TV">
                                        </div>
                                        <div>
                                            <label style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Vurgulu Başlık Metni</label>
                                            <input type="text"
                                                   wire:model.live.debounce.300ms="draftSections.{{ $sIndex }}.highlight_text"
                                                   class="dost-input"
                                                   placeholder="her an yanınızda">
                                        </div>
                                        <div>
                                            <label style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Açıklama Metni</label>
                                            <textarea wire:model.live.debounce.300ms="draftSections.{{ $sIndex }}.description"
                                                      class="dost-input"
                                                      rows="3"
                                                      style="height: auto; resize: vertical;"
                                                      placeholder="Diziler, haberler ve belgesellerle dolu yayın akışımızı takip edin..."></textarea>
                                        </div>
                                        <div>
                                            <label style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">TV Buton Metni</label>
                                            <input type="text"
                                                   wire:model.live.debounce.300ms="draftSections.{{ $sIndex }}.tv_button_text"
                                                   class="dost-input"
                                                   placeholder="Canlı TV İzle">
                                        </div>
                                        <div style="display: flex; align-items: center; justify-content: space-between;">
                                            <label style="font-size: 11px; font-weight: 600; color: #cbd5e1;">TV Butonunu Göster</label>
                                            <button type="button"
                                                    wire:click="$set('draftSections.{{ $sIndex }}.show_tv_button', {{ ! ($draftSections[$sIndex]['show_tv_button'] ?? true) ? 'true' : 'false' }})"
                                                    style="padding: 3px 8px; border-radius: 9999px; font-size: 10px; font-weight: 700; border: none; cursor: pointer; {{ ! empty($draftSections[$sIndex]['show_tv_button'] ?? true) ? 'background: #34d399; color: #0f172a;' : 'background: #475569; color: #cbd5e1;' }}">
                                                {{ ! empty($draftSections[$sIndex]['show_tv_button'] ?? true) ? 'AÇIK' : 'KAPALI' }}
                                            </button>
                                        </div>
                                        <div>
                                            <label style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Radyo Buton Metni</label>
                                            <input type="text"
                                                   wire:model.live.debounce.300ms="draftSections.{{ $sIndex }}.radio_button_text"
                                                   class="dost-input"
                                                   placeholder="Canlı Radyo Dinle">
                                        </div>
                                        <div style="display: flex; align-items: center; justify-content: space-between;">
                                            <label style="font-size: 11px; font-weight: 600; color: #cbd5e1;">Radyo Butonunu Göster</label>
                                            <button type="button"
                                                    wire:click="$set('draftSections.{{ $sIndex }}.show_radio_button', {{ ! ($draftSections[$sIndex]['show_radio_button'] ?? true) ? 'true' : 'false' }})"
                                                    style="padding: 3px 8px; border-radius: 9999px; font-size: 10px; font-weight: 700; border: none; cursor: pointer; {{ ! empty($draftSections[$sIndex]['show_radio_button'] ?? true) ? 'background: #34d399; color: #0f172a;' : 'background: #475569; color: #cbd5e1;' }}">
                                                {{ ! empty($draftSections[$sIndex]['show_radio_button'] ?? true) ? 'AÇIK' : 'KAPALI' }}
                                            </button>
                                        </div>
                                    @else
                                        {{-- Common Title & Text Fields --}}
                                        <div>
                                            <label style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Blok Başlığı</label>
                                            <input type="text"
                                                   wire:model.live.debounce.300ms="draftSections.{{ $sIndex }}.title"
                                                   class="dost-input"
                                                   placeholder="Başlık girin...">
                                        </div>

                                        <div style="display: flex; align-items: center; justify-content: space-between;">
                                            <label style="font-size: 11px; font-weight: 600; color: #cbd5e1;">Başlığı Göster</label>
                                            <button type="button"
                                                    wire:click="$set('draftSections.{{ $sIndex }}.show_title', {{ ! ($draftSections[$sIndex]['show_title'] ?? true) ? 'true' : 'false' }})"
                                                    style="padding: 3px 8px; border-radius: 9999px; font-size: 10px; font-weight: 700; border: none; cursor: pointer; {{ ! empty($draftSections[$sIndex]['show_title'] ?? true) ? 'background: #34d399; color: #0f172a;' : 'background: #475569; color: #cbd5e1;' }}">
                                                {{ ! empty($draftSections[$sIndex]['show_title'] ?? true) ? 'AÇIK' : 'KAPALI' }}
                                            </button>
                                        </div>

                                        @if ($blockType !== 'today_schedule')
                                            <div>
                                                <label style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Alt Açıklama Metni</label>
                                                <input type="text"
                                                       wire:model.live.debounce.300ms="draftSections.{{ $sIndex }}.subtitle"
                                                       class="dost-input"
                                                       placeholder="İsteğe bağlı alt açıklama...">
                                            </div>

                                            <div>
                                                <label style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Gösterilecek İçerik Sayısı</label>
                                                <select wire:model.live="draftSections.{{ $sIndex }}.content_limit" class="dost-select">
                                                    @foreach ($contentLimitOptions as $lKey => $lLabel)
                                                        <option value="{{ $lKey }}">{{ $lLabel }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        @else
                                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                                <label style="font-size: 11px; font-weight: 600; color: #cbd5e1;">"ŞİMDİ" Rozeti</label>
                                                <button type="button"
                                                        wire:click="$set('draftSections.{{ $sIndex }}.show_now_badge', {{ ! ($draftSections[$sIndex]['show_now_badge'] ?? true) ? 'true' : 'false' }})"
                                                        style="padding: 3px 8px; border-radius: 9999px; font-size: 10px; font-weight: 700; border: none; cursor: pointer; {{ ! empty($draftSections[$sIndex]['show_now_badge'] ?? true) ? 'background: #34d399; color: #0f172a;' : 'background: #475569; color: #cbd5e1;' }}">
                                                    {{ ! empty($draftSections[$sIndex]['show_now_badge'] ?? true) ? 'AÇIK' : 'KAPALI' }}
                                                </button>
                                            </div>

                                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                                <label style="font-size: 11px; font-weight: 600; color: #cbd5e1;">"SIRADAKİ" Rozeti</label>
                                                <button type="button"
                                                        wire:click="$set('draftSections.{{ $sIndex }}.show_next_badge', {{ ! ($draftSections[$sIndex]['show_next_badge'] ?? true) ? 'true' : 'false' }})"
                                                        style="padding: 3px 8px; border-radius: 9999px; font-size: 10px; font-weight: 700; border: none; cursor: pointer; {{ ! empty($draftSections[$sIndex]['show_next_badge'] ?? true) ? 'background: #34d399; color: #0f172a;' : 'background: #475569; color: #cbd5e1;' }}">
                                                    {{ ! empty($draftSections[$sIndex]['show_next_badge'] ?? true) ? 'AÇIK' : 'KAPALI' }}
                                                </button>
                                            </div>

                                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                                <label style="font-size: 11px; font-weight: 600; color: #cbd5e1;">"Tüm Akış" Bağlantısı</label>
                                                <button type="button"
                                                        wire:click="$set('draftSections.{{ $sIndex }}.show_all_link', {{ ! ($draftSections[$sIndex]['show_all_link'] ?? true) ? 'true' : 'false' }})"
                                                        style="padding: 3px 8px; border-radius: 9999px; font-size: 10px; font-weight: 700; border: none; cursor: pointer; {{ ! empty($draftSections[$sIndex]['show_all_link'] ?? true) ? 'background: #34d399; color: #0f172a;' : 'background: #475569; color: #cbd5e1;' }}">
                                                    {{ ! empty($draftSections[$sIndex]['show_all_link'] ?? true) ? 'AÇIK' : 'KAPALI' }}
                                                </button>
                                            </div>
                                        @endif

                                        <div style="display: flex; align-items: center; justify-content: space-between; border-t: 1px solid rgba(255,255,255,0.08); padding-top: 6px;">
                                            <label style="font-size: 11px; font-weight: 600; color: #cbd5e1;">Bölümü Göster (Aktif)</label>
                                            <button type="button"
                                                    wire:click="$set('draftSections.{{ $sIndex }}.visible', {{ ! ($draftSections[$sIndex]['visible'] ?? true) ? 'true' : 'false' }})"
                                                    style="padding: 3px 8px; border-radius: 9999px; font-size: 10px; font-weight: 700; border: none; cursor: pointer; {{ ! empty($draftSections[$sIndex]['visible'] ?? true) ? 'background: #34d399; color: #0f172a;' : 'background: #475569; color: #cbd5e1;' }}">
                                                {{ ! empty($draftSections[$sIndex]['visible'] ?? true) ? 'AÇIK' : 'KAPALI' }}
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            @php
                                $secData = $draftSections[$sIndex];
                                $sUuid = $secData['uuid'] ?? ($secData['key'] ?? '');
                                $respResolved = \App\Services\Home\HomepageBlockRegistry::resolveResponsiveSettings($secData);
                                $autoMap = \App\Services\Home\HomepageBlockRegistry::getAutoResponsiveMap((int)($secData['desktop_columns'] ?? 4));

                                $hasTabletOverride = ! empty($secData['responsive_settings']['tablet']);
                                $hasMobileOverride = ! empty($secData['responsive_settings']['mobile']);

                                $desktopColsVal = (int) ($secData['desktop_columns'] ?? 4);
                                $tabletColsVal = (int) ($respResolved['tablet']['columns']);
                                $mobileColsVal = (int) ($respResolved['mobile']['columns']);

                                $desktopVariantVal = $secData['display_variant'] ?? 'grid';
                                $tabletVariantVal = $respResolved['tablet']['display_variant'];
                                $mobileVariantVal = $respResolved['mobile']['display_variant'];
                            @endphp

                            {{-- TAB 2: YERLEŞİM SEKMESİ --}}
                            <div x-show="activeTab === 'layout'" style="display: flex; flex-direction: column; gap: 10px;">
                                {{-- Device Editing Context Switcher inside Sidebar --}}
                                <div style="background: #0f1219; padding: 3px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.12); display: flex; gap: 3px;">
                                    <button type="button" @click="activeDevice = 'desktop'"
                                            :style="activeDevice === 'desktop' ? 'background: #e11d48; color: #ffffff;' : 'color: #94a3b8; background: transparent;'"
                                            style="flex: 1; padding: 6px 0; border: none; border-radius: 6px; font-size: 11px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 4px;">
                                        <span>🖥</span>
                                        <span>Masaüstü</span>
                                    </button>
                                    <button type="button" @click="activeDevice = 'tablet'"
                                            :style="activeDevice === 'tablet' ? 'background: #e11d48; color: #ffffff;' : 'color: #94a3b8; background: transparent;'"
                                            style="flex: 1; padding: 6px 0; border: none; border-radius: 6px; font-size: 11px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 4px;">
                                        <span>▣</span>
                                        <span>Tablet</span>
                                    </button>
                                    <button type="button" @click="activeDevice = 'mobile'"
                                            :style="activeDevice === 'mobile' ? 'background: #e11d48; color: #ffffff;' : 'color: #94a3b8; background: transparent;'"
                                            style="flex: 1; padding: 6px 0; border: none; border-radius: 6px; font-size: 11px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 4px;">
                                        <span>📱</span>
                                        <span>Mobil</span>
                                    </button>
                                </div>

                                {{-- Section Level Geometry & Height Controls --}}
                                <div class="dost-card" style="border-left: 3px solid #e11d48;">
                                    <div style="font-size: 11px; font-weight: 700; color: #ffffff; display: flex; align-items: center; justify-content: space-between;">
                                        <span>📐 Bölüm Geometrisi & Genişlik</span>
                                        <span class="dost-badge dost-badge-slate" style="font-size: 9px;">Section Level</span>
                                    </div>

                                    {{-- Section Width Mode --}}
                                    <div>
                                        <label style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Dış Bölüm Genişliği</label>
                                        <div style="display: flex; gap: 4px; background: #0f1219; padding: 3px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.15);">
                                            <button type="button" wire:click="setSectionWidthMode({{ $sIndex }}, 'full')"
                                                    class="dost-btn {{ ($secData['section_width_mode'] ?? 'full') === 'full' ? 'dost-btn-rose' : 'dost-btn-slate' }}"
                                                    style="flex: 1; padding: 4px 0; justify-content: center; font-size: 10px;">
                                                Tam Ekran
                                            </button>
                                            <button type="button" wire:click="setSectionWidthMode({{ $sIndex }}, 'boxed')"
                                                    class="dost-btn {{ ($secData['section_width_mode'] ?? 'full') === 'boxed' ? 'dost-btn-rose' : 'dost-btn-slate' }}"
                                                    style="flex: 1; padding: 4px 0; justify-content: center; font-size: 10px;">
                                                Kutulu (1280)
                                            </button>
                                            <button type="button" wire:click="setSectionWidthMode({{ $sIndex }}, 'custom')"
                                                    class="dost-btn {{ ($secData['section_width_mode'] ?? 'full') === 'custom' ? 'dost-btn-rose' : 'dost-btn-slate' }}"
                                                    style="flex: 1; padding: 4px 0; justify-content: center; font-size: 10px;">
                                                Özel px
                                            </button>
                                        </div>
                                    </div>

                                    @if (($secData['section_width_mode'] ?? 'full') === 'custom')
                                        <div>
                                            <label style="font-size: 10px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Özel Dış Bölüm Genişliği (px)</label>
                                            <input type="number" min="400" max="3840"
                                                   wire:model.live.debounce.300ms="draftSections.{{ $sIndex }}.section_max_width"
                                                   class="dost-input" placeholder="1440">
                                        </div>
                                    @endif

                                    {{-- Content Inner Width Mode --}}
                                    @if (($secData['section_width_mode'] ?? 'full') !== 'boxed')
                                        <div>
                                            <label style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">İç İçerik Genişliği (Inner Container)</label>
                                            <div style="display: flex; gap: 4px; background: #0f1219; padding: 3px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.15);">
                                                <button type="button" wire:click="$set('draftSections.{{ $sIndex }}.content_width_mode', 'standard')"
                                                        class="dost-btn {{ ($secData['content_width_mode'] ?? 'standard') === 'standard' ? 'dost-btn-rose' : 'dost-btn-slate' }}"
                                                        style="flex: 1; padding: 4px 0; justify-content: center; font-size: 10px;">
                                                    Standart
                                                </button>
                                                <button type="button" wire:click="$set('draftSections.{{ $sIndex }}.content_width_mode', 'wide')"
                                                        class="dost-btn {{ ($secData['content_width_mode'] ?? 'standard') === 'wide' ? 'dost-btn-rose' : 'dost-btn-slate' }}"
                                                        style="flex: 1; padding: 4px 0; justify-content: center; font-size: 10px;">
                                                    Geniş (1600)
                                                </button>
                                                <button type="button" wire:click="$set('draftSections.{{ $sIndex }}.content_width_mode', 'full')"
                                                        class="dost-btn {{ ($secData['content_width_mode'] ?? 'standard') === 'full' ? 'dost-btn-rose' : 'dost-btn-slate' }}"
                                                        style="flex: 1; padding: 4px 0; justify-content: center; font-size: 10px;">
                                                    Tam (%100)
                                                </button>
                                                <button type="button" wire:click="$set('draftSections.{{ $sIndex }}.content_width_mode', 'custom')"
                                                        class="dost-btn {{ ($secData['content_width_mode'] ?? 'standard') === 'custom' ? 'dost-btn-rose' : 'dost-btn-slate' }}"
                                                        style="flex: 1; padding: 4px 0; justify-content: center; font-size: 10px;">
                                                    Özel
                                                </button>
                                            </div>
                                        </div>

                                        @if (($secData['content_width_mode'] ?? 'standard') === 'custom')
                                            <div>
                                                <label style="font-size: 10px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Özel İç İçerik Genişliği (px)</label>
                                                <input type="number" min="400" max="3840"
                                                       wire:model.live.debounce.300ms="draftSections.{{ $sIndex }}.content_max_width"
                                                       class="dost-input" placeholder="1600">
                                            </div>
                                        @endif
                                    @endif

                                    {{-- Dikey Boşluk / Sıkılık Control --}}
                                    <div>
                                        <label style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Dikey Bölüm Boşluğu (Padding)</label>
                                        <div style="display: flex; gap: 4px; background: #0f1219; padding: 3px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.15);">
                                            <button type="button" wire:click="$set('draftSections.{{ $sIndex }}.padding_y_mode', 'compact')"
                                                    class="dost-btn {{ ($secData['padding_y_mode'] ?? 'normal') === 'compact' ? 'dost-btn-rose' : 'dost-btn-slate' }}"
                                                    style="flex: 1; padding: 4px 0; justify-content: center; font-size: 10px;">
                                                Sıkı (16)
                                            </button>
                                            <button type="button" wire:click="$set('draftSections.{{ $sIndex }}.padding_y_mode', 'normal')"
                                                    class="dost-btn {{ ($secData['padding_y_mode'] ?? 'normal') === 'normal' ? 'dost-btn-rose' : 'dost-btn-slate' }}"
                                                    style="flex: 1; padding: 4px 0; justify-content: center; font-size: 10px;">
                                                Normal (32)
                                            </button>
                                            <button type="button" wire:click="$set('draftSections.{{ $sIndex }}.padding_y_mode', 'spacious')"
                                                    class="dost-btn {{ ($secData['padding_y_mode'] ?? 'normal') === 'spacious' ? 'dost-btn-rose' : 'dost-btn-slate' }}"
                                                    style="flex: 1; padding: 4px 0; justify-content: center; font-size: 10px;">
                                                Ferah (48)
                                            </button>
                                            <button type="button" wire:click="$set('draftSections.{{ $sIndex }}.padding_y_mode', 'custom')"
                                                    class="dost-btn {{ ($secData['padding_y_mode'] ?? 'normal') === 'custom' ? 'dost-btn-rose' : 'dost-btn-slate' }}"
                                                    style="flex: 1; padding: 4px 0; justify-content: center; font-size: 10px;">
                                                Özel
                                            </button>
                                        </div>
                                    </div>

                                    @if (($secData['padding_y_mode'] ?? 'normal') === 'custom')
                                        <div>
                                            <label style="font-size: 10px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Özel Dikey Boşluk (px)</label>
                                            <input type="number" min="0" max="160"
                                                   wire:model.live.debounce.300ms="draftSections.{{ $sIndex }}.padding_y_custom"
                                                   class="dost-input" placeholder="32">
                                        </div>
                                    @endif

                                    {{-- Section Height Control --}}
                                    <div>
                                        <label style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Bölüm Yüksekliği (Min Height)</label>
                                        <div style="display: flex; gap: 4px; background: #0f1219; padding: 3px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.15);">
                                            <button type="button" wire:click="$set('draftSections.{{ $sIndex }}.section_height_mode', 'auto')"
                                                    class="dost-btn {{ ($secData['section_height_mode'] ?? 'auto') === 'auto' ? 'dost-btn-rose' : 'dost-btn-slate' }}"
                                                    style="flex: 1; padding: 4px 0; justify-content: center; font-size: 10px;">
                                                Otomatik
                                            </button>
                                            <button type="button" wire:click="$set('draftSections.{{ $sIndex }}.section_height_mode', 'short')"
                                                    class="dost-btn {{ ($secData['section_height_mode'] ?? 'auto') === 'short' ? 'dost-btn-rose' : 'dost-btn-slate' }}"
                                                    style="flex: 1; padding: 4px 0; justify-content: center; font-size: 10px;">
                                                Kısa (380)
                                            </button>
                                            <button type="button" wire:click="$set('draftSections.{{ $sIndex }}.section_height_mode', 'medium')"
                                                    class="dost-btn {{ ($secData['section_height_mode'] ?? 'auto') === 'medium' ? 'dost-btn-rose' : 'dost-btn-slate' }}"
                                                    style="flex: 1; padding: 4px 0; justify-content: center; font-size: 10px;">
                                                Orta (480)
                                            </button>
                                            <button type="button" wire:click="$set('draftSections.{{ $sIndex }}.section_height_mode', 'tall')"
                                                    class="dost-btn {{ ($secData['section_height_mode'] ?? 'auto') === 'tall' ? 'dost-btn-rose' : 'dost-btn-slate' }}"
                                                    style="flex: 1; padding: 4px 0; justify-content: center; font-size: 10px;">
                                                Uzun (600)
                                            </button>
                                            <button type="button" wire:click="$set('draftSections.{{ $sIndex }}.section_height_mode', 'custom')"
                                                    class="dost-btn {{ ($secData['section_height_mode'] ?? 'auto') === 'custom' ? 'dost-btn-rose' : 'dost-btn-slate' }}"
                                                    style="flex: 1; padding: 4px 0; justify-content: center; font-size: 10px;">
                                                Özel
                                            </button>
                                        </div>
                                        <span style="font-size: 9px; color: #94a3b8; display: block; margin-top: 3px;">Minimum bölüm yüksekliğidir. İçeriği küçültmez.</span>
                                    </div>

                                    @if (($secData['section_height_mode'] ?? 'auto') === 'custom')
                                        <div>
                                            <label style="font-size: 10px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Özel Yükseklik (px)</label>
                                            <input type="number" min="100" max="2000"
                                                   wire:model.live.debounce.300ms="draftSections.{{ $sIndex }}.section_height"
                                                   class="dost-input" placeholder="420">
                                        </div>
                                    @endif
                                </div>

                                <div class="dost-card">
                                    {{-- MASAÜSTÜ YERLEŞİMİ --}}
                                    <div x-show="activeDevice === 'desktop'" style="display: flex; flex-direction: column; gap: 10px;">
                                        <div>
                                            <label style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Masaüstü Görünüm Biçimi</label>
                                            <div style="display: flex; gap: 4px; background: #0f1219; padding: 3px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.15);">
                                                <button type="button" wire:click="updateDevicePresentation('{{ $sUuid }}', 'desktop', 'display_variant', 'grid')"
                                                        class="dost-btn {{ $desktopVariantVal === 'grid' ? 'dost-btn-rose' : 'dost-btn-slate' }}"
                                                        style="flex: 1; padding: 5px 0; justify-content: center; font-size: 11px;">
                                                    Izgara (Grid)
                                                </button>
                                                <button type="button" wire:click="updateDevicePresentation('{{ $sUuid }}', 'desktop', 'display_variant', 'horizontal_carousel')"
                                                        class="dost-btn {{ $desktopVariantVal === 'horizontal_carousel' ? 'dost-btn-rose' : 'dost-btn-slate' }}"
                                                        style="flex: 1; padding: 5px 0; justify-content: center; font-size: 11px;">
                                                    Kaydırmalı (Slider)
                                                </button>
                                            </div>
                                        </div>

                                        <div>
                                            <label style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Masaüstü Kart Sayısı</label>
                                            <div style="display: flex; gap: 4px; background: #0f1219; padding: 3px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.15); align-items: center;">
                                                @foreach ([3, 4, 5, 6, 7, 8] as $cNum)
                                                    <button type="button" wire:click="updateDevicePresentation('{{ $sUuid }}', 'desktop', 'desktop_columns', {{ $cNum }})"
                                                            class="dost-btn {{ $desktopColsVal === $cNum ? 'dost-btn-rose' : 'dost-btn-slate' }}"
                                                            style="flex: 1; padding: 4px 0; justify-content: center; font-size: 11px;">
                                                        {{ $cNum }}
                                                    </button>
                                                @endforeach
                                                <div style="display: flex; align-items: center; gap: 2px; padding-left: 4px; border-left: 1px solid rgba(255,255,255,0.1);">
                                                    <span style="font-size: 9px; color: #94a3b8; font-weight: 600; margin-right: 2px;">Özel:</span>
                                                    <input type="number" min="1" max="12"
                                                           value="{{ $desktopColsVal }}"
                                                           wire:change="updateDevicePresentation('{{ $sUuid }}', 'desktop', 'desktop_columns', $event.target.value)"
                                                           class="dost-input" style="width: 48px; text-align: center; padding: 3px 2px; font-size: 11px; font-weight: 700;">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- TABLET YERLEŞİMİ --}}
                                    <div x-show="activeDevice === 'tablet'" style="display: flex; flex-direction: column; gap: 10px;">
                                        {{-- Tablet Override Status Bar --}}
                                        <div style="background: rgba(15, 18, 25, 0.8); border: 1px solid rgba(255,255,255,0.1); border-radius: 6px; padding: 6px 10px; display: flex; align-items: center; justify-content: space-between;">
                                            <div>
                                                <span style="font-size: 9px; text-transform: uppercase; color: #94a3b8; font-weight: 700; display: block;">Tablet Durumu</span>
                                                @if ($hasTabletOverride)
                                                    <strong style="font-size: 11px; color: #f43f5e; font-weight: 700;">Tablet Özelleştirildi</strong>
                                                @else
                                                    <strong style="font-size: 11px; color: #34d399; font-weight: 700;">Otomatik ({{ $autoMap['tablet'] }} Kart)</strong>
                                                @endif
                                            </div>
                                            @if ($hasTabletOverride)
                                                <button type="button" wire:click="resetDeviceOverride('{{ $sUuid }}', 'tablet')"
                                                        style="background: #1e2430; color: #fb7185; border: 1px solid rgba(244,63,94,0.3); border-radius: 4px; padding: 3px 8px; font-size: 10px; font-weight: 700; cursor: pointer;">
                                                    🔄 Otomatiğe Dön
                                                </button>
                                            @endif
                                        </div>

                                        <div>
                                            <label style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Tablet Görünüm Biçimi</label>
                                            <div style="display: flex; gap: 4px; background: #0f1219; padding: 3px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.15);">
                                                <button type="button" wire:click="updateDevicePresentation('{{ $sUuid }}', 'tablet', 'display_variant', 'grid')"
                                                        class="dost-btn {{ $tabletVariantVal === 'grid' ? 'dost-btn-rose' : 'dost-btn-slate' }}"
                                                        style="flex: 1; padding: 5px 0; justify-content: center; font-size: 11px;">
                                                    Izgara (Grid)
                                                </button>
                                                <button type="button" wire:click="updateDevicePresentation('{{ $sUuid }}', 'tablet', 'display_variant', 'horizontal_carousel')"
                                                        class="dost-btn {{ $tabletVariantVal === 'horizontal_carousel' ? 'dost-btn-rose' : 'dost-btn-slate' }}"
                                                        style="flex: 1; padding: 5px 0; justify-content: center; font-size: 11px;">
                                                    Kaydırmalı (Slider)
                                                </button>
                                            </div>
                                        </div>

                                        <div>
                                            <label style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Tablet Kart Sayısı</label>
                                            <div style="display: flex; gap: 4px; background: #0f1219; padding: 3px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.15); align-items: center;">
                                                @foreach ([2, 3, 4] as $cNum)
                                                    <button type="button" wire:click="updateDevicePresentation('{{ $sUuid }}', 'tablet', 'columns', {{ $cNum }})"
                                                            class="dost-btn {{ $tabletColsVal === $cNum ? 'dost-btn-rose' : 'dost-btn-slate' }}"
                                                            style="flex: 1; padding: 4px 0; justify-content: center; font-size: 11px;">
                                                        {{ $cNum }}
                                                    </button>
                                                @endforeach
                                                <div style="display: flex; align-items: center; gap: 2px; padding-left: 4px; border-left: 1px solid rgba(255,255,255,0.1);">
                                                    <span style="font-size: 9px; color: #94a3b8; font-weight: 600; margin-right: 2px;">Özel:</span>
                                                    <input type="number" min="1" max="12"
                                                           value="{{ $tabletColsVal }}"
                                                           wire:change="updateDevicePresentation('{{ $sUuid }}', 'tablet', 'columns', $event.target.value)"
                                                           class="dost-input" style="width: 48px; text-align: center; padding: 3px 2px; font-size: 11px; font-weight: 700;">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- MOBİL YERLEŞİMİ --}}
                                    <div x-show="activeDevice === 'mobile'" style="display: flex; flex-direction: column; gap: 10px;">
                                        {{-- Mobile Override Status Bar --}}
                                        <div style="background: rgba(15, 18, 25, 0.8); border: 1px solid rgba(255,255,255,0.1); border-radius: 6px; padding: 6px 10px; display: flex; align-items: center; justify-content: space-between;">
                                            <div>
                                                <span style="font-size: 9px; text-transform: uppercase; color: #94a3b8; font-weight: 700; display: block;">Mobil Durum</span>
                                                @if ($hasMobileOverride)
                                                    <strong style="font-size: 11px; color: #f43f5e; font-weight: 700;">Mobil Özelleştirildi</strong>
                                                @else
                                                    <strong style="font-size: 11px; color: #34d399; font-weight: 700;">Otomatik ({{ $autoMap['mobile'] }} Kart)</strong>
                                                @endif
                                            </div>
                                            @if ($hasMobileOverride)
                                                <button type="button" wire:click="resetDeviceOverride('{{ $sUuid }}', 'mobile')"
                                                        style="background: #1e2430; color: #fb7185; border: 1px solid rgba(244,63,94,0.3); border-radius: 4px; padding: 3px 8px; font-size: 10px; font-weight: 700; cursor: pointer;">
                                                    🔄 Otomatiğe Dön
                                                </button>
                                            @endif
                                        </div>

                                        <div>
                                            <label style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Mobil Görünüm Biçimi</label>
                                            <div style="display: flex; gap: 4px; background: #0f1219; padding: 3px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.15);">
                                                <button type="button" wire:click="updateDevicePresentation('{{ $sUuid }}', 'mobile', 'display_variant', 'horizontal_carousel')"
                                                        class="dost-btn {{ $mobileVariantVal === 'horizontal_carousel' ? 'dost-btn-rose' : 'dost-btn-slate' }}"
                                                        style="flex: 1; padding: 5px 0; justify-content: center; font-size: 11px;">
                                                    Kaydırmalı (Slider)
                                                </button>
                                                <button type="button" wire:click="updateDevicePresentation('{{ $sUuid }}', 'mobile', 'display_variant', 'grid')"
                                                        class="dost-btn {{ $mobileVariantVal === 'grid' ? 'dost-btn-rose' : 'dost-btn-slate' }}"
                                                        style="flex: 1; padding: 5px 0; justify-content: center; font-size: 11px;">
                                                    Izgara (Grid)
                                                </button>
                                            </div>
                                        </div>

                                        <div>
                                            <label style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Mobil Kart Sayısı</label>
                                            <div style="display: flex; gap: 4px; background: #0f1219; padding: 3px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.15); align-items: center;">
                                                @foreach ([1, 2] as $cNum)
                                                    <button type="button" wire:click="updateDevicePresentation('{{ $sUuid }}', 'mobile', 'columns', {{ $cNum }})"
                                                            class="dost-btn {{ $mobileColsVal === $cNum ? 'dost-btn-rose' : 'dost-btn-slate' }}"
                                                            style="flex: 1; padding: 4px 0; justify-content: center; font-size: 11px;">
                                                        {{ $cNum }}
                                                    </button>
                                                @endforeach
                                                <div style="display: flex; align-items: center; gap: 2px; padding-left: 4px; border-left: 1px solid rgba(255,255,255,0.1);">
                                                    <span style="font-size: 9px; color: #94a3b8; font-weight: 600; margin-right: 2px;">Özel:</span>
                                                    <input type="number" min="1" max="6"
                                                           value="{{ $mobileColsVal }}"
                                                           wire:change="updateDevicePresentation('{{ $sUuid }}', 'mobile', 'columns', $event.target.value)"
                                                           class="dost-input" style="width: 48px; text-align: center; padding: 3px 2px; font-size: 11px; font-weight: 700;">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    @if (($secData['display_variant'] ?? 'grid') === 'horizontal_carousel')
                                        <div style="margin-top: 10px;">
                                            <label style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Satır Sayısı</label>
                                            <div style="display: flex; gap: 4px; background: #0f1219; padding: 3px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.15);">
                                                @foreach ([1, 2, 3] as $rNum)
                                                    <button type="button" wire:click="$set('draftSections.{{ $sIndex }}.row_count', {{ $rNum }})"
                                                            class="dost-btn {{ (int)($secData['row_count'] ?? 1) === $rNum ? 'dost-btn-rose' : 'dost-btn-slate' }}"
                                                            style="flex: 1; padding: 4px 0; justify-content: center; font-size: 11px;">
                                                        {{ $rNum }} Satır
                                                    </button>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    <div style="margin-top: 10px;">
                                        <label style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Başlık Hizalaması</label>
                                        <div style="display: flex; gap: 4px; background: #0f1219; padding: 3px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.15);">
                                            <button type="button" wire:click="$set('draftSections.{{ $sIndex }}.title_alignment', 'left')"
                                                    class="dost-btn {{ ($secData['title_alignment'] ?? 'left') === 'left' ? 'dost-btn-rose' : 'dost-btn-slate' }}"
                                                    style="flex: 1; padding: 4px 0; justify-content: center; font-size: 11px;">
                                                Sol
                                            </button>
                                            <button type="button" wire:click="$set('draftSections.{{ $sIndex }}.title_alignment', 'center')"
                                                    class="dost-btn {{ ($secData['title_alignment'] ?? 'left') === 'center' ? 'dost-btn-rose' : 'dost-btn-slate' }}"
                                                    style="flex: 1; padding: 4px 0; justify-content: center; font-size: 11px;">
                                                Orta
                                            </button>
                                            <button type="button" wire:click="$set('draftSections.{{ $sIndex }}.title_alignment', 'right')"
                                                    class="dost-btn {{ ($secData['title_alignment'] ?? 'left') === 'right' ? 'dost-btn-rose' : 'dost-btn-slate' }}"
                                                    style="flex: 1; padding: 4px 0; justify-content: center; font-size: 11px;">
                                                Sağ
                                            </button>
                                        </div>
                                    </div>

                                    <div style="margin-top: 10px;">
                                        <label style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Arka Plan Stili</label>
                                        <div style="display: flex; gap: 4px; background: #0f1219; padding: 3px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.15);">
                                            <button type="button" wire:click="$set('draftSections.{{ $sIndex }}.bg_style', 'transparent')"
                                                    class="dost-btn {{ ($secData['bg_style'] ?? 'dark') === 'transparent' ? 'dost-btn-rose' : 'dost-btn-slate' }}"
                                                    style="flex: 1; padding: 4px 0; justify-content: center; font-size: 11px;">
                                                Şeffaf
                                            </button>
                                            <button type="button" wire:click="$set('draftSections.{{ $sIndex }}.bg_style', 'dark')"
                                                    class="dost-btn {{ ($secData['bg_style'] ?? 'dark') === 'dark' ? 'dost-btn-rose' : 'dost-btn-slate' }}"
                                                    style="flex: 1; padding: 4px 0; justify-content: center; font-size: 11px;">
                                                Hafif Koyu
                                            </button>
                                            <button type="button" wire:click="$set('draftSections.{{ $sIndex }}.bg_style', 'light')"
                                                    class="dost-btn {{ ($secData['bg_style'] ?? 'dark') === 'light' ? 'dost-btn-rose' : 'dost-btn-slate' }}"
                                                    style="flex: 1; padding: 4px 0; justify-content: center; font-size: 11px;">
                                        </div>
                                    </div>

                                    @php
                                        $currentBlockType = $secData['block_type'] ?? ($secData['type'] ?? '');
                                    @endphp

                                    @if (in_array($currentBlockType, ['featured_programs', 'program_showcase', 'category_shelf', 'content_shelf']))
                                        {{-- Program Kart Genişliği --}}
                                        <div style="margin-top: 10px;">
                                            <label style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Program Kart Genişliği</label>
                                            <div style="display: flex; gap: 4px; background: #0f1219; padding: 3px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.15);">
                                                <button type="button" wire:click="$set('draftSections.{{ $sIndex }}.card_width', 'auto')"
                                                        class="dost-btn {{ ($secData['card_width'] ?? 'auto') === 'auto' ? 'dost-btn-rose' : 'dost-btn-slate' }}"
                                                        style="flex: 1; padding: 4px 0; justify-content: center; font-size: 10px;">
                                                    Otomatik
                                                </button>
                                                <button type="button" wire:click="$set('draftSections.{{ $sIndex }}.card_width', 'sm')"
                                                        class="dost-btn {{ ($secData['card_width'] ?? 'auto') === 'sm' ? 'dost-btn-rose' : 'dost-btn-slate' }}"
                                                        style="flex: 1; padding: 4px 0; justify-content: center; font-size: 10px;">
                                                    Dar (190)
                                                </button>
                                                <button type="button" wire:click="$set('draftSections.{{ $sIndex }}.card_width', 'md')"
                                                        class="dost-btn {{ ($secData['card_width'] ?? 'auto') === 'md' ? 'dost-btn-rose' : 'dost-btn-slate' }}"
                                                        style="flex: 1; padding: 4px 0; justify-content: center; font-size: 10px;">
                                                    Orta (230)
                                                </button>
                                                <button type="button" wire:click="$set('draftSections.{{ $sIndex }}.card_width', 'lg')"
                                                        class="dost-btn {{ ($secData['card_width'] ?? 'auto') === 'lg' ? 'dost-btn-rose' : 'dost-btn-slate' }}"
                                                        style="flex: 1; padding: 4px 0; justify-content: center; font-size: 10px;">
                                                    Geniş (270)
                                                </button>
                                                <button type="button" wire:click="$set('draftSections.{{ $sIndex }}.card_width', 'custom')"
                                                        class="dost-btn {{ ($secData['card_width'] ?? 'auto') === 'custom' ? 'dost-btn-rose' : 'dost-btn-slate' }}"
                                                        style="flex: 1; padding: 4px 0; justify-content: center; font-size: 10px;">
                                                    Özel
                                                </button>
                                            </div>
                                        </div>

                                        @if (($secData['card_width'] ?? 'auto') === 'custom')
                                            <div style="margin-top: 8px;">
                                                <label style="font-size: 10px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Özel Kart Genişliği (px)</label>
                                                <input type="number" min="100" max="800"
                                                       wire:model.live.debounce.300ms="draftSections.{{ $sIndex }}.card_width_custom"
                                                       class="dost-input" placeholder="230">
                                            </div>
                                        @endif

                                        {{-- Kart Hizalama --}}
                                        @if (($secData['card_width'] ?? 'auto') !== 'auto')
                                            <div style="margin-top: 10px;">
                                                <label style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Kart Hizalaması</label>
                                                <div style="display: flex; gap: 4px; background: #0f1219; padding: 3px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.15);">
                                                    <button type="button" wire:click="$set('draftSections.{{ $sIndex }}.card_alignment', 'left')"
                                                            class="dost-btn {{ ($secData['card_alignment'] ?? 'left') === 'left' ? 'dost-btn-rose' : 'dost-btn-slate' }}"
                                                            style="flex: 1; padding: 4px 0; justify-content: center; font-size: 11px;">
                                                        Sol
                                                    </button>
                                                    <button type="button" wire:click="$set('draftSections.{{ $sIndex }}.card_alignment', 'center')"
                                                            class="dost-btn {{ ($secData['card_alignment'] ?? 'left') === 'center' ? 'dost-btn-rose' : 'dost-btn-slate' }}"
                                                            style="flex: 1; padding: 4px 0; justify-content: center; font-size: 11px;">
                                                        Orta
                                                    </button>
                                                </div>
                                            </div>
                                        @endif

                                        {{-- Kart Oranı / Aspect Ratio --}}
                                        <div style="margin-top: 10px;">
                                            <label style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Kart Oranı (Aspect Ratio)</label>
                                            <div style="display: flex; gap: 4px; background: #0f1219; padding: 3px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.15);">
                                                <button type="button" wire:click="$set('draftSections.{{ $sIndex }}.card_ratio', 'default')"
                                                        class="dost-btn {{ ($secData['card_ratio'] ?? 'default') === 'default' ? 'dost-btn-rose' : 'dost-btn-slate' }}"
                                                        style="flex: 1; padding: 4px 0; justify-content: center; font-size: 10px;">
                                                    3:4
                                                </button>
                                                <button type="button" wire:click="$set('draftSections.{{ $sIndex }}.card_ratio', '16:9')"
                                                        class="dost-btn {{ ($secData['card_ratio'] ?? 'default') === '16:9' ? 'dost-btn-rose' : 'dost-btn-slate' }}"
                                                        style="flex: 1; padding: 4px 0; justify-content: center; font-size: 10px;">
                                                    16:9 (Yassı)
                                                </button>
                                                <button type="button" wire:click="$set('draftSections.{{ $sIndex }}.card_ratio', '4:3')"
                                                        class="dost-btn {{ ($secData['card_ratio'] ?? 'default') === '4:3' ? 'dost-btn-rose' : 'dost-btn-slate' }}"
                                                        style="flex: 1; padding: 4px 0; justify-content: center; font-size: 10px;">
                                                    4:3
                                                </button>
                                                <button type="button" wire:click="$set('draftSections.{{ $sIndex }}.card_ratio', '1:1')"
                                                        class="dost-btn {{ ($secData['card_ratio'] ?? 'default') === '1:1' ? 'dost-btn-rose' : 'dost-btn-slate' }}"
                                                        style="flex: 1; padding: 4px 0; justify-content: center; font-size: 10px;">
                                                    1:1 (Kare)
                                                </button>
                                            </div>
                                        </div>
                                    @endif

                                    @if (in_array($currentBlockType, ['program_showcase', 'category_shelf', 'content_shelf']))
                                        <div style="margin-top: 10px; display: flex; align-items: center; justify-content: space-between; background: #0f1219; padding: 8px 12px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.15);">
                                            <div>
                                                <span style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block;">Program İsimlerini Göster</span>
                                                <span style="font-size: 9px; color: #94a3b8;">Kart altında program başlığının görünüp görünmeyeceği</span>
                                            </div>
                                            <button type="button" wire:click="$set('draftSections.{{ $sIndex }}.show_program_titles', {{ !($secData['show_program_titles'] ?? true) ? 'true' : 'false' }})"
                                                    class="dost-btn {{ ($secData['show_program_titles'] ?? true) ? 'dost-btn-rose' : 'dost-btn-slate' }}"
                                                    style="padding: 4px 10px; font-size: 11px;">
                                                {{ ($secData['show_program_titles'] ?? true) ? 'AÇIK' : 'KAPALI' }}
                                            </button>
                                        </div>
                                    @endif

                                    @if (in_array($currentBlockType, ['video_collection', 'content_shelf', 'category_shelf']))
                                        <div style="margin-top: 10px; display: flex; align-items: center; justify-content: space-between; background: #0f1219; padding: 8px 12px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.15);">
                                            <div>
                                                <span style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block;">Video İsimlerini Göster</span>
                                                <span style="font-size: 9px; color: #94a3b8;">Kart altında video başlığının görünüp görünmeyeceği</span>
                                            </div>
                                            <button type="button" wire:click="$set('draftSections.{{ $sIndex }}.show_video_titles', {{ !($secData['show_video_titles'] ?? true) ? 'true' : 'false' }})"
                                                    class="dost-btn {{ ($secData['show_video_titles'] ?? true) ? 'dost-btn-rose' : 'dost-btn-slate' }}"
                                                    style="padding: 4px 10px; font-size: 11px;">
                                                {{ ($secData['show_video_titles'] ?? true) ? 'AÇIK' : 'KAPALI' }}
                                            </button>
                                        </div>
                                    @endif
                                    {{-- SECTION COLOR OVERRIDE CONTROLS --}}
                                    <div class="dost-card" style="margin-top: 10px; border: 1px solid rgba(244,63,94,0.3); background: rgba(15, 23, 42, 0.6);">
                                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                                            <div>
                                                <label style="font-size: 11px; font-weight: 700; color: #f43f5e; display: block;">Bu Bölüme Özel Renk Kullan</label>
                                                <span style="font-size: 9px; color: #94a3b8;">Section ton katmanı ve vurgu renklerini özelleştirin</span>
                                            </div>
                                            <button type="button"
                                                    wire:click="$set('draftSections.{{ $sIndex }}.use_custom_colors', {{ ! ($draftSections[$sIndex]['use_custom_colors'] ?? false) ? 'true' : 'false' }})"
                                                    style="padding: 3px 8px; border-radius: 9999px; font-size: 10px; font-weight: 700; border: none; cursor: pointer; {{ ! empty($draftSections[$sIndex]['use_custom_colors'] ?? false) ? 'background: #34d399; color: #0f172a;' : 'background: #475569; color: #cbd5e1;' }}">
                                                {{ ! empty($draftSections[$sIndex]['use_custom_colors'] ?? false) ? 'AÇIK' : 'KAPALI (GLOBAL)' }}
                                            </button>
                                        </div>

                                        @if (! empty($draftSections[$sIndex]['use_custom_colors'] ?? false))
                                            <div style="display: flex; flex-direction: column; gap: 8px; margin-top: 8px; border-top: 1px solid rgba(255,255,255,0.08); padding-top: 8px;">
                                                {{-- Arka Plan --}}
                                                <div>
                                                    <label style="font-size: 10px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 2px;">Section Arka Plan Rengi (BG)</label>
                                                    <div style="display: flex; gap: 6px; align-items: center;">
                                                        <input type="color"
                                                               value="{{ $draftSections[$sIndex]['section_background'] ?? '#030712' }}"
                                                               wire:change="$set('draftSections.{{ $sIndex }}.section_background', $event.target.value)"
                                                               style="width: 28px; height: 28px; border: none; border-radius: 4px; cursor: pointer; background: none;">
                                                        <input type="text"
                                                               wire:model.live.debounce.300ms="draftSections.{{ $sIndex }}.section_background"
                                                               class="dost-input" style="flex: 1; font-size: 11px;" placeholder="#030712">
                                                    </div>
                                                </div>

                                                {{-- Yüzey Rengi --}}
                                                <div>
                                                    <label style="font-size: 10px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 2px;">Section Yüzey Rengi (Surface)</label>
                                                    <div style="display: flex; gap: 6px; align-items: center;">
                                                        <input type="color"
                                                               value="{{ $draftSections[$sIndex]['section_surface'] ?? '#0f172a' }}"
                                                               wire:change="$set('draftSections.{{ $sIndex }}.section_surface', $event.target.value)"
                                                               style="width: 28px; height: 28px; border: none; border-radius: 4px; cursor: pointer; background: none;">
                                                        <input type="text"
                                                               wire:model.live.debounce.300ms="draftSections.{{ $sIndex }}.section_surface"
                                                               class="dost-input" style="flex: 1; font-size: 11px;" placeholder="#0f172a">
                                                    </div>
                                                </div>

                                                {{-- Vurgu Rengi --}}
                                                <div>
                                                    <label style="font-size: 10px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 2px;">Section Vurgu Rengi (Accent)</label>
                                                    <div style="display: flex; gap: 6px; align-items: center;">
                                                        <input type="color"
                                                               value="{{ $draftSections[$sIndex]['section_accent'] ?? '#f43f5e' }}"
                                                               wire:change="$set('draftSections.{{ $sIndex }}.section_accent', $event.target.value)"
                                                               style="width: 28px; height: 28px; border: none; border-radius: 4px; cursor: pointer; background: none;">
                                                        <input type="text"
                                                               wire:model.live.debounce.300ms="draftSections.{{ $sIndex }}.section_accent"
                                                               class="dost-input" style="flex: 1; font-size: 11px;" placeholder="#f43f5e">
                                                    </div>
                                                </div>

                                                {{-- Metin Rengi --}}
                                                <div>
                                                    <label style="font-size: 10px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 2px;">Section Metin Rengi (Text)</label>
                                                    <div style="display: flex; gap: 6px; align-items: center;">
                                                        <input type="color"
                                                               value="{{ $draftSections[$sIndex]['section_text'] ?? '#f8fafc' }}"
                                                               wire:change="$set('draftSections.{{ $sIndex }}.section_text', $event.target.value)"
                                                               style="width: 28px; height: 28px; border: none; border-radius: 4px; cursor: pointer; background: none;">
                                                        <input type="text"
                                                               wire:model.live.debounce.300ms="draftSections.{{ $sIndex }}.section_text"
                                                               class="dost-input" style="flex: 1; font-size: 11px;" placeholder="#f8fafc">
                                                    </div>
                                                </div>

                                                {{-- Soluk Metin Rengi --}}
                                                <div>
                                                    <label style="font-size: 10px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 2px;">Soluk Metin Rengi (Muted)</label>
                                                    <div style="display: flex; gap: 6px; align-items: center;">
                                                        <input type="color"
                                                               value="{{ $draftSections[$sIndex]['section_muted'] ?? '#94a3b8' }}"
                                                               wire:change="$set('draftSections.{{ $sIndex }}.section_muted', $event.target.value)"
                                                               style="width: 28px; height: 28px; border: none; border-radius: 4px; cursor: pointer; background: none;">
                                                        <input type="text"
                                                               wire:model.live.debounce.300ms="draftSections.{{ $sIndex }}.section_muted"
                                                               class="dost-input" style="flex: 1; font-size: 11px;" placeholder="#94a3b8">
                                                    </div>
                                                </div>

                                                {{-- Border Rengi --}}
                                                <div>
                                                    <label style="font-size: 10px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 2px;">Sınır / Çizgi Rengi (Border)</label>
                                                    <input type="text"
                                                           wire:model.live.debounce.300ms="draftSections.{{ $sIndex }}.section_border"
                                                           class="dost-input" style="font-size: 11px;" placeholder="rgba(148,163,184,0.15)">
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- TAB 3: ÖLÇÜLER VE TİPOGRAFİ SEKMESİ --}}
                            <div x-show="activeTab === 'dimensions'" style="display: flex; flex-direction: column; gap: 10px;">
                                {{-- Device Context Switcher inside Sidebar --}}
                                <div style="background: #0f1219; padding: 3px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.12); display: flex; gap: 3px;">
                                    <button type="button" @click="activeDevice = 'desktop'"
                                            :style="activeDevice === 'desktop' ? 'background: #e11d48; color: #ffffff;' : 'color: #94a3b8; background: transparent;'"
                                            style="flex: 1; padding: 6px 0; border: none; border-radius: 6px; font-size: 11px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 4px;">
                                        <span>🖥</span>
                                        <span>Masaüstü</span>
                                    </button>
                                    <button type="button" @click="activeDevice = 'tablet'"
                                            :style="activeDevice === 'tablet' ? 'background: #e11d48; color: #ffffff;' : 'color: #94a3b8; background: transparent;'"
                                            style="flex: 1; padding: 6px 0; border: none; border-radius: 6px; font-size: 11px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 4px;">
                                        <span>▣</span>
                                        <span>Tablet</span>
                                    </button>
                                    <button type="button" @click="activeDevice = 'mobile'"
                                            :style="activeDevice === 'mobile' ? 'background: #e11d48; color: #ffffff;' : 'color: #94a3b8; background: transparent;'"
                                            style="flex: 1; padding: 6px 0; border: none; border-radius: 6px; font-size: 11px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 4px;">
                                        <span>📱</span>
                                        <span>Mobil</span>
                                    </button>
                                </div>

                                @foreach (['desktop' => 'Masaüstü', 'tablet' => 'Tablet', 'mobile' => 'Mobil'] as $devKey => $devLabel)
                                    @php
                                        $dParams = $respResolved[$devKey] ?? [];
                                    @endphp
                                    <div x-show="activeDevice === '{{ $devKey }}'" class="dost-card" style="display: flex; flex-direction: column; gap: 12px;">
                                        <div style="font-size: 11px; font-weight: 700; color: #f43f5e; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 4px;">
                                            {{ $devLabel }} Tipografi ve Spacing Ölçüleri
                                        </div>

                                        {{-- Heading Size --}}
                                        <div>
                                            <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                                                <label style="font-size: 11px; font-weight: 600; color: #cbd5e1;">Bölüm Başlık Boyutu (Heading)</label>
                                                <span style="font-size: 10px; color: #f43f5e; font-weight: 700;">{{ $dParams['heading_size'] ?? 24 }} px</span>
                                            </div>
                                            <div style="display: flex; gap: 6px; align-items: center;">
                                                <input type="number" min="12" max="64"
                                                       value="{{ $dParams['heading_size'] ?? 24 }}"
                                                       wire:change="updateDevicePresentation('{{ $sUuid }}', '{{ $devKey }}', 'heading_size', $event.target.value)"
                                                       class="dost-input" style="flex: 1; text-align: center; font-size: 11px; font-weight: 700;">
                                            </div>
                                        </div>

                                        {{-- Subtitle Size --}}
                                        <div>
                                            <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                                                <label style="font-size: 11px; font-weight: 600; color: #cbd5e1;">Alt Başlık Boyutu (Subtitle)</label>
                                                <span style="font-size: 10px; color: #f43f5e; font-weight: 700;">{{ $dParams['subtitle_size'] ?? 14 }} px</span>
                                            </div>
                                            <div style="display: flex; gap: 6px; align-items: center;">
                                                <input type="number" min="10" max="32"
                                                       value="{{ $dParams['subtitle_size'] ?? 14 }}"
                                                       wire:change="updateDevicePresentation('{{ $sUuid }}', '{{ $devKey }}', 'subtitle_size', $event.target.value)"
                                                       class="dost-input" style="flex: 1; text-align: center; font-size: 11px; font-weight: 700;">
                                            </div>
                                        </div>

                                        {{-- CTA Link Size --}}
                                        <div>
                                            <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                                                <label style="font-size: 11px; font-weight: 600; color: #cbd5e1;">CTA Buton / Link Boyutu</label>
                                                <span style="font-size: 10px; color: #f43f5e; font-weight: 700;">{{ $dParams['cta_size'] ?? 13 }} px</span>
                                            </div>
                                            <div style="display: flex; gap: 6px; align-items: center;">
                                                <input type="number" min="10" max="24"
                                                       value="{{ $dParams['cta_size'] ?? 13 }}"
                                                       wire:change="updateDevicePresentation('{{ $sUuid }}', '{{ $devKey }}', 'cta_size', $event.target.value)"
                                                       class="dost-input" style="flex: 1; text-align: center; font-size: 11px; font-weight: 700;">
                                            </div>
                                        </div>

                                        {{-- Card Title Size --}}
                                        <div>
                                            <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                                                <label style="font-size: 11px; font-weight: 600; color: #cbd5e1;">Kart Başlık Boyutu</label>
                                                <span style="font-size: 10px; color: #f43f5e; font-weight: 700;">{{ $dParams['card_title_size'] ?? 14 }} px</span>
                                            </div>
                                            <div style="display: flex; gap: 6px; align-items: center;">
                                                <input type="number" min="10" max="28"
                                                       value="{{ $dParams['card_title_size'] ?? 14 }}"
                                                       wire:change="updateDevicePresentation('{{ $sUuid }}', '{{ $devKey }}', 'card_title_size', $event.target.value)"
                                                       class="dost-input" style="flex: 1; text-align: center; font-size: 11px; font-weight: 700;">
                                            </div>
                                        </div>

                                        {{-- Section Padding Top & Bottom --}}
                                        <div>
                                            <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                                                <label style="font-size: 11px; font-weight: 600; color: #cbd5e1;">Dikey Bölüm Boşluğu (Top / Bottom)</label>
                                                <span style="font-size: 10px; color: #f43f5e; font-weight: 700;">{{ $dParams['section_padding_top'] ?? 24 }} px</span>
                                            </div>
                                            <div style="display: flex; gap: 6px; align-items: center;">
                                                <span style="font-size: 9px; color: #94a3b8;">Üst:</span>
                                                <input type="number" min="0" max="150"
                                                       value="{{ $dParams['section_padding_top'] ?? 24 }}"
                                                       wire:change="updateDevicePresentation('{{ $sUuid }}', '{{ $devKey }}', 'section_padding_top', $event.target.value)"
                                                       class="dost-input" style="flex: 1; text-align: center; font-size: 11px; font-weight: 700;">
                                                <span style="font-size: 9px; color: #94a3b8;">Alt:</span>
                                                <input type="number" min="0" max="150"
                                                       value="{{ $dParams['section_padding_bottom'] ?? 24 }}"
                                                       wire:change="updateDevicePresentation('{{ $sUuid }}', '{{ $devKey }}', 'section_padding_bottom', $event.target.value)"
                                                       class="dost-input" style="flex: 1; text-align: center; font-size: 11px; font-weight: 700;">
                                            </div>
                                        </div>

                                        {{-- Section Padding X & Card Gap --}}
                                        <div>
                                            <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                                                <label style="font-size: 11px; font-weight: 600; color: #cbd5e1;">Yatay Boşluk & Kart Aralığı</label>
                                                <span style="font-size: 10px; color: #f43f5e; font-weight: 700;">Gap: {{ \App\Services\Home\HomepageBlockRegistry::resolveGapPx($dParams['gap_size'] ?? 16) }} px</span>
                                            </div>
                                            <div style="display: flex; gap: 6px; align-items: center;">
                                                <span style="font-size: 9px; color: #94a3b8;">Yatay PX:</span>
                                                <input type="number" min="0" max="64"
                                                       value="{{ $dParams['section_padding_x'] ?? 12 }}"
                                                       wire:change="updateDevicePresentation('{{ $sUuid }}', '{{ $devKey }}', 'section_padding_x', $event.target.value)"
                                                       class="dost-input" style="flex: 1; text-align: center; font-size: 11px; font-weight: 700;">
                                                <span style="font-size: 9px; color: #94a3b8;">Kart Gap:</span>
                                                <input type="number" min="0" max="64"
                                                       value="{{ \App\Services\Home\HomepageBlockRegistry::resolveGapPx($dParams['gap_size'] ?? 16) }}"
                                                       wire:change="updateDevicePresentation('{{ $sUuid }}', '{{ $devKey }}', 'gap_size', $event.target.value)"
                                                       class="dost-input" style="flex: 1; text-align: center; font-size: 11px; font-weight: 700;">
                                            </div>
                                        </div>

                                        @if ($blockType === 'today_schedule')
                                            <div style="background: rgba(15, 18, 25, 0.8); border: 1px solid rgba(244,63,94,0.3); border-radius: 6px; padding: 8px; display: flex; flex-direction: column; gap: 8px; margin-top: 4px;">
                                                <div style="font-size: 10px; font-weight: 700; color: #fb7185; text-transform: uppercase;">
                                                    Yayın Akışı {{ $devLabel }} Özel Ölçüleri
                                                </div>

                                                <div style="display: flex; align-items: center; justify-content: space-between; gap: 6px;">
                                                    <label style="font-size: 10px; color: #cbd5e1;">Saat Yazı Boyutu</label>
                                                    <input type="number" min="9" max="24"
                                                           value="{{ $dParams['schedule_time_size'] ?? 13 }}"
                                                           wire:change="updateDevicePresentation('{{ $sUuid }}', '{{ $devKey }}', 'schedule_time_size', $event.target.value)"
                                                           class="dost-input" style="width: 54px; text-align: center; font-size: 10px; font-weight: 700;">
                                                </div>

                                                <div style="display: flex; align-items: center; justify-content: space-between; gap: 6px;">
                                                    <label style="font-size: 10px; color: #cbd5e1;">Program Adı Yazı Boyutu</label>
                                                    <input type="number" min="9" max="24"
                                                           value="{{ $dParams['schedule_program_size'] ?? 13 }}"
                                                           wire:change="updateDevicePresentation('{{ $sUuid }}', '{{ $devKey }}', 'schedule_program_size', $event.target.value)"
                                                           class="dost-input" style="width: 54px; text-align: center; font-size: 10px; font-weight: 700;">
                                                </div>

                                                <div style="display: flex; align-items: center; justify-content: space-between; gap: 6px;">
                                                    <label style="font-size: 10px; color: #cbd5e1;">Kutu Yüksekliği (px)</label>
                                                    <input type="number" min="40" max="150"
                                                           value="{{ $dParams['schedule_item_height'] ?? 64 }}"
                                                           wire:change="updateDevicePresentation('{{ $sUuid }}', '{{ $devKey }}', 'schedule_item_height', $event.target.value)"
                                                           class="dost-input" style="width: 54px; text-align: center; font-size: 10px; font-weight: 700;">
                                                </div>

                                                <div style="display: flex; align-items: center; justify-content: space-between; gap: 6px;">
                                                    <label style="font-size: 10px; color: #cbd5e1;">Öğe Aralığı & İç Boşluk</label>
                                                    <div style="display: flex; gap: 4px;">
                                                        <input type="number" min="0" max="32"
                                                               value="{{ $dParams['schedule_item_gap'] ?? 8 }}"
                                                               wire:change="updateDevicePresentation('{{ $sUuid }}', '{{ $devKey }}', 'schedule_item_gap', $event.target.value)"
                                                               class="dost-input" style="width: 42px; text-align: center; font-size: 10px; font-weight: 700;" placeholder="Gap">
                                                        <input type="number" min="0" max="32"
                                                               value="{{ $dParams['schedule_horizontal_padding'] ?? 10 }}"
                                                               wire:change="updateDevicePresentation('{{ $sUuid }}', '{{ $devKey }}', 'schedule_horizontal_padding', $event.target.value)"
                                                               class="dost-input" style="width: 42px; text-align: center; font-size: 10px; font-weight: 700;" placeholder="Px">
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>

                            {{-- TAB 4: GELİŞMİŞ SEKMESİ --}}
                            <div x-show="activeTab === 'advanced'" style="display: flex; flex-direction: column; gap: 10px;">
                                <div class="dost-card">
                                    <div style="display: flex; align-items: center; justify-content: space-between;">
                                        <label style="font-size: 11px; font-weight: 600; color: #cbd5e1;">Otomatik Oynat (Autoplay)</label>
                                        <button type="button"
                                                wire:click="$set('draftSections.{{ $sIndex }}.autoplay', {{ ! ($draftSections[$sIndex]['autoplay'] ?? true) ? 'true' : 'false' }})"
                                                style="padding: 3px 8px; border-radius: 9999px; font-size: 10px; font-weight: 700; border: none; cursor: pointer; {{ ! empty($draftSections[$sIndex]['autoplay'] ?? true) ? 'background: #34d399; color: #0f172a;' : 'background: #475569; color: #cbd5e1;' }}">
                                            {{ ! empty($draftSections[$sIndex]['autoplay'] ?? true) ? 'AÇIK' : 'KAPALI' }}
                                        </button>
                                    </div>

                                    <div style="display: flex; align-items: center; justify-content: space-between;">
                                        <label style="font-size: 11px; font-weight: 600; color: #cbd5e1;">Sonsuz Döngü (Loop)</label>
                                        <button type="button"
                                                wire:click="$set('draftSections.{{ $sIndex }}.loop', {{ ! ($draftSections[$sIndex]['loop'] ?? true) ? 'true' : 'false' }})"
                                                style="padding: 3px 8px; border-radius: 9999px; font-size: 10px; font-weight: 700; border: none; cursor: pointer; {{ ! empty($draftSections[$sIndex]['loop'] ?? true) ? 'background: #34d399; color: #0f172a;' : 'background: #475569; color: #cbd5e1;' }}">
                                            {{ ! empty($draftSections[$sIndex]['loop'] ?? true) ? 'AÇIK' : 'KAPALI' }}
                                        </button>
                                    </div>

                                    <div style="display: flex; align-items: center; justify-content: space-between;">
                                        <label style="font-size: 11px; font-weight: 600; color: #cbd5e1;">Navigasyon Okları</label>
                                        <button type="button"
                                                wire:click="$set('draftSections.{{ $sIndex }}.show_arrows', {{ ! ($draftSections[$sIndex]['show_arrows'] ?? true) ? 'true' : 'false' }})"
                                                style="padding: 3px 8px; border-radius: 9999px; font-size: 10px; font-weight: 700; border: none; cursor: pointer; {{ ! empty($draftSections[$sIndex]['show_arrows'] ?? true) ? 'background: #34d399; color: #0f172a;' : 'background: #475569; color: #cbd5e1;' }}">
                                            {{ ! empty($draftSections[$sIndex]['show_arrows'] ?? true) ? 'AÇIK' : 'KAPALI' }}
                                        </button>
                                    </div>

                                    <div style="display: flex; align-items: center; justify-content: space-between;">
                                        <label style="font-size: 11px; font-weight: 600; color: #cbd5e1;">Sayfa Noktaları (Dots)</label>
                                        <button type="button"
                                                wire:click="$set('draftSections.{{ $sIndex }}.show_dots', {{ ! ($draftSections[$sIndex]['show_dots'] ?? false) ? 'true' : 'false' }})"
                                                style="padding: 3px 8px; border-radius: 9999px; font-size: 10px; font-weight: 700; border: none; cursor: pointer; {{ ! empty($draftSections[$sIndex]['show_dots'] ?? false) ? 'background: #34d399; color: #0f172a;' : 'background: #475569; color: #cbd5e1;' }}">
                                            {{ ! empty($draftSections[$sIndex]['show_dots'] ?? false) ? 'AÇIK' : 'KAPALI' }}
                                        </button>
                                    </div>

                                    @if ($blockType === 'today_schedule')
                                        <div style="display: flex; align-items: center; justify-content: space-between;">
                                            <label style="font-size: 11px; font-weight: 600; color: #cbd5e1;">Aktif Yayına Otomatik Git</label>
                                            <button type="button"
                                                    wire:click="$set('draftSections.{{ $sIndex }}.auto_scroll_to_now', {{ ! ($draftSections[$sIndex]['auto_scroll_to_now'] ?? true) ? 'true' : 'false' }})"
                                                    style="padding: 3px 8px; border-radius: 9999px; font-size: 10px; font-weight: 700; border: none; cursor: pointer; {{ ! empty($draftSections[$sIndex]['auto_scroll_to_now'] ?? true) ? 'background: #34d399; color: #0f172a;' : 'background: #475569; color: #cbd5e1;' }}">
                                                {{ ! empty($draftSections[$sIndex]['auto_scroll_to_now'] ?? true) ? 'AÇIK' : 'KAPALI' }}
                                            </button>
                                        </div>
                                    @endif

                                    <hr style="border: 0; border-top: 1px solid rgba(255,255,255,0.08); margin: 4px 0;">

                                    {{-- Responsive Breakpoint Overrides Toggle --}}
                                    <div style="display: flex; align-items: center; justify-content: space-between;">
                                        <div>
                                            <label style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block;">Cihaz Bazlı Ayarları Özelleştir</label>
                                            <span style="font-size: 9px; color: #94a3b8;">Tablet ve Mobil için ayrı sunum ayarları</span>
                                        </div>
                                        <button type="button"
                                                wire:click="$set('draftSections.{{ $sIndex }}.custom_responsive', {{ ! ($draftSections[$sIndex]['custom_responsive'] ?? false) ? 'true' : 'false' }})"
                                                style="padding: 3px 8px; border-radius: 9999px; font-size: 10px; font-weight: 700; border: none; cursor: pointer; {{ ! empty($draftSections[$sIndex]['custom_responsive'] ?? false) ? 'background: #34d399; color: #0f172a;' : 'background: #475569; color: #cbd5e1;' }}">
                                            {{ ! empty($draftSections[$sIndex]['custom_responsive'] ?? false) ? 'AÇIK' : 'KAPALI (OTOMATİK)' }}
                                        </button>
                                    </div>

                                    @if (! empty($draftSections[$sIndex]['custom_responsive'] ?? false))
                                        <div x-data="{ deviceSubTab: 'tablet' }" style="background: rgba(15, 18, 25, 0.8); padding: 10px; border-radius: 8px; border: 1px solid rgba(244,63,94,0.3); display: flex; flex-direction: column; gap: 10px;">

                                            <div style="display: flex; background: #0f1219; padding: 2px; border-radius: 6px; gap: 2px;">
                                                <button type="button" @click="deviceSubTab = 'tablet'"
                                                        :style="deviceSubTab === 'tablet' ? 'background: #e11d48; color: #ffffff;' : 'color: #94a3b8;'"
                                                        style="flex: 1; padding: 4px 0; border: none; border-radius: 4px; font-size: 10px; font-weight: 700; cursor: pointer; text-align: center;">
                                                    ▣ TABLET (768px)
                                                </button>
                                                <button type="button" @click="deviceSubTab = 'mobile'"
                                                        :style="deviceSubTab === 'mobile' ? 'background: #e11d48; color: #ffffff;' : 'color: #94a3b8;'"
                                                        style="flex: 1; padding: 4px 0; border: none; border-radius: 4px; font-size: 10px; font-weight: 700; cursor: pointer; text-align: center;">
                                                    📱 MOBİL (390px)
                                                </button>
                                            </div>

                                            {{-- TABLET OVERRIDES --}}
                                            <div x-show="deviceSubTab === 'tablet'" style="display: flex; flex-direction: column; gap: 8px;">
                                                <div>
                                                    <label style="font-size: 10px; font-weight: 600; color: #94a3b8; display: block; margin-bottom: 4px;">Tablet Görünüm Biçimi</label>
                                                    <select wire:model.live="draftSections.{{ $sIndex }}.responsive_settings.tablet.display_variant" class="dost-select">
                                                        <option value="grid">Izgara (Grid)</option>
                                                        <option value="horizontal_carousel">Kaydırmalı (Slider)</option>
                                                    </select>
                                                </div>

                                                <div>
                                                    <label style="font-size: 10px; font-weight: 600; color: #94a3b8; display: block; margin-bottom: 4px;">Tablet Kart Yoğunluğu (Sütun)</label>
                                                    <select wire:model.live="draftSections.{{ $sIndex }}.responsive_settings.tablet.columns" class="dost-select">
                                                        @foreach ([1 => '1 Kart', 2 => '2 Kart', 3 => '3 Kart', 4 => '4 Kart'] as $tKey => $tLabel)
                                                            <option value="{{ $tKey }}">{{ $tLabel }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div>
                                                    <label style="font-size: 10px; font-weight: 600; color: #94a3b8; display: block; margin-bottom: 4px;">Tablet Kart Aralığı (Gap px)</label>
                                                    <input type="number" min="0" max="64"
                                                           wire:model.live.debounce.300ms="draftSections.{{ $sIndex }}.responsive_settings.tablet.gap_size"
                                                           class="dost-input" placeholder="Örn: 12">
                                                </div>

                                                <div style="display: flex; align-items: center; justify-content: space-between;">
                                                    <label style="font-size: 10px; font-weight: 600; color: #94a3b8;">Tablet Navigasyon Okları</label>
                                                    <button type="button"
                                                            wire:click="$set('draftSections.{{ $sIndex }}.responsive_settings.tablet.show_arrows', {{ ! ($draftSections[$sIndex]['responsive_settings']['tablet']['show_arrows'] ?? true) ? 'true' : 'false' }})"
                                                            style="padding: 2px 6px; border-radius: 9999px; font-size: 9px; font-weight: 700; border: none; cursor: pointer; {{ ! empty($draftSections[$sIndex]['responsive_settings']['tablet']['show_arrows'] ?? true) ? 'background: #34d399; color: #0f172a;' : 'background: #475569; color: #cbd5e1;' }}">
                                                        {{ ! empty($draftSections[$sIndex]['responsive_settings']['tablet']['show_arrows'] ?? true) ? 'AÇIK' : 'KAPALI' }}
                                                    </button>
                                                </div>
                                            </div>

                                            {{-- MOBİL OVERRIDES --}}
                                            <div x-show="deviceSubTab === 'mobile'" style="display: flex; flex-direction: column; gap: 8px;">
                                                <div>
                                                    <label style="font-size: 10px; font-weight: 600; color: #94a3b8; display: block; margin-bottom: 4px;">Mobil Görünüm Biçimi</label>
                                                    <select wire:model.live="draftSections.{{ $sIndex }}.responsive_settings.mobile.display_variant" class="dost-select">
                                                        <option value="horizontal_carousel">Kaydırmalı (Slider)</option>
                                                        <option value="grid">Izgara (Grid)</option>
                                                    </select>
                                                </div>

                                                <div>
                                                    <label style="font-size: 10px; font-weight: 600; color: #94a3b8; display: block; margin-bottom: 4px;">Mobil Kart Yoğunluğu (Sütun)</label>
                                                    <select wire:model.live="draftSections.{{ $sIndex }}.responsive_settings.mobile.columns" class="dost-select">
                                                        @foreach ([1 => '1 Kart', 2 => '2 Kart'] as $mKey => $mLabel)
                                                            <option value="{{ $mKey }}">{{ $mLabel }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div>
                                                    <label style="font-size: 10px; font-weight: 600; color: #94a3b8; display: block; margin-bottom: 4px;">Mobil Kart Aralığı (Gap px)</label>
                                                    <input type="number" min="0" max="48"
                                                           wire:model.live.debounce.300ms="draftSections.{{ $sIndex }}.responsive_settings.mobile.gap_size"
                                                           class="dost-input" placeholder="Örn: 10">
                                                </div>

                                                <div style="display: flex; align-items: center; justify-content: space-between;">
                                                    <label style="font-size: 10px; font-weight: 600; color: #94a3b8;">Mobil Navigasyon Okları</label>
                                                    <button type="button"
                                                            wire:click="$set('draftSections.{{ $sIndex }}.responsive_settings.mobile.show_arrows', {{ ! ($draftSections[$sIndex]['responsive_settings']['mobile']['show_arrows'] ?? false) ? 'true' : 'false' }})"
                                                            style="padding: 2px 6px; border-radius: 9999px; font-size: 9px; font-weight: 700; border: none; cursor: pointer; {{ ! empty($draftSections[$sIndex]['responsive_settings']['mobile']['show_arrows'] ?? false) ? 'background: #34d399; color: #0f172a;' : 'background: #475569; color: #cbd5e1;' }}">
                                                        {{ ! empty($draftSections[$sIndex]['responsive_settings']['mobile']['show_arrows'] ?? false) ? 'AÇIK' : 'KAPALI' }}
                                                    </button>
                                                </div>
                                            </div>

                                        </div>
                                    @endif

                                </div>
                            </div>
                        @endif
                    @endif

                @elseif ($contextMode === 'fixed')
                    {{-- Context C: Fixed Global Area Presentation Editor (Clean 4-Tab Structure) --}}
                    @php
                        $area = $fixedAreaName ?: 'hero';
                        $areaTitle = match($area) {
                            'header' => 'Üst Alan (Header)',
                            'hero' => 'Manşet (Hero Banner)',
                            'footer' => 'Alt Bilgi & İletişim (Footer)',
                            default => 'Sabit Bölüm',
                        };
                    @endphp

                    <div style="display: flex; flex-direction: column; gap: 8px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px; margin-bottom: 4px;">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <button type="button"
                                    wire:click="selectListContext"
                                    style="background: none; border: none; color: #f43f5e; font-size: 12px; font-weight: 600; cursor: pointer; padding: 0; display: flex; align-items: center; gap: 4px;">
                                <span>&larr; Bölüm Listesine Dön</span>
                            </button>
                            <span class="dost-badge dost-badge-emerald" style="background: rgba(234, 179, 8, 0.15); color: #eab308; border-color: rgba(234, 179, 8, 0.3); font-size: 9px;">
                                🔒 Sabit Bölüm
                            </span>
                        </div>

                        <div style="background: rgba(15, 18, 25, 0.6); padding: 8px 10px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.08);">
                            <strong style="font-size: 12px; font-weight: 700; color: #ffffff; display: block; line-height: 1.2;">
                                {{ $areaTitle }}
                            </strong>
                            <p style="font-size: 10px; color: #94a3b8; margin: 4px 0 0 0; line-height: 1.4;">
                                Bu bölüm silinemez veya taşınamaz. Tasarım ayarlarını düzenleyebilirsiniz.
                            </p>
                        </div>
                    </div>

                    <div x-data="{ activeTab: 'content' }" style="display: flex; flex-direction: column; gap: 12px;">

                        {{-- Segmented 4-Tab Switcher --}}
                        <div style="display: flex; background: rgba(15, 18, 25, 0.95); padding: 3px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.12); gap: 2px;">
                            <button type="button" @click="activeTab = 'content'"
                                    :style="activeTab === 'content' ? 'background: #e11d48; color: #ffffff;' : 'color: #94a3b8;'"
                                    style="flex: 1; padding: 6px 0; border: none; border-radius: 5px; font-size: 10px; font-weight: 700; cursor: pointer; text-align: center;">
                                İÇERİK
                            </button>
                            <button type="button" @click="activeTab = 'layout'"
                                    :style="activeTab === 'layout' ? 'background: #e11d48; color: #ffffff;' : 'color: #94a3b8;'"
                                    style="flex: 1; padding: 6px 0; border: none; border-radius: 5px; font-size: 10px; font-weight: 700; cursor: pointer; text-align: center;">
                                YERLEŞİM
                            </button>
                            <button type="button" @click="activeTab = 'dimensions'"
                                    :style="activeTab === 'dimensions' ? 'background: #e11d48; color: #ffffff;' : 'color: #94a3b8;'"
                                    style="flex: 1; padding: 6px 0; border: none; border-radius: 5px; font-size: 10px; font-weight: 700; cursor: pointer; text-align: center;">
                                ÖLÇÜLER
                            </button>
                            <button type="button" @click="activeTab = 'advanced'"
                                    :style="activeTab === 'advanced' ? 'background: #e11d48; color: #ffffff;' : 'color: #94a3b8;'"
                                    style="flex: 1; padding: 6px 0; border: none; border-radius: 5px; font-size: 10px; font-weight: 700; cursor: pointer; text-align: center;">
                                GELİŞMİŞ
                            </button>
                        </div>

                        {{-- TAB 1: İÇERİK --}}
                        <div x-show="activeTab === 'content'" style="display: flex; flex-direction: column; gap: 10px;">
                            <div class="dost-card">
                                <p style="font-size: 11px; color: #cbd5e1; line-height: 1.5; margin: 0;">
                                    @if ($area === 'header')
                                        İçerik verileri (Logo, Menü, Canlı Yayın Butonu, Sosyal Medya Linkleri) <strong>Site Ayarları</strong> ve <strong>Menü Yönetimi</strong> üzerinden çekilmektedir.
                                    @elseif ($area === 'hero')
                                        <div style="display: flex; flex-direction: column; gap: 10px;">
                                            <p style="font-size: 11px; color: #cbd5e1; line-height: 1.5; margin: 0 0 4px 0;">
                                                Manşet banner içerikleri ve öne çıkan yayınlar <strong>Öne Çıkan Programlar (is_featured)</strong> ve <strong>Banner Yönetimi</strong> üzerinden dinamik çekilir.
                                            </p>

                                            <div style="font-size: 10px; font-weight: 700; color: #f43f5e; text-transform: uppercase; letter-spacing: 0.05em; margin-top: 4px;">
                                                Görünüm & Element Görünürlüğü
                                            </div>

                                            {{-- Toggle 1: Program Adını Göster --}}
                                            <div style="display: flex; align-items: center; justify-content: space-between; background: rgba(15, 18, 25, 0.6); padding: 8px 10px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.08);">
                                                <span style="font-size: 11px; font-weight: 600; color: #e2e8f0;">Program Adını Göster</span>
                                                <button type="button"
                                                        wire:click="updateFixedSetting('hero', 'show_title', {{ ! ($fixedSectionsSettings['hero']['show_title'] ?? true) ? 'true' : 'false' }})"
                                                        style="padding: 3px 8px; border-radius: 9999px; font-size: 10px; font-weight: 700; border: none; cursor: pointer; {{ ! empty($fixedSectionsSettings['hero']['show_title'] ?? true) ? 'background: #34d399; color: #0f172a;' : 'background: #475569; color: #cbd5e1;' }}">
                                                    {{ ! empty($fixedSectionsSettings['hero']['show_title'] ?? true) ? 'AÇIK' : 'KAPALI' }}
                                                </button>
                                            </div>

                                            {{-- Toggle 2: Yayın Gün/Saatini Göster --}}
                                            <div style="display: flex; align-items: center; justify-content: space-between; background: rgba(15, 18, 25, 0.6); padding: 8px 10px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.08);">
                                                <span style="font-size: 11px; font-weight: 600; color: #e2e8f0;">Yayın Gün/Saatini Göster</span>
                                                <button type="button"
                                                        wire:click="updateFixedSetting('hero', 'show_schedule', {{ ! ($fixedSectionsSettings['hero']['show_schedule'] ?? true) ? 'true' : 'false' }})"
                                                        style="padding: 3px 8px; border-radius: 9999px; font-size: 10px; font-weight: 700; border: none; cursor: pointer; {{ ! empty($fixedSectionsSettings['hero']['show_schedule'] ?? true) ? 'background: #34d399; color: #0f172a;' : 'background: #475569; color: #cbd5e1;' }}">
                                                    {{ ! empty($fixedSectionsSettings['hero']['show_schedule'] ?? true) ? 'AÇIK' : 'KAPALI' }}
                                                </button>
                                            </div>

                                            {{-- Toggle 3: Hero Açıklamasını Göster --}}
                                            <div style="display: flex; align-items: center; justify-content: space-between; background: rgba(15, 18, 25, 0.6); padding: 8px 10px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.08);">
                                                <span style="font-size: 11px; font-weight: 600; color: #e2e8f0;">Hero Açıklamasını Göster</span>
                                                <button type="button"
                                                        wire:click="updateFixedSetting('hero', 'show_description', {{ ! ($fixedSectionsSettings['hero']['show_description'] ?? true) ? 'true' : 'false' }})"
                                                        style="padding: 3px 8px; border-radius: 9999px; font-size: 10px; font-weight: 700; border: none; cursor: pointer; {{ ! empty($fixedSectionsSettings['hero']['show_description'] ?? true) ? 'background: #34d399; color: #0f172a;' : 'background: #475569; color: #cbd5e1;' }}">
                                                    {{ ! empty($fixedSectionsSettings['hero']['show_description'] ?? true) ? 'AÇIK' : 'KAPALI' }}
                                                </button>
                                            </div>

                                            {{-- Overlay Control: Hero Görsel Karartması --}}
                                            <div style="display: flex; flex-direction: column; gap: 6px; background: rgba(15, 18, 25, 0.6); padding: 8px 10px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.08);">
                                                <span style="font-size: 11px; font-weight: 600; color: #e2e8f0;">Hero Görsel Karartması</span>
                                                @php
                                                    $currentOverlay = (string) ($fixedSectionsSettings['hero']['overlay_mode'] ?? 'none');
                                                @endphp
                                                <div style="display: flex; gap: 4px; background: #0f1219; padding: 3px; border-radius: 6px;">
                                                    <button type="button"
                                                            wire:click="updateFixedSetting('hero', 'overlay_mode', 'none')"
                                                            style="flex: 1; padding: 4px 6px; border-radius: 4px; font-size: 10px; font-weight: 700; border: none; cursor: pointer; {{ $currentOverlay === 'none' ? 'background: #f43f5e; color: #ffffff;' : 'background: transparent; color: #cbd5e1;' }}">
                                                        Kapalı
                                                    </button>
                                                    <button type="button"
                                                            wire:click="updateFixedSetting('hero', 'overlay_mode', 'soft')"
                                                            style="flex: 1; padding: 4px 6px; border-radius: 4px; font-size: 10px; font-weight: 700; border: none; cursor: pointer; {{ $currentOverlay === 'soft' ? 'background: #f43f5e; color: #ffffff;' : 'background: transparent; color: #cbd5e1;' }}">
                                                        Hafif
                                                    </button>
                                                    <button type="button"
                                                            wire:click="updateFixedSetting('hero', 'overlay_mode', 'strong')"
                                                            style="flex: 1; padding: 4px 6px; border-radius: 4px; font-size: 10px; font-weight: 700; border: none; cursor: pointer; {{ $currentOverlay === 'strong' ? 'background: #f43f5e; color: #ffffff;' : 'background: transparent; color: #cbd5e1;' }}">
                                                        Güçlü
                                                    </button>
                                                </div>
                                            </div>

                                            {{-- Hero Görselleri ve Mobil Uyumluluk Bilgisi --}}
                                            @php
                                                $heroPrograms = $this->heroPrograms;
                                            @endphp

                                            <div style="display: flex; flex-direction: column; gap: 8px; background: rgba(15, 18, 25, 0.6); padding: 10px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.08); margin-top: 4px;">
                                                <div style="display: flex; align-items: center; justify-content: space-between;">
                                                    <span style="font-size: 11px; font-weight: 700; color: #cbd5e1; display: flex; align-items: center; gap: 4px;">
                                                        <span>📱</span> Hero Görselleri & Mobil Kullanım
                                                    </span>
                                                    <span class="dost-badge dost-badge-slate" style="font-size: 9px;">Otomatik Fallback</span>
                                                </div>

                                                <p style="font-size: 10px; color: #94a3b8; margin: 0; line-height: 1.4;">
                                                    Masaüstü ve tablet cihazlarda <strong>Yatay Hero Görseli</strong>, 768px altı mobil ekranlarda varsa <strong>Mobil Hero Görseli (1080x1350)</strong> kullanılır. Mobil görsel tanımlı değilse yatay görsel otomatik ölçeklenir.
                                                </p>

                                                @if ($heroPrograms->isNotEmpty())
                                                    <div style="display: flex; flex-direction: column; gap: 6px; margin-top: 4px;">
                                                        @foreach ($heroPrograms as $hProg)
                                                            @php
                                                                $hasMobImg = filled($hProg->mobile_hero_image);
                                                            @endphp
                                                            <div style="display: flex; align-items: center; justify-content: space-between; background: #0f1219; padding: 6px 10px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.06);">
                                                                <span style="font-size: 11px; font-weight: 600; color: #f8fafc;">
                                                                    {{ $hProg->name }}
                                                                </span>
                                                                @if ($hasMobImg)
                                                                    <span class="dost-badge dost-badge-emerald" style="font-size: 9px;">
                                                                        ✓ Özel Mobil Hero Var
                                                                    </span>
                                                                @else
                                                                    <span class="dost-badge dost-badge-slate" style="font-size: 9px;">
                                                                        Yatay Hero Kullanılıyor
                                                                    </span>
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @else
                                        İletişim, adres, telefon, e-posta ve Google Harita verileri <strong>Sayfa & Site Ayarları</strong> veritabanından çekilir. Görsel ve hizalama ayarlarını yan sekmelerden değiştirebilirsiniz.
                                    @endif
                                </p>
                            </div>
                        </div>

                        {{-- TAB 2: YERLEŞİM --}}
                        <div x-show="activeTab === 'layout'" style="display: flex; flex-direction: column; gap: 10px;">
                            <div class="dost-card">
                                @if ($area === 'footer')
                                    <div>
                                        <label style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Bilgi Kartları Arası Boşluk (px)</label>
                                        <input type="number" wire:model.live="fixedSectionsSettings.footer.contact_cards_gap" class="dost-input" placeholder="24">
                                    </div>
                                    <div>
                                        <label style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Kartlar &rarr; Harita Boşluğu (px)</label>
                                        <input type="number" wire:model.live="fixedSectionsSettings.footer.contact_cards_to_map_gap" class="dost-input" placeholder="28">
                                    </div>
                                    <div>
                                        <label style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Harita Başlığı &rarr; Harita Boşluğu (px)</label>
                                        <input type="number" wire:model.live="fixedSectionsSettings.footer.map_title_gap" class="dost-input" placeholder="14">
                                    </div>
                                    <div>
                                        <label style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Harita &rarr; Form Boşluğu (px)</label>
                                        <input type="number" wire:model.live="fixedSectionsSettings.footer.map_form_gap" class="dost-input" placeholder="56">
                                    </div>
                                    <div>
                                        <label style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Form Alanları Arası Boşluk (px)</label>
                                        <input type="number" wire:model.live="fixedSectionsSettings.footer.form_field_gap" class="dost-input" placeholder="20">
                                    </div>
                                @elseif ($area === 'header')
                                    <div>
                                        <label style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Header Yüksekliği (px)</label>
                                        <input type="number" wire:model.live="fixedSectionsSettings.header.header_height" class="dost-input" placeholder="80">
                                    </div>
                                    <div>
                                        <label style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Logo Boyutu (px)</label>
                                        <input type="number" wire:model.live="fixedSectionsSettings.header.logo_size" class="dost-input" placeholder="48">
                                    </div>
                                    <div>
                                        <label style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Menü Öğeleri Arası Boşluk (px)</label>
                                        <input type="number" wire:model.live="fixedSectionsSettings.header.nav_spacing" class="dost-input" placeholder="24">
                                    </div>
                                @else
                                    <div>
                                        <label style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Manşet Yüksekliği (px)</label>
                                        <input type="number" wire:model.live="fixedSectionsSettings.hero.hero_height" class="dost-input" placeholder="480">
                                    </div>
                                    <div>
                                        <label style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Öğeler Arası Boşluk (px)</label>
                                        <input type="number" wire:model.live="fixedSectionsSettings.hero.gap_size" class="dost-input" placeholder="24">
                                    </div>
                                    <div>
                                        <label style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Metin Dikey Kaydırma (px)</label>
                                        <input type="number" wire:model.live="fixedSectionsSettings.hero.text_offset" class="dost-input" placeholder="0">
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- TAB 3: ÖLÇÜLER --}}
                        <div x-show="activeTab === 'dimensions'" style="display: flex; flex-direction: column; gap: 10px;">
                            <div class="dost-card">
                                @if ($area === 'footer')
                                    <div>
                                        <label style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Kart Kenarlığı (Border)</label>
                                        <select wire:model.live="fixedSectionsSettings.footer.card_border" class="dost-input" style="background: #0f1219; color: #ffffff;">
                                            <option value="none">Yok (Sınır Çizgisi Yok)</option>
                                            <option value="light">Hafif (Mikro Border)</option>
                                            <option value="prominent">Belirgin (Düşük Kontrast)</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Kart Yüzeyi (Background)</label>
                                        <select wire:model.live="fixedSectionsSettings.footer.card_surface" class="dost-input" style="background: #0f1219; color: #ffffff;">
                                            <option value="transparent">Şeffaf</option>
                                            <option value="soft">Yumuşak Koyu Lacivert</option>
                                            <option value="prominent">Belirgin Koyu Lacivert</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Harita Yüksekliği (px)</label>
                                        <input type="number" wire:model.live="fixedSectionsSettings.footer.map_height" class="dost-input" placeholder="320">
                                    </div>
                                    <div>
                                        <label style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Harita Radius (px)</label>
                                        <input type="number" wire:model.live="fixedSectionsSettings.footer.map_radius" class="dost-input" placeholder="16">
                                    </div>
                                    <div>
                                        <label style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Bilgi Kartları Radius (px)</label>
                                        <input type="number" wire:model.live="fixedSectionsSettings.footer.card_radius" class="dost-input" placeholder="16">
                                    </div>
                                    <div>
                                        <label style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Form Kapsayıcı Radius (px)</label>
                                        <input type="number" wire:model.live="fixedSectionsSettings.footer.form_radius" class="dost-input" placeholder="16">
                                    </div>
                                    <div>
                                        <label style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Üst Boşluk - Padding Top (px)</label>
                                        <input type="number" wire:model.live="fixedSectionsSettings.footer.section_padding_top" class="dost-input" placeholder="48">
                                    </div>
                                    <div>
                                        <label style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Alt Boşluk - Padding Bottom (px)</label>
                                        <input type="number" wire:model.live="fixedSectionsSettings.footer.section_padding_bottom" class="dost-input" placeholder="56">
                                    </div>
                                    <div>
                                        <label style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Yan Boşluklar - Padding X (px)</label>
                                        <input type="number" wire:model.live="fixedSectionsSettings.footer.section_padding_x" class="dost-input" placeholder="32">
                                    </div>
                                    <div>
                                        <label style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Input Yüksekliği (px)</label>
                                        <input type="number" wire:model.live="fixedSectionsSettings.footer.input_height" class="dost-input" placeholder="48">
                                    </div>
                                    <div>
                                        <label style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Input Yan Boşluk - Padding X (px)</label>
                                        <input type="number" wire:model.live="fixedSectionsSettings.footer.input_padding_x" class="dost-input" placeholder="16">
                                    </div>
                                    <div>
                                        <label style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Input Dikey Boşluk - Padding Y (px)</label>
                                        <input type="number" wire:model.live="fixedSectionsSettings.footer.input_padding_y" class="dost-input" placeholder="12">
                                    </div>
                                    <div>
                                        <label style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Mesaj Alanı Yüksekliği (px)</label>
                                        <input type="number" wire:model.live="fixedSectionsSettings.footer.textarea_height" class="dost-input" placeholder="140">
                                    </div>
                                    <div>
                                        <label style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Başlık Metin Boyutu (px)</label>
                                        <input type="number" wire:model.live="fixedSectionsSettings.footer.heading_size" class="dost-input" placeholder="24">
                                    </div>
                                    <div>
                                        <label style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Etiket Metin Boyutu (px)</label>
                                        <input type="number" wire:model.live="fixedSectionsSettings.footer.label_size" class="dost-input" placeholder="12">
                                    </div>
                                @elseif ($area === 'header')
                                    <div>
                                        <label style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Üst Boşluk - Padding Top (px)</label>
                                        <input type="number" wire:model.live="fixedSectionsSettings.header.section_padding_top" class="dost-input" placeholder="16">
                                    </div>
                                    <div>
                                        <label style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Alt Boşluk - Padding Bottom (px)</label>
                                        <input type="number" wire:model.live="fixedSectionsSettings.header.section_padding_bottom" class="dost-input" placeholder="16">
                                    </div>
                                    <div>
                                        <label style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Yan Boşluklar - Padding X (px)</label>
                                        <input type="number" wire:model.live="fixedSectionsSettings.header.section_padding_x" class="dost-input" placeholder="32">
                                    </div>
                                @else
                                    <div>
                                        <label style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Üst Boşluk - Padding Top (px)</label>
                                        <input type="number" wire:model.live="fixedSectionsSettings.hero.padding_top" class="dost-input" placeholder="32">
                                    </div>
                                    <div>
                                        <label style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Alt Boşluk - Padding Bottom (px)</label>
                                        <input type="number" wire:model.live="fixedSectionsSettings.hero.padding_bottom" class="dost-input" placeholder="32">
                                    </div>
                                    <div>
                                        <label style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block; margin-bottom: 4px;">Kart Radius (px)</label>
                                        <input type="number" wire:model.live="fixedSectionsSettings.hero.card_radius" class="dost-input" placeholder="16">
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- TAB 4: GELİŞMİŞ --}}
                        <div x-show="activeTab === 'advanced'" style="display: flex; flex-direction: column; gap: 10px;">
                            <div class="dost-card">
                                <label style="font-size: 11px; font-weight: 600; color: #cbd5e1; display: block;">Cihaz Bazlı Duyarlı (Responsive) Özelleştirme</label>
                                <p style="font-size: 10px; color: #94a3b8; margin: 4px 0 8px 0;">
                                    Üstteki cihaz değiştiriciden Masaüstü, Tablet veya Mobil seçerek bağımsız tasarım ayarları tanımlayabilirsiniz.
                                </p>
                                <button type="button"
                                        wire:click="resetFixedDeviceOverride('{{ $area }}', activeDevice)"
                                        class="dost-btn dost-btn-slate" style="font-size: 10px; width: 100%; justify-content: center;">
                                    Aktif Cihaz Özelleştirmesini Sıfırla
                                </button>
                            </div>
                        </div>

                    </div>
                @endif
            </aside>

            {{-- 100% Remaining Width/Height Preview Canvas --}}
            <main class="dost-editor-canvas" style="display: flex; flex-direction: column; width: 100%; height: 100%; overflow: hidden; background: #090b10;">
                {{-- Compact Device Switcher Toolbar --}}
                <div class="dost-canvas-toolbar" style="height: 38px; min-height: 38px; background: #0f1219; border-bottom: 1px solid rgba(255,255,255,0.08); display: flex; align-items: center; justify-content: space-between; padding: 0 16px; flex-shrink: 0; z-index: 10;">
                    <div style="display: flex; align-items: center; gap: 4px;">
                        <button type="button"
                                @click="activeDevice = 'desktop'"
                                :style="activeDevice === 'desktop' ? 'background: #e11d48; color: #ffffff; border-color: #f43f5e;' : 'background: #1e2430; color: #cbd5e1; border-color: rgba(255,255,255,0.1);'"
                                class="dost-btn" style="padding: 3px 10px; font-size: 11px;">
                            <span>🖥 Masaüstü</span>
                            <span style="font-size: 9px; opacity: 0.7; margin-left: 2px;">1440 px</span>
                        </button>
                        <button type="button"
                                @click="activeDevice = 'tablet'"
                                :style="activeDevice === 'tablet' ? 'background: #e11d48; color: #ffffff; border-color: #f43f5e;' : 'background: #1e2430; color: #cbd5e1; border-color: rgba(255,255,255,0.1);'"
                                class="dost-btn" style="padding: 3px 10px; font-size: 11px;">
                            <span>▣ Tablet</span>
                            <span style="font-size: 9px; opacity: 0.7; margin-left: 2px;">768 px</span>
                        </button>
                        <button type="button"
                                @click="activeDevice = 'mobile'"
                                :style="activeDevice === 'mobile' ? 'background: #e11d48; color: #ffffff; border-color: #f43f5e;' : 'background: #1e2430; color: #cbd5e1; border-color: rgba(255,255,255,0.1);'"
                                class="dost-btn" style="padding: 3px 10px; font-size: 11px;">
                            <span>📱 Mobil</span>
                            <span style="font-size: 9px; opacity: 0.7; margin-left: 2px;">390 px</span>
                        </button>
                    </div>

                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="font-size: 10px; color: #94a3b8; font-weight: 600;" x-text="activeDevice === 'desktop' ? 'Viewport: 1440 px (Masaüstü)' : (activeDevice === 'tablet' ? 'Viewport: 768 px (Tablet)' : 'Viewport: 390 px (Mobil)')"></span>
                        <span class="dost-badge dost-badge-slate" style="font-size: 9px;">Gerçek CSS Responsive</span>
                    </div>
                </div>

                {{-- Centered Responsive Viewport Canvas Wrapper --}}
                <div style="flex: 1; min-height: 0; width: 100%; height: calc(100% - 38px); overflow: hidden; background: #07080c; display: flex; align-items: center; justify-content: center; padding: 0;">
                    <div :style="activeDevice === 'desktop' ? 'width: 100%; height: 100%; transition: width 0.3s ease;' : (activeDevice === 'tablet' ? 'width: 768px; height: 100%; transition: width 0.3s ease; box-shadow: 0 0 40px rgba(0,0,0,0.8); border-left: 1px solid rgba(255,255,255,0.1); border-right: 1px solid rgba(255,255,255,0.1);' : 'width: 390px; height: 100%; transition: width 0.3s ease; box-shadow: 0 0 40px rgba(0,0,0,0.8); border-left: 1px solid rgba(255,255,255,0.1); border-right: 1px solid rgba(255,255,255,0.1);')"
                         style="position: relative; height: 100%;">
                        <iframe id="editor-preview-frame"
                                src="{{ $this->previewFrameUrl }}"
                                class="dost-editor-preview-frame"></iframe>
                    </div>
                </div>
            </main>
        </div>
    </div>

    {{-- PostMessage Listener script --}}
    <script>
        window.addEventListener('message', function(event) {
            if (event.data && event.data.type === 'select-block' && event.data.uuid) {
                @this.call('selectBlock', event.data.uuid);
            } else if (event.data && event.data.type === 'select-fixed') {
                @this.call('selectFixedArea', event.data.area || 'hero');
            }
        });
    </script>
</div>
