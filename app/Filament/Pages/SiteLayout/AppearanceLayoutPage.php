<?php

namespace App\Filament\Pages\SiteLayout;

use App\Models\FontFamily;
use App\Models\SiteSetting;
use App\Models\ThemeSetting;
use App\Support\SiteCache;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;

class AppearanceLayoutPage extends Page implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;

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

        $themeSettings = $settings->normalized_theme_settings;

        $this->form->fill([
            'active_font_id' => $defaultFont?->id,
            'theme_mode' => $themeSettings['mode'] ?? 'dark',
            'theme_settings' => $themeSettings,
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
                        Tab::make('Tema Ayarları & Renkler')
                            ->schema([
                                Radio::make('theme_settings.mode')
                                    ->label('Public Tema Modu (Theme Mode)')
                                    ->options([
                                        'dark' => 'Koyu Tema (Dark Mode - Dost TV Standardı)',
                                        'light' => 'Açık Tema (Light Mode)',
                                        'system' => 'Sistem Tercihini Kullan (System / prefers-color-scheme)',
                                    ])
                                    ->default('dark')
                                    ->live()
                                    ->helperText('Site genelinde aktif olacak renk temasını seçin.'),

                                Section::make('Koyu Tema (Dark Mode) Renkleri')
                                    ->description('Dark Mode aktifken geçerli ana zemin, yüzey ve vurgu renkleri.')
                                    ->schema([
                                        Grid::make(3)->schema([
                                            ColorPicker::make('theme_settings.dark.background')
                                                ->label('Ana Zemin (Background)')
                                                ->required()
                                                ->live(),

                                            ColorPicker::make('theme_settings.dark.surface')
                                                ->label('İkincil Yüzey (Surface / Card)')
                                                ->required()
                                                ->live(),

                                            ColorPicker::make('theme_settings.dark.accent')
                                                ->label('Vurgu Rengi (Accent / CTA)')
                                                ->required()
                                                ->live(),
                                        ]),
                                    ]),

                                Section::make('Açık Tema (Light Mode) Renkleri')
                                    ->description('Light Mode aktifken geçerli ana zemin, yüzey ve vurgu renkleri.')
                                    ->schema([
                                        Grid::make(3)->schema([
                                            ColorPicker::make('theme_settings.light.background')
                                                ->label('Ana Zemin (Background)')
                                                ->required()
                                                ->live(),

                                            ColorPicker::make('theme_settings.light.surface')
                                                ->label('İkincil Yüzey (Surface / Card)')
                                                ->required()
                                                ->live(),

                                            ColorPicker::make('theme_settings.light.accent')
                                                ->label('Vurgu Rengi (Accent / CTA)')
                                                ->required()
                                                ->live(),
                                        ]),
                                    ]),

                                Section::make('SAYFA GRADIENT AYARLARI (ÇİFT YÖNLÜ)')
                                    ->description('Ana sayfa dikey yüksekliğine göre otomatik çalışan KOYU -> AÇIK -> KOYU çift yönlü renk geçişini özelleştirin.')
                                    ->schema([
                                        Toggle::make('theme_settings.page_gradient.enabled')
                                            ->label('Sayfa Gradient\'ını Kullan')
                                            ->default(false)
                                            ->live()
                                            ->columnSpanFull()
                                            ->helperText('Kapalı olduğunda (varsayılan) ana sayfa normal koyu DOST TV zemin rengini kullanır. Açıldığında dikey renk geçişi devreye girer.'),

                                        Section::make('ÜSTTEN AÇILMA')
                                            ->schema([
                                                Grid::make(3)->schema([
                                                    \Filament\Forms\Components\TextInput::make('theme_settings.page_gradient.top_dark_hold')
                                                        ->label('Üst Koyu Alan Bitişi (%)')
                                                        ->numeric()
                                                        ->minValue(0)
                                                        ->maxValue(70)
                                                        ->default(25),

                                                    \Filament\Forms\Components\TextInput::make('theme_settings.page_gradient.top_fade_start')
                                                        ->label('Açılma Başlangıcı (%)')
                                                        ->numeric()
                                                        ->minValue(0)
                                                        ->maxValue(90)
                                                        ->default(25),

                                                    \Filament\Forms\Components\TextInput::make('theme_settings.page_gradient.light_zone_start')
                                                        ->label('Açık Alana Ulaşma (%)')
                                                        ->numeric()
                                                        ->minValue(0)
                                                        ->maxValue(100)
                                                        ->default(50),
                                                ]),
                                            ]),

                                        Section::make('ORTA / ALTTAN KOYULAŞMA')
                                            ->schema([
                                                Grid::make(3)->schema([
                                                    \Filament\Forms\Components\TextInput::make('theme_settings.page_gradient.light_zone_end')
                                                        ->label('Açık Alan Bitişi (%)')
                                                        ->numeric()
                                                        ->minValue(0)
                                                        ->maxValue(100)
                                                        ->default(65),

                                                    \Filament\Forms\Components\TextInput::make('theme_settings.page_gradient.bottom_fade_start')
                                                        ->label('Alttan Koyulaşma Başlangıcı (%)')
                                                        ->numeric()
                                                        ->minValue(0)
                                                        ->maxValue(100)
                                                        ->default(65),

                                                    \Filament\Forms\Components\TextInput::make('theme_settings.page_gradient.bottom_dark_at')
                                                        ->label('Tam Koyu Olma Noktası (%)')
                                                        ->numeric()
                                                        ->minValue(40)
                                                        ->maxValue(100)
                                                        ->default(100),
                                                ]),
                                            ]),

                                        Section::make('RENKLER')
                                            ->schema([
                                                Grid::make(2)->schema([
                                                    ColorPicker::make('theme_settings.page_gradient.light_color')
                                                        ->label('Açık Orta Alan Rengi')
                                                        ->default('#F5F2E8'),

                                                    ColorPicker::make('theme_settings.page_gradient.bottom_color')
                                                        ->label('Alt Bitiş Rengi (DOST TV Koyu Zemin)')
                                                        ->default('#030712'),
                                                ]),
                                            ]),
                                    ]),
                            ]),

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
                                                <div class='flex flex-wrap items-center justify-end gap-3'>
                                                    <a href='/admin/font-families' class='text-xs text-rose-400 hover:text-rose-300 font-medium hover:underline inline-flex items-center gap-1'>
                                                        Fontları Yönet (FontFamily →)
                                                    </a>
                                                </div>
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
            ->update(['value' => ! empty($data['reduced_motion']) ? '1' : '0']);

        // 4. Update SiteSetting custom_css & theme_settings
        $siteSettings = SiteSetting::current();
        $siteSettings->update([
            'custom_css' => $data['custom_css'] ?? null,
            'theme_settings' => $data['theme_settings'] ?? $siteSettings->normalized_theme_settings,
        ]);

        SiteCache::forgetSiteSetting();
        SiteCache::forgetTheme();

        Notification::make()
            ->title('Görünüm Ayarları Güncellendi')
            ->body('Tema ve görünüm değişiklikleriniz başarıyla kaydedildi.')
            ->success()
            ->send();
    }

    public function livePreview(): void
    {
        $data = $this->form->getRawState();
        $themeSettings = $data['theme_settings'] ?? SiteSetting::current()->normalized_theme_settings;

        $previewToken = \Illuminate\Support\Str::random(32);
        $previewData = [
            'theme_settings' => $themeSettings,
            'mode' => $themeSettings['mode'] ?? 'dark',
            'token' => $previewToken,
        ];

        session()->put('theme_preview_data', $previewData);
        session()->put('theme_preview_token', $previewToken);
        \Illuminate\Support\Facades\Cache::put('theme_preview_' . $previewToken, $previewData, now()->addHours(2));

        Notification::make()
            ->title('Canlı Tema Test Modu Başlatıldı')
            ->body('Tema ayarlarınız DB\'ye kaydedilmeden yeni sekmede test ediliyor.')
            ->warning()
            ->send();

        $previewUrl = url('/?theme_preview_token=' . $previewToken);
        $this->js("window.open('{$previewUrl}', '_blank');");
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('previewInNewTab')
                ->label('Yeni Sekmede Önizle')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->url(url('/'))
                ->openUrlInNewTab(),
        ];
    }
}
