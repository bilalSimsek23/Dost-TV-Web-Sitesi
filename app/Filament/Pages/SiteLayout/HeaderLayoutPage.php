<?php

namespace App\Filament\Pages\SiteLayout;

use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page as PageModel;
use App\Models\SiteSetting;
use App\Support\SiteCache;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class HeaderLayoutPage extends Page implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;

    protected string $view = 'filament.pages.site-layout.header-layout';

    protected static ?string $navigationLabel = 'Header / Üst Alan';

    protected static string|\UnitEnum|null $navigationGroup = 'Site Düzeni';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedComputerDesktop;

    protected static ?string $title = 'Header / Üst Alan Yönetimi';

    protected static ?string $slug = 'site-layout/header';

    public ?array $data = [];

    public string $menuSearch = '';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'administrator', 'designer', 'editor']) ?? false;
    }

    public function getSubheading(): ?string
    {
        return 'Üst alan logosu, menü navigasyonu ve header davranışlarını tek merkezden yönetin.';
    }

    public function mount(): void
    {
        $settings = SiteSetting::current();
        $this->form->fill($settings->toArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('header-layout-tabs')
                    ->tabs([
                        // 1. Logo ve Marka Sekmesi
                        Tab::make('Logo ve Marka')
                            ->icon('heroicon-o-photo')
                            ->schema([
                                TextInput::make('site_name')
                                    ->label('Site Adı')
                                    ->required()
                                    ->maxLength(100),

                                FileUpload::make('logo')
                                    ->label('Site Logosu')
                                    ->image()
                                    ->disk('public')
                                    ->directory('branding')
                                    ->maxSize(5120)
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'])
                                    ->helperText('Önerilen format: Şeffaf PNG veya SVG (maksimum 5 MB). Yüklenmezse varsayılan logo kullanılır.'),

                                FileUpload::make('favicon')
                                    ->label('Favicon (Site Simgesi)')
                                    ->disk('public')
                                    ->directory('branding')
                                    ->maxSize(2048)
                                    ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/x-icon', 'image/vnd.microsoft.icon', 'image/svg+xml', 'image/ico'])
                                    ->helperText('Önerilen: 32×32 veya 64×64 PNG, ICO veya SVG (maksimum 2 MB).'),

                                TextInput::make('logo_alt_text')
                                    ->label('Logo Alternatif Metni (Alt Text)')
                                    ->placeholder('Dost TV Kurumsal Logosu')
                                    ->helperText('Erişilebilirlik için kullanılır. Boş bırakılırsa site adı kullanılır.'),
                            ]),

                        // 2. Navigasyon Sekmesi
                        Tab::make('Navigasyon')
                            ->icon('heroicon-o-bars-3')
                            ->schema([
                                Placeholder::make('header_menu_table')
                                    ->hiddenLabel()
                                    ->content(fn () => view('filament.pages.site-layout.partials.header-navigation-table', [
                                        'menuItems' => $this->menuItems,
                                        'menuSearch' => $this->menuSearch,
                                    ]))
                                    ->columnSpanFull(),
                            ]),

                        // 3. Header Davranışı Sekmesi
                        Tab::make('Header Davranışı')
                            ->icon('heroicon-o-adjustments-horizontal')
                            ->schema([
                                Toggle::make('header_is_sticky')
                                    ->label('Header Sabit Kalsın (Sticky)')
                                    ->helperText('Sayfa aşağı kaydırıldığında header ekranın üstünde sabit kalır.')
                                    ->default(true),

                                Toggle::make('search_is_visible')
                                    ->label('Arama İkonunu Göster')
                                    ->helperText('Header üzerinde hızlı arama seçeneğini aktifleştirir.')
                                    ->default(true),
                            ]),
                    ])
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function ensureHeaderPrimaryMenu(): Menu
    {
        return Menu::firstOrCreate(
            ['location' => 'header_primary'],
            [
                'name' => 'Ana Üst Menü',
                'description' => 'Masaüstü ve ana navigasyon header menüsü',
                'is_active' => true,
            ]
        );
    }

    public function getMenuItemsProperty(): Collection
    {
        $menu = $this->ensureHeaderPrimaryMenu();
        $query = MenuItem::query()
            ->where('menu_id', $menu->id)
            ->whereNull('parent_id')
            ->orderBy('sort_order');

        if (filled($this->menuSearch)) {
            $searchTerm = trim($this->menuSearch);
            $query->where('title', 'like', '%' . $searchTerm . '%');
        }

        return $query->get();
    }

    public function reorderMenuItems(array $items): void
    {
        foreach ($items as $index => $id) {
            MenuItem::where('id', $id)->update(['sort_order' => $index + 1]);
        }

        $this->clearMenuCache();

        Notification::make()
            ->title('Header menü sıralaması güncellendi.')
            ->success()
            ->send();
    }

    public function createMenuItemAction(): Action
    {
        return Action::make('createMenuItem')
            ->label('Yeni Menü Öğesi')
            ->icon('heroicon-o-plus')
            ->color('warning')
            ->modalHeading('Yeni Header Menü Öğesi Ekle')
            ->modalWidth('lg')
            ->form($this->getMenuItemFormSchema())
            ->action(function (array $data) {
                $menu = $this->ensureHeaderPrimaryMenu();
                $data['menu_id'] = $menu->id;

                if (($data['item_type'] ?? '') === 'page') {
                    $data['linked_model_type'] = 'page';
                }

                if (blank($data['sort_order'] ?? null)) {
                    $data['sort_order'] = ($menu->items()->whereNull('parent_id')->max('sort_order') ?? 0) + 1;
                }

                MenuItem::create($data);
                $this->clearMenuCache();

                Notification::make()
                    ->title('Menü öğesi başarıyla eklendi.')
                    ->success()
                    ->send();
            });
    }

    public function editMenuItemAction(): Action
    {
        return Action::make('editMenuItem')
            ->label('Düzenle')
            ->icon('heroicon-m-pencil-square')
            ->color('gray')
            ->outlined()
            ->size('sm')
            ->modalHeading('Menü Öğesini Düzenle')
            ->modalWidth('lg')
            ->form($this->getMenuItemFormSchema())
            ->fillForm(function (array $arguments) {
                $item = MenuItem::find($arguments['item'] ?? null);
                return $item ? $item->toArray() : [];
            })
            ->action(function (array $data, array $arguments) {
                $item = MenuItem::find($arguments['item'] ?? null);
                if ($item) {
                    if (($data['item_type'] ?? '') === 'page') {
                        $data['linked_model_type'] = 'page';
                    }
                    $item->update($data);
                    $this->clearMenuCache();

                    Notification::make()
                        ->title('Menü öğesi güncellendi.')
                        ->success()
                        ->send();
                }
            });
    }

    public function deleteMenuItemAction(): Action
    {
        return Action::make('deleteMenuItem')
            ->icon('heroicon-m-trash')
            ->color('gray')
            ->iconButton()
            ->size('sm')
            ->tooltip('Menü Öğesini Sil')
            ->requiresConfirmation()
            ->modalHeading('Menü Öğesini Sil')
            ->modalDescription('Bu menü öğesini header navigasyonundan silmek istediğinizden emin misiniz?')
            ->action(function (array $arguments) {
                $item = MenuItem::find($arguments['item'] ?? null);
                if ($item) {
                    $item->delete();
                    $this->clearMenuCache();

                    Notification::make()
                        ->title('Menü öğesi silindi.')
                        ->success()
                        ->send();
                }
            });
    }

    public function getMenuItemFormSchema(): array
    {
        return [
            TextInput::make('title')
                ->label('Başlık')
                ->placeholder('Örn: Programlar, Özel Sayfa vb.')
                ->required()
                ->maxLength(100),

            Select::make('item_type')
                ->label('Bağlantı Türü')
                ->options([
                    'program_mega_menu' => 'Programlar Mega Menüsü',
                    'route' => 'İç Rota (Yayın Akışı, Canlı TV vb.)',
                    'page' => 'Kurumsal / Statik Sayfa',
                    'url' => 'Özel URL / Dış Bağlantı',
                    'dropdown' => 'Açılır Menü (Dropdown)',
                ])
                ->default('route')
                ->required()
                ->live(),

            Select::make('route_name')
                ->label('Hedef Rota')
                ->options(MenuItem::ROUTE_NAME_OPTIONS)
                ->visible(fn ($get) => $get('item_type') === 'route')
                ->required(fn ($get) => $get('item_type') === 'route'),

            Select::make('linked_model_id')
                ->label('Kurumsal Sayfa Seçin')
                ->options(fn () => PageModel::where('page_type', 'corporate')->pluck('title', 'id'))
                ->visible(fn ($get) => $get('item_type') === 'page')
                ->required(fn ($get) => $get('item_type') === 'page'),

            TextInput::make('url')
                ->label('Dış Bağlantı URL')
                ->placeholder('https://example.com')
                ->visible(fn ($get) => in_array($get('item_type'), ['url', 'custom']))
                ->required(fn ($get) => in_array($get('item_type'), ['url', 'custom'])),

            Toggle::make('is_active')
                ->label('Menüde Göster (Aktif)')
                ->default(true),

            Toggle::make('open_in_new_tab')
                ->label('Yeni Sekmede Aç')
                ->default(false),

            TextInput::make('badge_text')
                ->label('Rozet Metni (Opsiyonel)')
                ->placeholder('Yeni, Özel vb.')
                ->maxLength(20),
        ];
    }

    public function clearMenuCache(): void
    {
        SiteCache::forgetMenu('header_primary');
        \App\Services\Menu\ProgramMegaMenuService::forgetCache();
        Cache::flush();
    }

    public function save(): void
    {
        $data = $this->form->getState();
        SiteSetting::current()->update($data);
        SiteCache::forgetSiteSetting();

        Notification::make()
            ->title('Header ayarları başarıyla kaydedildi.')
            ->success()
            ->send();
    }
}
