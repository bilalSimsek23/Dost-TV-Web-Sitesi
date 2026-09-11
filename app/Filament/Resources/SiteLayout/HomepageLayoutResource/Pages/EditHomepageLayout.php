<?php

namespace App\Filament\Resources\SiteLayout\HomepageLayoutResource\Pages;

use App\Filament\Resources\SiteLayout\HomepageLayoutResource\HomepageLayoutResource;
use App\Models\Category;
use App\Models\Episode;
use App\Models\HomepageLayout;
use App\Models\Program;
use App\Models\ProgramCollection;
use App\Models\VideoCollection;
use App\Models\YoutubeChannel;
use App\Services\Home\HomepageBlockRegistry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EditHomepageLayout extends EditRecord
{
    protected static string $resource = HomepageLayoutResource::class;

    protected string $view = 'filament.pages.site-layout.editor-shell';

    public \Illuminate\Database\Eloquent\Model|string|int|null $record;

    public array $draftSections = [];

    public ?string $selectedBlockUuid = null;

    public ?string $newBlockType = null;

    public string $contextMode = 'list'; // 'list' (overview), 'edit' / 'block' (block editor) veya 'fixed' (fixed area notice)

    public string $editTargetMode = 'homepage'; // 'homepage' or 'collection'

    public ?int $previewProgramId = null;

    public ?array $collectionPublicSettings = null;

    public string $fixedAreaName = 'hero';

    public array $fixedSectionsSettings = [];

    public ?int $selectedHeroProgramId = null;

    public function getHeroProgramsProperty(): \Illuminate\Support\Collection
    {
        return app(\App\Services\Home\HomepageDataService::class)->resolveHeroPrograms();
    }

    public function getSelectedHeroProgramProperty(): ?Program
    {
        $heroPrograms = $this->heroPrograms;
        if ($heroPrograms->isEmpty()) {
            return null;
        }

        if ($this->selectedHeroProgramId) {
            $found = $heroPrograms->firstWhere('id', $this->selectedHeroProgramId);
            if ($found) {
                return $found;
            }
        }

        return $heroPrograms->first();
    }

    public function selectHeroProgram(int|string|null $programId): void
    {
        $this->selectedHeroProgramId = $programId ? (int) $programId : null;
        $this->dispatch('hero-program-selected', programId: $this->selectedHeroProgramId);
    }

    public function updateHeroProgramFocusCoords(int $programId, string $device, int $x, int $y): void
    {
        $program = Program::query()->find($programId);
        if (! $program) {
            return;
        }

        $settings = (array) ($program->hero_focus_settings ?? []);

        if (! in_array($device, ['desktop', 'tablet', 'mobile'], true)) {
            $device = 'desktop';
        }

        $settings["{$device}_x"] = max(0, min(100, (int) $x));
        $settings["{$device}_y"] = max(0, min(100, (int) $y));

        $program->hero_focus_settings = $settings;
        $program->save();

        \App\Support\SiteCache::forgetHomeHeroPrograms();

        $this->selectedHeroProgramId = $programId;
        $this->dispatch('preview-updated');
    }

    public function updateHeroProgramFocusAxis(int $programId, string $device, string $axis, int $value): void
    {
        $program = Program::query()->find($programId);
        if (! $program) {
            return;
        }

        $settings = (array) ($program->hero_focus_settings ?? []);

        if (! in_array($device, ['desktop', 'tablet', 'mobile'], true)) {
            $device = 'desktop';
        }
        if (! in_array($axis, ['x', 'y'], true)) {
            $axis = 'x';
        }

        $settings["{$device}_{$axis}"] = max(0, min(100, (int) $value));

        $program->hero_focus_settings = $settings;
        $program->save();

        \App\Support\SiteCache::forgetHomeHeroPrograms();

        $this->selectedHeroProgramId = $programId;
        $this->dispatch('preview-updated');
    }

    public function getDefaultFixedSettings(): array
    {
        return [
            'header' => [
                'header_height' => 80,
                'section_padding_top' => 16,
                'section_padding_bottom' => 16,
                'section_padding_x' => 32,
                'logo_size' => 48,
                'nav_spacing' => 24,
            ],
            'hero' => [
                'hero_height' => 480,
                'padding_top' => 32,
                'padding_bottom' => 32,
                'gap_size' => 24,
                'card_radius' => 16,
                'text_offset' => 0,
                'show_title' => true,
                'show_schedule' => true,
                'show_description' => true,
                'overlay_mode' => 'none',
                'hero_focus_desktop_x' => 50,
                'hero_focus_desktop_y' => 30,
                'hero_focus_tablet_x' => 50,
                'hero_focus_tablet_y' => 30,
                'hero_focus_mobile_x' => 50,
                'hero_focus_mobile_y' => 30,
            ],
            'footer' => [
                'section_padding_top' => 48,
                'section_padding_bottom' => 56,
                'section_padding_x' => 32,
                'contact_cards_gap' => 24,
                'contact_cards_to_map_gap' => 28,
                'map_title_gap' => 14,
                'map_height' => 320,
                'map_radius' => 16,
                'map_form_gap' => 56,
                'form_field_gap' => 20,
                'input_height' => 48,
                'input_padding_x' => 16,
                'input_padding_y' => 12,
                'textarea_height' => 140,
                'card_radius' => 16,
                'form_radius' => 16,
                'heading_size' => 24,
                'label_size' => 12,
                'card_border' => 'light',
                'card_surface' => 'soft',
            ],
        ];
    }

    public function mount(int|string $record): void
    {
        parent::mount($record);
        $drafts = $this->record->draft_sections ?? [];
        $fixedSettings = $drafts['_fixed_settings'] ?? [];
        $this->fixedSectionsSettings = array_replace_recursive($this->getDefaultFixedSettings(), $fixedSettings);

        unset($drafts['_fixed_settings']);
        $this->draftSections = array_values($drafts);
    }

    public function selectBlock(?string $uuid): void
    {
        $this->selectedBlockUuid = $uuid;
        $this->contextMode = $uuid !== null ? 'edit' : 'list';
        $this->editTargetMode = 'homepage';
        $this->loadCollectionPublicSettings();
    }

    public function setEditTargetMode(string $mode): void
    {
        if ($mode === 'collection') {
            $block = $this->selectedBlock;
            if (! $block) {
                return;
            }

            $blockType = $block['block_type'] ?? '';
            $colId = null;

            if ($blockType === 'program_showcase') {
                $colId = $block['program_collection_id'] ?? null;
            } elseif ($blockType === 'video_collection') {
                $colId = $block['collection_id'] ?? ($block['video_collection_id'] ?? null);
            }

            if (! $colId) {
                Notification::make()
                    ->title('Koleksiyon Seçilmedi')
                    ->body('Tümünü Gör Sayfası moduna geçmek için önce bir ' . ($blockType === 'program_showcase' ? 'Program' : 'Video') . ' Koleksiyonu seçin.')
                    ->warning()
                    ->send();
                return;
            }

            $this->loadCollectionPublicSettings();
            $this->editTargetMode = 'collection';
            $this->dispatch('preview-updated');
            return;
        }

        $this->editTargetMode = 'homepage';
        $this->dispatch('preview-updated');
    }

    public function loadCollectionPublicSettings(): void
    {
        $block = $this->selectedBlock;
        if (! $block) {
            $this->collectionPublicSettings = null;
            return;
        }

        $blockType = $block['block_type'] ?? '';

        if ($blockType === 'program_showcase') {
            $colId = $block['program_collection_id'] ?? null;
            if ($colId) {
                $col = ProgramCollection::find($colId);
                if ($col) {
                    $this->collectionPublicSettings = $col->resolvePublicSettings();
                    return;
                }
            }
        } elseif ($blockType === 'video_collection') {
            $colId = $block['collection_id'] ?? ($block['video_collection_id'] ?? null);
            if ($colId) {
                $col = VideoCollection::find($colId);
                if ($col) {
                    $this->collectionPublicSettings = $col->resolvePublicSettings();
                    return;
                }
            }
        }

        $this->collectionPublicSettings = null;
    }

    public function updateCollectionPublicSetting(string $key, $value): void
    {
        $block = $this->selectedBlock;
        if (! $block) {
            return;
        }

        $blockType = $block['block_type'] ?? '';
        $col = null;

        if ($blockType === 'program_showcase') {
            $colId = $block['program_collection_id'] ?? null;
            if ($colId) {
                $col = ProgramCollection::find($colId);
            }
        } elseif ($blockType === 'video_collection') {
            $colId = $block['collection_id'] ?? ($block['video_collection_id'] ?? null);
            if ($colId) {
                $col = VideoCollection::find($colId);
            }
        }

        if ($col) {
            $settings = $col->resolvePublicSettings();
            data_set($settings, $key, $value);
            $col->public_settings = $settings;
            $col->save();
            $this->collectionPublicSettings = $settings;
            $this->dispatch('preview-updated');
        }
    }

    public function getPreviewFrameUrlProperty(): string
    {
        $pageType = $this->record->page_type ?? 'home';

        if ($pageType === 'program_detail') {
            $program = null;
            if ($this->previewProgramId) {
                $program = Program::query()->find($this->previewProgramId);
            }
            if (! $program) {
                $program = Program::query()
                    ->where('is_active', true)
                    ->where('show_on_public', true)
                    ->first();
            }
            if ($program) {
                return route('programs.show', [
                    'program' => $program,
                    'preview_layout_id' => $this->record->id,
                ]);
            }
        }

        if ($pageType === 'program_collection' && $this->record->target_id) {
            $col = ProgramCollection::find($this->record->target_id);
            if ($col) {
                return route('program.collections.show', [
                    'slug' => $col->slug,
                    'preview_layout_id' => $this->record->id,
                ]);
            }
        }

        if ($pageType === 'video_collection' && $this->record->target_id) {
            $col = VideoCollection::find($this->record->target_id);
            if ($col) {
                return route('collections.show', [
                    'collection' => $col->slug,
                    'preview_layout_id' => $this->record->id,
                ]);
            }
        }

        if ($this->editTargetMode === 'collection') {
            $block = $this->selectedBlock;
            if ($block) {
                $blockType = $block['block_type'] ?? '';
                if ($blockType === 'program_showcase') {
                    $colId = $block['program_collection_id'] ?? null;
                    if ($colId) {
                        $col = ProgramCollection::find($colId);
                        if ($col) {
                            return route('program.collections.show', $col->slug);
                        }
                    }
                } elseif ($blockType === 'video_collection') {
                    $colId = $block['collection_id'] ?? ($block['video_collection_id'] ?? null);
                    if ($colId) {
                        $col = VideoCollection::find($colId);
                        if ($col) {
                            return route('collections.show', $col->slug);
                        }
                    }
                }
            }
        }

        return route('admin.site-layout.preview-frame', $this->record);
    }

    public function editTargetDetailPage(): void
    {
        $block = $this->selectedBlock;
        if (! $block) {
            return;
        }

        $blockType = $block['block_type'] ?? '';
        $targetType = null;
        $targetId = null;

        if ($blockType === 'program_showcase') {
            $targetType = 'program_collection';
            $targetId = $block['program_collection_id'] ?? null;
        } elseif ($blockType === 'video_collection') {
            $targetType = 'video_collection';
            $targetId = $block['collection_id'] ?? ($block['video_collection_id'] ?? null);
        }

        if (! $targetType || ! $targetId) {
            Notification::make()
                ->title('Hedef Detay Bulunamadı')
                ->body('Detay sayfasını açmak için geçerli bir koleksiyon seçilmelidir.')
                ->warning()
                ->send();
            return;
        }

        $targetLayout = \App\Services\Page\PageDiscoveryService::findOrCreateLayoutForTarget($targetType, (int) $targetId);

        $this->redirect(HomepageLayoutResource::getUrl('edit', ['record' => $targetLayout]));
    }

    public function selectFixedArea(string $area = 'hero'): void
    {
        $this->fixedAreaName = $area;
        $this->selectedBlockUuid = null;
        $this->contextMode = 'fixed';
    }

    public function showOverview(): void
    {
        $this->selectedBlockUuid = null;
        $this->contextMode = 'list';
    }

    public function selectListContext(): void
    {
        $this->showOverview();
    }

    public function deleteBlock(string $uuid): void
    {
        if (in_array($uuid, ['header', 'hero', 'footer'], true)) {
            Notification::make()
                ->title('Sabit Bölüm Silinemez')
                ->body('Header, Hero ve Footer alanları yapısal olarak kilitlidir, silinemez.')
                ->warning()
                ->send();
            return;
        }

        $this->removeBlock($uuid);
    }

    public function reorderBlocks(int $fromIndex, int $toIndex): void
    {
        if (! isset($this->draftSections[$fromIndex]) || ! isset($this->draftSections[$toIndex])) {
            return;
        }

        $movedItem = array_splice($this->draftSections, $fromIndex, 1)[0];
        array_splice($this->draftSections, $toIndex, 0, [$movedItem]);

        $this->saveDraft(false);
        $this->dispatch('preview-updated');
    }

    public function moveBlock(string $uuid, string $direction): void
    {
        if (in_array($uuid, ['header', 'hero', 'footer'], true)) {
            return;
        }

        $index = null;
        foreach ($this->draftSections as $idx => $section) {
            if (($section['uuid'] ?? ($section['key'] ?? '')) === $uuid) {
                $index = $idx;
                break;
            }
        }

        if ($index === null) {
            return;
        }

        $targetIndex = $direction === 'up' ? $index - 1 : $index + 1;
        if ($targetIndex < 0 || $targetIndex >= count($this->draftSections)) {
            return;
        }

        $temp = $this->draftSections[$index];
        $this->draftSections[$index] = $this->draftSections[$targetIndex];
        $this->draftSections[$targetIndex] = $temp;

        $this->saveDraft(false);
        $this->dispatch('preview-updated');
    }

    public function removeBlock(string $uuid): void
    {
        if (in_array($uuid, ['header', 'hero', 'footer'], true)) {
            return;
        }

        $this->draftSections = array_values(array_filter(
            $this->draftSections,
            fn ($section) => ($section['uuid'] ?? ($section['key'] ?? '')) !== $uuid
        ));

        if ($this->selectedBlockUuid === $uuid) {
            $this->selectedBlockUuid = null;
            $this->contextMode = 'list';
        }

        $this->saveDraft(false);
        $this->dispatch('preview-updated');

        Notification::make()
            ->title('Bölüm Silindi')
            ->body('Seçilen bölüm taslaktan çıkarıldı.')
            ->success()
            ->send();
    }

    public function addBlock(?string $blockType = null): void
    {
        $blockType = $blockType ?: $this->newBlockType;

        if (empty($blockType)) {
            return;
        }

        $pageType = $this->record->page_type ?? 'home';
        $allowedTypes = HomepageBlockRegistry::getBlockTypesForPageType($pageType);
        if (! isset($allowedTypes[$blockType])) {
            return;
        }

        $newUuid = (string) Str::uuid();
        $defaultTitle = $allowedTypes[$blockType] ?? 'Yeni Bölüm';

        $newSection = [
            'uuid' => $newUuid,
            'block_type' => $blockType,
            'visible' => true,
            'title' => $blockType === 'category_shelf' ? '' : ($blockType === 'today_schedule' ? 'Bugünün Yayın Akışı' : ($blockType === 'content_shelf' ? 'Yeni İçerik Rafı' : $defaultTitle)),
            'show_title' => true,
            'title_alignment' => 'left',
            'subtitle' => null,
            'display_variant' => 'horizontal_carousel',
            'desktop_columns' => 5,
            'desktop_columns_custom' => 5,
            'tablet_columns' => 2,
            'mobile_columns' => 1,
            'row_count' => 1,
            'content_limit' => 'all',
            'content_limit_custom' => 12,
            'padding_y' => 'md',
            'gap_size' => 'md',
            'category_id' => null,
            'shelf_type' => 'program', // 'program' or 'video'
            'source_mode' => $blockType === 'content_shelf' ? 'manual' : ($blockType === 'video_collection' ? 'video_collections' : ($blockType === 'program_showcase' ? 'program_collections' : null)),
            'program_collection_id' => null,
            'collection_id' => null,
            'category_mode' => 'live_category', // 'live_category' or 'fixed_list'
            'item_ids' => [],
            'channel_ids' => [],
            'avatar_size' => 'normal',
            'auto_title' => true,
            'show_now_badge' => true,
            'show_next_badge' => true,
            'show_all_link' => true,
            'density' => 'normal',
            'bg_style' => 'dark',
            'autoplay' => true,
            'loop' => true,
            'show_arrows' => true,
            'show_dots' => false,
            'auto_scroll_to_now' => true,
            'section_width' => 'boxed',
            'title_size' => 'md',
            'card_ratio' => 'default',
            'card_radius' => 'md',
        ];

        $this->draftSections[] = $newSection;
        $this->newBlockType = null;
        $this->saveDraft(false);

        $this->selectBlock($newUuid);

        Notification::make()
            ->title('Bölüm Eklendi')
            ->body("{$defaultTitle} bölümü taslağa eklendi.")
            ->success()
            ->send();
    }

    public function toggleBlockVisibility(string $uuid): void
    {
        if (in_array($uuid, ['header', 'hero', 'footer'], true)) {
            return;
        }

        foreach ($this->draftSections as $index => &$section) {
            if (($section['uuid'] ?? ($section['key'] ?? '')) === $uuid) {
                $section['visible'] = ! ($section['visible'] ?? true);
                break;
            }
        }

        $this->saveDraft(false);
        $this->dispatch('preview-updated');
    }

    public function updateFixedSetting(string $area, string $key, $value): void
    {
        if (! isset($this->fixedSectionsSettings[$area])) {
            $this->fixedSectionsSettings[$area] = [];
        }

        $this->fixedSectionsSettings[$area][$key] = $value;
        $this->saveDraft(false);
        $this->dispatch('preview-updated');
    }

    public function updateFixedDevicePresentation(string $area, string $device, string $key, $value): void
    {
        if (! isset($this->fixedSectionsSettings[$area])) {
            $this->fixedSectionsSettings[$area] = [];
        }

        if ($device === 'desktop') {
            $this->fixedSectionsSettings[$area][$key] = $value;
        } else {
            $this->fixedSectionsSettings[$area]['custom_responsive'] = true;
            if (! isset($this->fixedSectionsSettings[$area]['responsive_settings'])) {
                $this->fixedSectionsSettings[$area]['responsive_settings'] = [];
            }
            if (! isset($this->fixedSectionsSettings[$area]['responsive_settings'][$device])) {
                $this->fixedSectionsSettings[$area]['responsive_settings'][$device] = [];
            }
            $this->fixedSectionsSettings[$area]['responsive_settings'][$device][$key] = $value;
        }

        $this->saveDraft(false);
        $this->dispatch('preview-updated');
    }

    public function resetFixedDeviceOverride(string $area, string $device): void
    {
        if (isset($this->fixedSectionsSettings[$area]['responsive_settings'][$device])) {
            unset($this->fixedSectionsSettings[$area]['responsive_settings'][$device]);
        }

        $hasTablet = ! empty($this->fixedSectionsSettings[$area]['responsive_settings']['tablet']);
        $hasMobile = ! empty($this->fixedSectionsSettings[$area]['responsive_settings']['mobile']);

        if (! $hasTablet && ! $hasMobile) {
            $this->fixedSectionsSettings[$area]['custom_responsive'] = false;
            unset($this->fixedSectionsSettings[$area]['responsive_settings']);
        }

        $this->saveDraft(false);
        $this->dispatch('preview-updated');
    }

    public function updatedFixedSectionsSettings($value, $key): void
    {
        $this->saveDraft(false);
        $this->dispatch('preview-updated');
    }

    public function getHasUnpublishedChangesProperty(): bool
    {
        $draft = $this->record->draft_sections ?? [];
        $published = $this->record->published_sections ?? [];

        return $draft != $published;
    }

    public function saveDraft(bool $notify = true): void
    {
        $sections = array_values(array_filter($this->draftSections, fn ($sec) => is_array($sec) && ($sec['block_type'] ?? '') !== '_fixed_settings'));

        $payload = $sections;
        if (! empty($this->fixedSectionsSettings)) {
            $payload['_fixed_settings'] = $this->fixedSectionsSettings;
        }

        $this->validateUniqueCollections($sections);

        $this->record->draft_sections = $payload;
        $this->record->save();

        if ($notify) {
            Notification::make()
                ->title('Taslak kaydedildi')
                ->body('Değişiklikler henüz ziyaretçilere açık değil. Canlı siteye aktarmak için Yayınla butonuna basın.')
                ->success()
                ->send();
        }
    }

    public function setSectionWidthMode(int $sIndex, string $mode): void
    {
        if (isset($this->draftSections[$sIndex])) {
            $this->draftSections[$sIndex]['section_width_mode'] = $mode;

            // UX Auto-select: If mode is 'full' and content_width_mode is not explicitly set or is 'standard', auto-set content_width_mode to 'full'.
            if ($mode === 'full') {
                $currentContentMode = $this->draftSections[$sIndex]['content_width_mode'] ?? 'standard';
                if ($currentContentMode === 'standard') {
                    $this->draftSections[$sIndex]['content_width_mode'] = 'full';
                }
            }
        }
    }

    public function publish(bool $andActivate = false): void
    {
        $this->saveDraft(false);
        $this->record->publish($andActivate);

        Notification::make()
            ->title('Değişiklikler yayınlandı')
            ->body('Yeni tasarım artık canlı sitede görüntüleniyor.')
            ->success()
            ->send();
    }

    protected function validateUniqueCollections(array $sections): void
    {
        $collectionIds = [];
        $programCollectionIds = [];
        $shelfCategoryIds = [];
        foreach ($sections as $section) {
            if (($section['block_type'] ?? '') === 'video_collection') {
                $colId = $section['collection_id'] ?? ($section['video_collection_id'] ?? null);
                if ($colId) {
                    if (in_array($colId, $collectionIds, true)) {
                        throw ValidationException::withMessages([
                            'draftSections' => 'Aynı video koleksiyonu aynı ana sayfa düzeninde iki farklı blokta seçilemez.',
                        ]);
                    }
                    $collectionIds[] = $colId;
                }
            }

            if (($section['block_type'] ?? '') === 'program_showcase') {
                $pColId = $section['program_collection_id'] ?? null;
                if ($pColId) {
                    if (in_array($pColId, $programCollectionIds, true)) {
                        throw ValidationException::withMessages([
                            'draftSections' => 'Aynı program koleksiyonu aynı ana sayfa düzeninde iki farklı blokta seçilemez.',
                        ]);
                    }
                    $programCollectionIds[] = $pColId;
                }
            }

            if (($section['block_type'] ?? '') === 'category_shelf') {
                $catId = $section['category_id'] ?? null;
                if ($catId) {
                    if (in_array($catId, $shelfCategoryIds, true)) {
                        throw ValidationException::withMessages([
                            'draftSections' => 'Aynı kategori aynı ana sayfa düzeninde iki farklı Kategori Rafı bloğunda seçilemez.',
                        ]);
                    }
                    $shelfCategoryIds[] = $catId;
                }
            }
        }
    }

    /**
     * Herhangi bir blok alanı değiştiğinde taslağı kaydeder ve önizlemeyi günceller.
     */
    public function updatedDraftSections($value, $key): void
    {
        if (str_ends_with($key, '.program_collection_id') || str_ends_with($key, '.collection_id')) {
            $this->loadCollectionPublicSettings();
        }
        if (str_ends_with($key, '.category_id')) {
            $index = (int) explode('.', $key)[0];
            $section = $this->draftSections[$index] ?? null;

            if ($section) {
                $category = Category::query()->find($value);
                if ($category && (blank($section['title'] ?? null) || ($section['auto_title'] ?? false))) {
                    $this->draftSections[$index]['title'] = $category->name;
                    $this->draftSections[$index]['auto_title'] = true;
                }

                if (($section['block_type'] ?? '') === 'content_shelf' && ($section['category_mode'] ?? '') === 'fixed_list') {
                    $this->populateFixedListItems($index, $section['shelf_type'] ?? 'program', $value);
                }
            }
        }

        if (str_ends_with($key, '.title')) {
            $index = (int) explode('.', $key)[0];
            if (isset($this->draftSections[$index])) {
                $this->draftSections[$index]['auto_title'] = false;
            }
        }

        try {
            $this->saveDraft(false);
        } catch (ValidationException) {
            // Geçici durum — yut
        }

        $this->dispatch('preview-updated');
    }

    public function updateDevicePresentation(string $uuid, string $device, string $key, $value): void
    {
        $index = $this->findSectionIndexByUuid($uuid);
        if ($index === null) {
            return;
        }

        if ($device === 'desktop') {
            $this->draftSections[$index][$key] = $value;
        } else {
            $this->draftSections[$index]['custom_responsive'] = true;
            if (! isset($this->draftSections[$index]['responsive_settings'])) {
                $this->draftSections[$index]['responsive_settings'] = [];
            }
            if (! isset($this->draftSections[$index]['responsive_settings'][$device])) {
                $this->draftSections[$index]['responsive_settings'][$device] = [];
            }
            $this->draftSections[$index]['responsive_settings'][$device][$key] = $value;
        }

        $this->saveDraft(false);
        $this->dispatch('preview-updated');
    }

    public function resetDeviceOverride(string $uuid, string $device): void
    {
        $index = $this->findSectionIndexByUuid($uuid);
        if ($index === null) {
            return;
        }

        if (isset($this->draftSections[$index]['responsive_settings'][$device])) {
            unset($this->draftSections[$index]['responsive_settings'][$device]);
        }

        $hasTabletOverride = ! empty($this->draftSections[$index]['responsive_settings']['tablet']);
        $hasMobileOverride = ! empty($this->draftSections[$index]['responsive_settings']['mobile']);

        if (! $hasTabletOverride && ! $hasMobileOverride) {
            $this->draftSections[$index]['custom_responsive'] = false;
            unset($this->draftSections[$index]['responsive_settings']);
        }

        $this->saveDraft(false);
        $this->dispatch('preview-updated');
    }

    public function addShelfItem(string $uuid, $itemId): void
    {
        if (empty($itemId)) {
            return;
        }
        $index = $this->findSectionIndexByUuid($uuid);
        if ($index === null) {
            return;
        }

        $itemIds = array_values(array_filter((array) ($this->draftSections[$index]['item_ids'] ?? [])));
        if (! in_array((int) $itemId, array_map('intval', $itemIds), true)) {
            $itemIds[] = (int) $itemId;
            $this->draftSections[$index]['item_ids'] = $itemIds;
            $this->saveDraft(false);
            $this->dispatch('preview-updated');
        }
    }

    public function removeShelfItem(string $uuid, $itemId): void
    {
        $index = $this->findSectionIndexByUuid($uuid);
        if ($index === null) {
            return;
        }

        $itemIds = array_values(array_filter((array) ($this->draftSections[$index]['item_ids'] ?? [])));
        $itemIds = array_values(array_filter($itemIds, fn ($id) => (int) $id !== (int) $itemId));
        $this->draftSections[$index]['item_ids'] = $itemIds;

        $this->saveDraft(false);
        $this->dispatch('preview-updated');
    }

    public function moveShelfItem(string $uuid, $itemId, string $direction): void
    {
        $index = $this->findSectionIndexByUuid($uuid);
        if ($index === null) {
            return;
        }

        $itemIds = array_values(array_filter((array) ($this->draftSections[$index]['item_ids'] ?? [])));
        $pos = array_search((int) $itemId, array_map('intval', $itemIds), true);
        if ($pos === false) {
            return;
        }

        $targetPos = $direction === 'up' ? $pos - 1 : $pos + 1;
        if ($targetPos < 0 || $targetPos >= count($itemIds)) {
            return;
        }

        $temp = $itemIds[$pos];
        $itemIds[$pos] = $itemIds[$targetPos];
        $itemIds[$targetPos] = $temp;

        $this->draftSections[$index]['item_ids'] = array_values($itemIds);
        $this->saveDraft(false);
        $this->dispatch('preview-updated');
    }

    public function addShelfChannel(string $uuid, $channelId): void
    {
        if (empty($channelId)) {
            return;
        }
        $index = $this->findSectionIndexByUuid($uuid);
        if ($index === null) {
            return;
        }

        $channelIds = array_values(array_filter((array) ($this->draftSections[$index]['channel_ids'] ?? [])));
        if (! in_array((int) $channelId, array_map('intval', $channelIds), true)) {
            $channelIds[] = (int) $channelId;
            $this->draftSections[$index]['channel_ids'] = $channelIds;
            $this->saveDraft(false);
            $this->dispatch('preview-updated');
        }
    }

    public function removeShelfChannel(string $uuid, $channelId): void
    {
        $index = $this->findSectionIndexByUuid($uuid);
        if ($index === null) {
            return;
        }

        $channelIds = array_values(array_filter((array) ($this->draftSections[$index]['channel_ids'] ?? [])));
        $channelIds = array_values(array_filter($channelIds, fn ($id) => (int) $id !== (int) $channelId));
        $this->draftSections[$index]['channel_ids'] = $channelIds;

        $this->saveDraft(false);
        $this->dispatch('preview-updated');
    }

    public function moveShelfChannel(string $uuid, $channelId, string $direction): void
    {
        $index = $this->findSectionIndexByUuid($uuid);
        if ($index === null) {
            return;
        }

        $channelIds = array_values(array_filter((array) ($this->draftSections[$index]['channel_ids'] ?? [])));
        $pos = array_search((int) $channelId, array_map('intval', $channelIds), true);
        if ($pos === false) {
            return;
        }

        $targetPos = $direction === 'up' ? $pos - 1 : $pos + 1;
        if ($targetPos < 0 || $targetPos >= count($channelIds)) {
            return;
        }

        $temp = $channelIds[$pos];
        $channelIds[$pos] = $channelIds[$targetPos];
        $channelIds[$targetPos] = $temp;

        $this->draftSections[$index]['channel_ids'] = array_values($channelIds);
        $this->saveDraft(false);
        $this->dispatch('preview-updated');
    }

    public function setShelfType(string $uuid, string $type): void
    {
        $index = $this->findSectionIndexByUuid($uuid);
        if ($index === null) {
            return;
        }

        $currentType = $this->draftSections[$index]['shelf_type'] ?? 'program';
        if ($currentType !== $type) {
            $this->draftSections[$index]['shelf_type'] = $type;
            $this->draftSections[$index]['item_ids'] = [];
            $this->saveDraft(false);
            $this->dispatch('preview-updated');
        }
    }

    public function setShelfSourceMode(string $uuid, string $mode): void
    {
        $index = $this->findSectionIndexByUuid($uuid);
        if ($index === null) {
            return;
        }

        $this->draftSections[$index]['source_mode'] = $mode;
        $this->saveDraft(false);
        $this->dispatch('preview-updated');
    }

    public function setShelfCategoryMode(string $uuid, string $mode): void
    {
        $index = $this->findSectionIndexByUuid($uuid);
        if ($index === null) {
            return;
        }

        $this->draftSections[$index]['category_mode'] = $mode;

        if ($mode === 'fixed_list') {
            $catId = $this->draftSections[$index]['category_id'] ?? null;
            $shelfType = $this->draftSections[$index]['shelf_type'] ?? 'program';
            $this->populateFixedListItems($index, $shelfType, $catId);
        }

        $this->saveDraft(false);
        $this->dispatch('preview-updated');
    }

    protected function populateFixedListItems(int $index, string $shelfType, $categoryId): void
    {
        if (! $categoryId) {
            return;
        }

        if ($shelfType === 'program') {
            $ids = Program::query()
                ->where('show_on_public', true)
                ->where('is_active', true)
                ->whereHas('categories', fn ($q) => $q->where('categories.id', $categoryId))
                ->orderBy('sort_order')
                ->pluck('id')
                ->toArray();
        } else {
            $ids = Episode::query()
                ->where('show_on_public', true)
                ->where('is_active', true)
                ->whereHas('program.categories', fn ($q) => $q->where('categories.id', $categoryId))
                ->orderByDesc('aired_at')
                ->orderByDesc('id')
                ->pluck('id')
                ->toArray();
        }

        $this->draftSections[$index]['item_ids'] = array_values($ids);
    }

    protected function findSectionIndexByUuid(string $uuid): ?int
    {
        foreach ($this->draftSections as $idx => $section) {
            if (($section['uuid'] ?? ($section['key'] ?? '')) === $uuid) {
                return $idx;
            }
        }
        return null;
    }

    public function getSelectedBlockProperty(): ?array
    {
        if (! $this->selectedBlockUuid) {
            return null;
        }

        foreach ($this->draftSections as $section) {
            if (($section['uuid'] ?? ($section['key'] ?? '')) === $this->selectedBlockUuid) {
                return $section;
            }
        }

        return null;
    }

    public function getAvailableLayoutsProperty()
    {
        return HomepageLayout::query()->orderBy('name')->get();
    }

    public function getProgramCollectionOptionsProperty(): array
    {
        return ProgramCollection::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    public function getVideoCollectionOptionsProperty(): array
    {
        return VideoCollection::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->toArray();
    }

    public function getProgramOptionsProperty(): array
    {
        return Program::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->toArray();
    }

    public function getCategoryOptionsProperty(): array
    {
        return Category::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->toArray();
    }

    public function getEpisodeOptionsProperty(): array
    {
        return Episode::query()
            ->with('program')
            ->where('is_active', true)
            ->latest()
            ->take(150)
            ->get()
            ->mapWithKeys(fn ($ep) => [$ep->id => ($ep->program ? $ep->program->name . ' — ' : '') . $ep->title])
            ->toArray();
    }

    public function getYoutubeChannelOptionsProperty(): array
    {
        return YoutubeChannel::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->pluck('name', 'id')
            ->toArray();
    }
}
