<?php

namespace App\Filament\Pages;

use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Services\Menu\MenuResolver;
use App\Support\SiteCache;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page as FilamentPage;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class TopHeader extends FilamentPage implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected string $view = 'filament.pages.top-header';

    protected static ?string $navigationLabel = 'TOP HEADER';

    protected static string|\UnitEnum|null $navigationGroup = 'Site Yönetimi';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedComputerDesktop;

    /**
     * Disable automatic standalone navigation registration for this Page
     * so it doesn't create a flat entry (it is registered in AdminPanelProvider as a
     * NavigationItem with dynamic MenuItem-based children attached via ->parentItem()).
     */
    protected static bool $shouldRegisterNavigation = false;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    protected static ?string $title = 'Top Header Yönetimi';

    protected static ?string $slug = 'top-header';

    public ?Menu $menu = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'administrator', 'designer', 'editor']) ?? false;
    }

    public function mount(): void
    {
        $this->ensureHeaderPrimaryMenu();

        if (! isset($this->table)) {
            $this->table = $this->table($this->makeTable());
        }

        if (request()->query('action') === 'create') {
            $this->mountTableAction('create_root');
        } elseif (request()->has('item')) {
            $itemId = request()->integer('item');
            $item = MenuItem::where('menu_id', $this->menu->id)->find($itemId);
            if ($item) {
                $this->mountTableAction('edit', $item);
            }
        }
    }

    public function ensureHeaderPrimaryMenu(): Menu
    {
        if (! $this->menu) {
            $this->menu = Menu::firstOrCreate(
                ['location' => 'header_primary'],
                [
                    'name' => 'Ana Üst Menü',
                    'description' => 'Masaüstü ve ana navigasyon header menüsü',
                    'is_active' => true,
                ]
            );

            $this->seedInitialItemsIfEmpty();
        }

        return $this->menu;
    }

    public function seedInitialItemsIfEmpty(): void
    {
        if ($this->menu->items()->count() === 0) {
            // 1. Programlar
            MenuItem::create([
                'menu_id' => $this->menu->id,
                'parent_id' => null,
                'title' => 'Programlar',
                'item_type' => 'program_mega_menu',
                'route_name' => 'programs.index',
                'sort_order' => 1,
                'is_active' => true,
            ]);

            // 2. Yayın Akışı
            MenuItem::create([
                'menu_id' => $this->menu->id,
                'parent_id' => null,
                'title' => 'Yayın Akışı',
                'item_type' => 'route',
                'route_name' => 'schedule.index',
                'sort_order' => 2,
                'is_active' => true,
            ]);

            // 3. Hatim / Cüz Al
            MenuItem::create([
                'menu_id' => $this->menu->id,
                'parent_id' => null,
                'title' => 'Hatim / Cüz Al',
                'item_type' => 'url',
                'url' => '#',
                'sort_order' => 3,
                'is_active' => true,
            ]);

            // 4. Canlı
            $canli = MenuItem::create([
                'menu_id' => $this->menu->id,
                'parent_id' => null,
                'title' => 'Canlı',
                'item_type' => 'dropdown',
                'sort_order' => 4,
                'is_active' => true,
            ]);

            // 4.1 Dost TV Canlı
            MenuItem::create([
                'menu_id' => $this->menu->id,
                'parent_id' => $canli->id,
                'title' => 'Dost TV Canlı',
                'item_type' => 'live_tv',
                'sort_order' => 1,
                'is_active' => true,
            ]);

            // 4.2 Dost FM Canlı
            MenuItem::create([
                'menu_id' => $this->menu->id,
                'parent_id' => $canli->id,
                'title' => 'Dost FM Canlı',
                'item_type' => 'live_radio',
                'sort_order' => 2,
                'is_active' => true,
            ]);

            $this->clearCache();
        }
    }

    public function table(Table $table): Table
    {
        $menu = $this->ensureHeaderPrimaryMenu();

        return $table
            ->query(fn (): Builder => MenuItem::query()->where('menu_id', $menu->id))
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('drag_handle')
                    ->label('')
                    ->state(fn (MenuItem $record) => '
                        <button type="button" 
                                class="drag-handle cursor-grab active:cursor-grabbing p-1.5 text-slate-400 hover:text-slate-600 dark:text-slate-500 dark:hover:text-slate-300 font-bold select-none text-base transition-colors" 
                                data-id="' . $record->id . '" 
                                data-parent-id="' . ($record->parent_id ?? 'root') . '" 
                                aria-label="' . e($record->title) . ' menü öğesini taşı" 
                                title="Sürükleyerek sırala">
                            ⋮⋮
                        </button>
                    ')
                    ->html(),

                TextColumn::make('title')
                    ->label('Başlık')
                    ->state(fn (MenuItem $record) => $record->parent_id
                        ? '↳ ' . $record->title
                        : $record->title
                    )
                    ->description(fn (MenuItem $record) => $record->parent ? 'Üst Öğe: ' . $record->parent->title : null)
                    ->searchable(),

                TextColumn::make('item_type')
                    ->label('Tür')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => MenuItem::ITEM_TYPES[$state] ?? $state),

                TextColumn::make('resolved_url')
                    ->label('Hedef URL / Rota')
                    ->state(function (MenuItem $record) {
                        $resolved = $record->resolved_url;
                        if (blank($resolved) && in_array($record->item_type, ['url', 'custom'])) {
                            return '⚠️ Bağlantı henüz tanımlanmadı';
                        }
                        return $resolved ?? '—';
                    })
                    ->color(fn ($state) => str_contains((string) $state, 'Bağlantı henüz tanımlanmadı') ? 'warning' : 'gray'),

                ToggleColumn::make('is_active')
                    ->label('Aktif')
                    ->afterStateUpdated(function () {
                        $this->clearCache();
                        Notification::make()
                            ->title('Top Header başarıyla güncellendi.')
                            ->success()
                            ->send();
                    }),
            ])
            ->headerActions([
                Action::make('create_root')
                    ->label('Yeni Üst Öğe Ekle')
                    ->icon(Heroicon::OutlinedPlus)
                    ->button()
                    ->form($this->getFormSchema())
                    ->action(function (array $data) use ($menu) {
                        $data['menu_id'] = $menu->id;
                        MenuItem::create($data);
                        $this->clearCache();
                        Notification::make()
                            ->title('Top Header başarıyla güncellendi.')
                            ->success()
                            ->send();
                    }),
            ])
            ->recordActions([
                Action::make('add_child')
                    ->label('Alt Öğe Ekle')
                    ->icon(Heroicon::OutlinedPlusCircle)
                    ->color('info')
                    ->form($this->getFormSchema())
                    ->mountUsing(fn ($form, MenuItem $record) => $form->fill([
                        'parent_id' => $record->id,
                        'is_active' => true,
                        'sort_order' => $record->children()->count() + 1,
                    ]))
                    ->action(function (array $data) use ($menu) {
                        $data['menu_id'] = $menu->id;
                        MenuItem::create($data);
                        $this->clearCache();
                        Notification::make()
                            ->title('Top Header başarıyla güncellendi.')
                            ->success()
                            ->send();
                    }),

                Action::make('edit')
                    ->label('Düzenle')
                    ->icon(Heroicon::OutlinedPencil)
                    ->color('primary')
                    ->form($this->getFormSchema())
                    ->mountUsing(fn ($form, MenuItem $record) => $form->fill($record->toArray()))
                    ->action(function (MenuItem $record, array $data) {
                        $record->update($data);
                        $this->clearCache();
                        Notification::make()
                            ->title('Top Header başarıyla güncellendi.')
                            ->success()
                            ->send();
                    }),

                Action::make('delete')
                    ->label('Sil')
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Menü öğesini sil')
                    ->modalDescription(fn (MenuItem $record) => $record->children()->count() > 0
                        ? "Bu öğenin {$record->children()->count()} alt öğesi var. Silindiğinde alt öğeleri de silinecektir."
                        : 'Bu menü öğesini silmek istediğinizden emin misiniz?'
                    )
                    ->action(function (MenuItem $record) {
                        $record->delete();
                        $this->clearCache();
                        Notification::make()
                            ->title('Top Header başarıyla güncellendi.')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public function getFormSchema(): array
    {
        $menu = $this->ensureHeaderPrimaryMenu();

        return [
            TextInput::make('title')
                ->label('Başlık')
                ->required(),

            Select::make('item_type')
                ->label('Bağlantı Türü')
                ->options(MenuItem::ITEM_TYPES)
                ->default('route')
                ->required()
                ->live(),

            Select::make('parent_id')
                ->label('Üst Öğe (Parent)')
                ->options(fn () => MenuItem::where('menu_id', $menu->id)
                    ->whereNull('parent_id')
                    ->pluck('title', 'id')
                )
                ->placeholder('Ana Seviye Öğe (Üst Öğe Yok)')
                ->nullable(),

            Select::make('route_name')
                ->label('Adlandırılmış Rota')
                ->options(MenuItem::ROUTE_NAME_OPTIONS)
                ->visible(fn ($get) => $get('item_type') === 'route')
                ->required(fn ($get) => $get('item_type') === 'route'),

            Select::make('linked_model_id')
                ->label('Sayfa')
                ->options(fn () => Page::pluck('title', 'id'))
                ->visible(fn ($get) => $get('item_type') === 'page')
                ->required(fn ($get) => $get('item_type') === 'page'),

            TextInput::make('url')
                ->label('Dış Bağlantı URL')
                ->placeholder('https://example.com')
                ->visible(fn ($get) => in_array($get('item_type'), ['url', 'custom']))
                ->nullable(),

            Toggle::make('is_active')
                ->label('Aktif')
                ->default(true),

            TextInput::make('sort_order')
                ->label('Sıra')
                ->numeric()
                ->default(1),

            Toggle::make('open_in_new_tab')
                ->label('Yeni Sekmede Aç')
                ->default(false),

            TextInput::make('icon')
                ->label('İkon Class / Adı'),

            TextInput::make('badge_text')
                ->label('Rozet Metni'),

            ColorPicker::make('text_color')
                ->label('Metin Rengi'),

            ColorPicker::make('background_color')
                ->label('Arka Plan Rengi'),
        ];
    }

    public function clearCache(): void
    {
        SiteCache::forgetMenu('header_primary');
        \App\Services\Menu\ProgramMegaMenuService::forgetCache();
        Cache::flush();
    }
}
