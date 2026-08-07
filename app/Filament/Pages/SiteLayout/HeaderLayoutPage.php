<?php

namespace App\Filament\Pages\SiteLayout;

use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\SiteSetting;
use App\Support\SiteCache;
use BackedEnum;
use Filament\Forms\Components\FileUpload;

use Filament\Forms\Components\Placeholder;

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
use Illuminate\Support\HtmlString;

class HeaderLayoutPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.site-layout.header-layout';

    protected static ?string $navigationLabel = 'Header / Üst Alan';

    protected static string|\UnitEnum|null $navigationGroup = 'Site Düzeni';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedComputerDesktop;

    protected static ?string $title = 'Header / Üst Alan Yönetimi';

    protected static ?string $slug = 'site-layout/header';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'administrator', 'designer', 'editor']) ?? false;
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
                        Tab::make('Logo ve Marka')
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

                        Tab::make('Navigasyon')
                            ->schema([
                                Placeholder::make('navigation_info')
                                    ->label('HEADER MENÜSÜ')
                                    ->helperText('Header ana menüsü bu ekrandan düzenlenmez. Burada yalnızca mevcut durum gösterilir.')
                                    ->content(function () {
                                        $menu = Menu::where('location', 'header_primary')->first();
                                        $totalItems = $menu ? $menu->items()->count() : 0;
                                        $activeItems = $menu ? $menu->items()->where('is_active', true)->count() : 0;
                                        $updatedAt = $menu && $menu->updated_at ? $menu->updated_at->format('d Ağustos Y') : now()->format('d Ağustos Y');

                                        return new HtmlString("
                                            <div class='p-3 rounded-lg bg-slate-900 border border-slate-700/80 text-xs space-y-2'>
                                                <div class='flex flex-wrap items-center justify-between gap-3 border-b border-slate-800 pb-2'>
                                                    <div>
                                                        <span class='text-slate-400'>Toplam Menü:</span>
                                                        <strong class='text-white ml-1 font-semibold'>{$totalItems}</strong>
                                                    </div>
                                                    <div>
                                                        <span class='text-slate-400'>Aktif Menü:</span>
                                                        <strong class='text-emerald-400 ml-1 font-semibold'>{$activeItems}</strong>
                                                    </div>
                                                    <div>
                                                        <span class='text-slate-400'>Son Güncelleme:</span>
                                                        <span class='text-slate-300 ml-1 font-medium'>{$updatedAt}</span>
                                                    </div>
                                                </div>
                                                <div class='flex justify-end pt-1'>
                                                    <a href='/admin/top-header' class='text-xs text-rose-400 hover:text-rose-300 font-medium hover:underline inline-flex items-center gap-1'>
                                                        Header Menüsünü Düzenle →
                                                    </a>
                                                </div>
                                            </div>
                                        ");
                                    })
                                    ->columnSpanFull(),

                                Placeholder::make('navigation_preview_list')
                                    ->label('Mevcut Menü Özet Listesi')
                                    ->content(function () {
                                        $menu = Menu::where('location', 'header_primary')->first();
                                        if (! $menu) {
                                            return new HtmlString("<p class='text-slate-400 text-xs'>Menü kaydı bulunamadı.</p>");
                                        }

                                        $items = $menu->rootItems()->where('is_active', true)->take(6)->get();
                                        $html = "<div class='flex flex-wrap gap-2 p-2.5 rounded-lg bg-slate-900 border border-slate-700/80 text-xs'>";
                                        foreach ($items as $item) {
                                            $html .= "<div class='inline-flex items-center gap-1.5 px-2.5 py-1 rounded bg-slate-800 text-slate-200 border border-slate-700/50'><span class='text-emerald-400 font-bold'>✓</span> {$item->title}</div>";
                                        }
                                        $html .= "</div>";

                                        return new HtmlString($html);
                                    })
                                    ->columnSpanFull(),
                            ]),

                        Tab::make('Canlı Yayın Butonu')
                            ->schema([
                                Toggle::make('live_button_is_visible')
                                    ->label('"Canlı İzle" Butonunu Göster')
                                    ->default(true),

                                TextInput::make('live_button_text')
                                    ->label('Buton Metni')
                                    ->default('Canlı İzle')
                                    ->required()
                                    ->maxLength(50),

                                Placeholder::make('live_broadcast_info')
                                    ->label('Yayın Durumu Bilgisi')
                                    ->content(function () {
                                        $settings = SiteSetting::current();
                                        $tvActive = $settings->live_tv_is_active ? 'Aktif' : 'Pasif';
                                        $tvPublic = $settings->live_tv_is_public ? 'Açık' : 'Kapalı';

                                        return new HtmlString("
                                            <div class='p-3 rounded-lg bg-slate-900 border border-slate-700/80 text-xs space-y-2'>
                                                <div class='flex flex-wrap items-center justify-between gap-2 border-b border-slate-800 pb-2'>
                                                    <div>
                                                        <span class='text-slate-400'>Hedef Sayfa:</span>
                                                        <strong class='text-white ml-1 font-semibold'>Canlı TV Sayfası</strong>
                                                    </div>
                                                    <div>
                                                        <span class='text-slate-400'>Yayın Durumu:</span>
                                                        <span class='ml-1 font-semibold text-emerald-400'>{$tvActive}</span>
                                                    </div>
                                                    <div>
                                                        <span class='text-slate-400'>Public Görünürlük:</span>
                                                        <span class='ml-1 font-semibold text-rose-400'>{$tvPublic}</span>
                                                    </div>
                                                </div>
                                                <div class='flex justify-end pt-1'>
                                                    <a href='/admin/live-broadcast' class='text-xs text-amber-400 hover:text-amber-300 font-medium hover:underline inline-flex items-center gap-1'>
                                                        Canlı Yayın Ayarlarını Aç →
                                                    </a>
                                                </div>
                                            </div>
                                        ");
                                    })
                                    ->columnSpanFull(),
                            ]),

                        Tab::make('Header Davranışı')
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

                        Tab::make('Önizleme')
                            ->schema([
                                Placeholder::make('header_simulated_preview')
                                    ->label('Public Header Önizlemesi (Ziyaretçi Görünümü)')
                                    ->content(function (callable $get) {
                                        $renderedHeader = view('components.site.header', [
                                            'preview' => true,
                                            'siteName' => $get('site_name'),
                                            'logo' => $get('logo'),
                                            'logoAltText' => $get('logo_alt_text'),
                                            'liveButtonVisible' => $get('live_button_is_visible'),
                                            'liveButtonText' => $get('live_button_text'),
                                            'headerSticky' => $get('header_is_sticky'),
                                            'searchVisible' => $get('search_is_visible'),
                                        ])->render();

                                        return new HtmlString("
                                            <div class='w-full max-w-[1150px] mx-auto overflow-hidden rounded-xl border border-slate-700/80 shadow-2xl bg-slate-950 p-1'>
                                                {$renderedHeader}
                                            </div>
                                        ");
                                    })
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpanFull(),
            ])
            ->statePath('data');
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
