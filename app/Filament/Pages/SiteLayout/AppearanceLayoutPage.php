<?php

namespace App\Filament\Pages\SiteLayout;

use App\Models\FontFamily;
use App\Models\SiteSetting;
use App\Models\ThemeSetting;
use App\Support\SiteCache;
use BackedEnum;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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

class AppearanceLayoutPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.site-layout.appearance-layout';

    protected static ?string $navigationLabel = 'Görünüm';

    protected static string|\UnitEnum|null $navigationGroup = 'Site Düzeni';

    protected static ?int $navigationSort = 4;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaintBrush;

    protected static ?string $title = 'Görünüm Yönetimi';

    protected static ?string $slug = 'site-layout/appearance';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'administrator', 'designer', 'editor']) ?? false;
    }

    public function mount(): void
    {
        $settings = SiteSetting::current();
        $defaultFont = FontFamily::where('is_default', true)->first() ?? FontFamily::first();
        $reducedMotion = ThemeSetting::where('key', 'accessibility.reduced_motion_support')->first()?->value === '1';

        $this->form->fill([
            'active_font_id' => $defaultFont?->id,
            'theme_mode' => 'dark',
            'reduced_motion' => $reducedMotion,
            'corner_radius' => 'default',
            'shadow_intensity' => 'default',
            'blur_effect' => 'default',
            'custom_css' => $settings->custom_css,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('appearance-layout-tabs')
                    ->tabs([
                        Tab::make('Tipografi')
                            ->schema([
                                Select::make('active_font_id')
                                    ->label('Aktif Yazı Tipi (Font)')
                                    ->options(function () {
                                        return FontFamily::where('is_active', true)
                                            ->pluck('name', 'id')
                                            ->toArray();
                                    })
                                    ->required()
                                    ->helperText('Sitede genel metinler ve başlıklar için kullanılacak aktif yazı tipi.'),

                                Placeholder::make('typography_info')
                                    ->label('Font Yönetim Merkezi')
                                    ->content(function () {
                                        $activeCount = FontFamily::where('is_active', true)->count();
                                        $defaultFont = FontFamily::where('is_default', true)->first()?->name ?? 'Sistem Fontu';

                                        return new HtmlString("
                                            <div class='p-3 rounded-lg bg-slate-900 border border-slate-700/80 text-xs space-y-2'>
                                                <div class='flex flex-wrap items-center justify-between gap-3 border-b border-slate-800 pb-2'>
                                                    <div>
                                                        <span class='text-slate-400'>Varsayılan Font:</span>
                                                        <strong class='text-white ml-1 font-semibold'>{$defaultFont}</strong>
                                                    </div>
                                                    <div>
                                                        <span class='text-slate-400'>Aktif Font Sayısı:</span>
                                                        <strong class='text-emerald-400 ml-1 font-semibold'>{$activeCount}</strong>
                                                    </div>
                                                </div>
                                                <div class='flex justify-end pt-1'>
                                                    <a href='/admin/font-families' class='text-xs text-rose-400 hover:text-rose-300 font-medium hover:underline inline-flex items-center gap-1'>
                                                        Fontları Yönet (FontFamily →)
                                                    </a>
                                                </div>
                                            </div>
                                        ");
                                    })
                                    ->columnSpanFull(),
                            ]),

                        Tab::make('Tema Modu')
                            ->schema([
                                Radio::make('theme_mode')
                                    ->label('Varsayılan Görünüm Modu')
                                    ->options([
                                        'dark' => 'Koyu Tema (Dost TV Tasarım Standardı)',
                                        'system' => 'Sistem Tercihini Kullan',
                                    ])
                                    ->default('dark')
                                    ->helperText('DOST TV public arayüzü yüksek kontrastlı modern Koyu Tema (Dark Mode) standartlarında tasarlanmıştır.'),
                            ]),

                        Tab::make('Efektler ve Davranış')
                            ->schema([
                                Toggle::make('reduced_motion')
                                    ->label('Hareketi / Animasyonları Azalt (Reduced Motion)')
                                    ->helperText('Erişilebilirlik için sayfa geçiş ve mikro animasyonlarını en aza indirir.'),

                                Select::make('corner_radius')
                                    ->label('Köşe Yuvarlaklığı (Border Radius)')
                                    ->options([
                                        'default' => 'Tasarım Varsayılanı (Sleek Modern Radius)',
                                        'less' => 'Daha Az (Köşeli)',
                                        'more' => 'Daha Fazla (Oval)',
                                    ])
                                    ->default('default'),

                                Select::make('shadow_intensity')
                                    ->label('Gölge Yoğunluğu')
                                    ->options([
                                        'default' => 'Tasarım Varsayılanı (Derin Glow Gölgeler)',
                                        'light' => 'Hafif',
                                        'off' => 'Kapalı',
                                    ])
                                    ->default('default'),

                                Select::make('blur_effect')
                                    ->label('Blur / Cam Efekti (Backdrop Blur)')
                                    ->options([
                                        'default' => 'Tasarım Varsayılanı (Bulanık Arka Plan Cam Efekti)',
                                        'reduced' => 'Azaltılmış Blur',
                                        'off' => 'Kapalı',
                                    ])
                                    ->default('default'),
                            ]),

                        Tab::make('Gelişmiş')
                            ->schema([
                                Textarea::make('custom_css')
                                    ->label('Özel CSS Kodları')
                                    ->rows(6)
                                    ->maxLength(5000)
                                    ->placeholder('/* Özel stil eklemeleri */')
                                    ->helperText('Yalnızca CSS stil tanımları kabul edilir. JavaScript veya <script> etiketleri güvenlik nedeniyle reddedilir.'),

                                Placeholder::make('advanced_management_links')
                                    ->label('Gelişmiş Yönetim Bağlantıları')
                                    ->content(function () {
                                        return new HtmlString("
                                            <div class='p-3 rounded-lg bg-slate-900 border border-slate-700/80 text-xs space-y-2'>
                                                <div class='flex flex-wrap items-center justify-between gap-3'>
                                                    <a href='/admin/theme-settings' class='text-xs text-amber-400 hover:text-amber-300 font-medium hover:underline inline-flex items-center gap-1'>
                                                        Tema Ayarlarını Aç (ThemeSetting →)
                                                    </a>
                                                    <a href='/admin/font-families' class='text-xs text-rose-400 hover:text-rose-300 font-medium hover:underline inline-flex items-center gap-1'>
                                                        Fontları Yönet (FontFamily →)
                                                    </a>
                                                </div>
                                            </div>
                                        ");
                                    })
                                    ->columnSpanFull(),
                            ]),

                        Tab::make('Önizleme')
                            ->schema([
                                Placeholder::make('appearance_simulated_preview')
                                    ->hiddenLabel()
                                    ->content(function (callable $get) {
                                        $headerView = view('components.site.header', ['preview' => true])->render();
                                        $bannerView = view('components.site.hero-banner', [
                                            'preview' => true,
                                            'title' => 'DOST TV Yayın Akışı & Canlı Yayın',
                                            'subtitle' => 'Tüm cihazlarla uyumlu, yüksek kaliteli yayın portalı.',
                                        ])->render();
                                        $footerView = view('components.site.footer', ['preview' => true])->render();

                                        return new HtmlString("
                                            <div class='w-full max-w-[1150px] mx-auto overflow-hidden rounded-xl border border-slate-700/80 shadow-2xl bg-slate-950 p-1 space-y-3'>
                                                <div>{$headerView}</div>
                                                <div>{$bannerView}</div>
                                                <div>{$footerView}</div>
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

        // 1. Safety validation for Custom CSS: Reject <script> tags or JS
        if (! empty($data['custom_css'])) {
            if (preg_match('/<script|javascript:|on\w+=/i', $data['custom_css'])) {
                Notification::make()
                    ->title('Güvenlik Uyarısı: Özel CSS alanına JavaScript veya <script> etiketi eklenemez.')
                    ->danger()
                    ->send();

                return;
            }
        }

        // 2. Update active font
        if (! empty($data['active_font_id'])) {
            FontFamily::query()->update(['is_default' => false]);
            FontFamily::where('id', $data['active_font_id'])->update(['is_default' => true]);
        }

        // 3. Update reduced motion setting in ThemeSetting
        ThemeSetting::where('key', 'accessibility.reduced_motion_support')
            ->update(['value' => $data['reduced_motion'] ? '1' : '0']);

        // 4. Update SiteSetting custom_css
        SiteSetting::current()->update([
            'custom_css' => $data['custom_css'] ?? null,
        ]);

        SiteCache::forgetSiteSetting();
        SiteCache::forgetTheme();

        Notification::make()
            ->title('Görünüm ayarları başarıyla kaydedildi.')
            ->success()
            ->send();
    }
}
