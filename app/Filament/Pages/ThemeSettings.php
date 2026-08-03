<?php

namespace App\Filament\Pages;

use App\Models\FontFamily;
use App\Services\Theme\ThemeSettingsService;
use App\Support\ColorContrast;
use BackedEnum;
use Filament\Forms\Components\ColorPicker;
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
use Illuminate\Support\Arr;

class ThemeSettings extends Page implements HasForms
{
    use InteractsWithForms;

    /**
     * Loose but real color-format guard: 3/6-digit hex, or rgb()/rgba()/hsl()/hsla().
     * Kept permissive on the function-syntax branches since ColorPicker's
     * native UI already constrains most input; this mainly catches stray
     * free-typed garbage.
     */
    private const COLOR_REGEX = '/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$|^rgba?\(.+\)$|^hsla?\(.+\)$/';

    protected string $view = 'filament.pages.theme-settings';

    protected static ?string $navigationLabel = 'Tema Ayarları';

    protected static string|\UnitEnum|null $navigationGroup = 'Site Yönetimi';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSwatch;

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'administrator', 'designer']) ?? false;
    }

    public function mount(): void
    {
        $nested = [];

        foreach (app(ThemeSettingsService::class)->all() as $key => $setting) {
            $value = match ($setting->value_type) {
                'boolean' => (bool) ((int) $setting->value),
                default => $setting->value,
            };

            Arr::set($nested, $key, $value);
        }

        $this->form->fill($nested);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('theme-settings-tabs')
                    ->tabs([
                        Tab::make('Marka')->schema($this->brandFields()),
                        Tab::make('Renkler')->schema($this->colorFields()),
                        Tab::make('Tipografi')->schema($this->typographyFields()),
                        Tab::make('Layout')->schema($this->layoutFields()),
                        Tab::make('Butonlar')->schema($this->buttonFields()),
                        Tab::make('Kartlar')->schema($this->cardFields()),
                        Tab::make('Header')->schema($this->headerFields()),
                        Tab::make('Footer')->schema($this->footerFields()),
                        Tab::make('Animasyonlar')->schema($this->animationFields()),
                        Tab::make('Erişilebilirlik')->schema($this->accessibilityFields()),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $service = app(ThemeSettingsService::class);

        foreach (Arr::dot($this->form->getState()) as $key => $value) {
            $service->set($key, is_bool($value) ? ($value ? '1' : '0') : $value);
        }

        Notification::make()
            ->title('Tema ayarları kaydedildi')
            ->body('Değişiklikler public arayüze Aşama 3\'te bağlanacaktır.')
            ->success()
            ->send();
    }

    private function brandFields(): array
    {
        return [
            TextInput::make('brand.site_name')->label('Site Adı')->required(),
            TextInput::make('brand.site_short_name')->label('Kısa Site Adı'),
            TextInput::make('brand.slogan')->label('Slogan'),
            FileUpload::make('brand.logo')->label('Logo')->image()->disk('public')->directory('branding'),
            FileUpload::make('brand.dark_logo')->label('Koyu Logo')->image()->disk('public')->directory('branding'),
            FileUpload::make('brand.light_logo')->label('Açık Logo')->image()->disk('public')->directory('branding'),
            FileUpload::make('brand.favicon')->label('Favicon')->image()->disk('public')->directory('branding'),
        ];
    }

    private function colorFields(): array
    {
        return [
            $this->colorField('color.primary', 'Ana Renk'),
            $this->colorField('color.primary_hover', 'Ana Renk (Hover)'),
            $this->colorField('color.secondary', 'İkincil Renk'),
            $this->colorField('color.accent', 'Vurgu Rengi'),
            $this->colorField('color.background', 'Arka Plan'),
            $this->colorField('color.surface', 'Yüzey'),
            $this->colorField('color.surface_alt', 'Yüzey (Alternatif)'),
            $this->colorField('color.border', 'Kenarlık Rengi'),
            $this->colorField('color.text_primary', 'Birincil Metin Rengi'),
            $this->colorField('color.text_secondary', 'İkincil Metin Rengi'),
            $this->colorField('color.text_muted', 'Soluk Metin Rengi'),
            Placeholder::make('contrast_warning')
                ->label('Kontrast Kontrolü')
                ->live()
                ->content(function ($get) {
                    $ratio = ColorContrast::ratio($get('color.text_primary'), $get('color.background'));

                    if ($ratio === null) {
                        return 'Kontrast oranı hesaplanamadı.';
                    }

                    return $ratio >= 4.5
                        ? "Kontrast oranı {$ratio}:1 — WCAG AA standardını karşılıyor."
                        : "⚠ Kontrast oranı {$ratio}:1 — WCAG AA'nın (4.5:1) altında, metin okunabilirliği riskli.";
                }),
            $this->colorField('color.success', 'Başarı Rengi'),
            $this->colorField('color.warning', 'Uyarı Rengi'),
            $this->colorField('color.danger', 'Tehlike Rengi'),
            $this->colorField('color.live', 'Canlı Yayın Rengi'),
            $this->colorField('color.header_background', 'Header Arka Planı'),
            $this->colorField('color.footer_background', 'Footer Arka Planı'),
        ];
    }

    private function typographyFields(): array
    {
        return [
            $this->fontSelect('typography.body_font_family', 'Gövde Metni Fontu'),
            $this->fontSelect('typography.heading_font_family', 'Başlık Fontu'),
            $this->fontSelect('typography.navigation_font_family', 'Navigasyon Fontu'),
            $this->fontSelect('typography.button_font_family', 'Buton Fontu'),
            $this->numberField('typography.base_font_size', 'Temel Yazı Boyutu', 10, 24, 'px'),
            $this->numberField('typography.body_line_height', 'Gövde Satır Yüksekliği', 1, 3),
            $this->numberField('typography.h1_size', 'H1 Boyutu', 24, 96, 'px'),
            $this->numberField('typography.h2_size', 'H2 Boyutu', 20, 72, 'px'),
            $this->numberField('typography.h3_size', 'H3 Boyutu', 16, 56, 'px'),
            $this->numberField('typography.h4_size', 'H4 Boyutu', 14, 40, 'px'),
            $this->numberField('typography.navigation_font_size', 'Navigasyon Yazı Boyutu', 10, 20, 'px'),
            $this->numberField('typography.button_font_size', 'Buton Yazı Boyutu', 10, 20, 'px'),
        ];
    }

    private function layoutFields(): array
    {
        return [
            $this->numberField('spacing.container_max_width', 'Konteyner Maksimum Genişliği', 960, 1920, 'px'),
            $this->numberField('spacing.content_max_width', 'İçerik Maksimum Genişliği', 640, 1600, 'px'),
            $this->numberField('spacing.header_height', 'Header Yüksekliği', 48, 160, 'px'),
            $this->numberField('spacing.mobile_header_height', 'Mobil Header Yüksekliği', 40, 120, 'px'),
            $this->numberField('spacing.section_spacing', 'Bölüm Boşluğu', 0, 200, 'px'),
            $this->numberField('spacing.grid_gap', 'Grid Boşluğu', 0, 80, 'px'),
            $this->numberField('spacing.card_gap', 'Kart Boşluğu', 0, 80, 'px'),
        ];
    }

    private function buttonFields(): array
    {
        return [
            $this->numberField('buttons.button_height', 'Buton Yüksekliği', 32, 72, 'px'),
            $this->numberField('buttons.button_padding_x', 'Buton Yatay Boşluk', 0, 64, 'px'),
            $this->numberField('buttons.button_padding_y', 'Buton Dikey Boşluk', 0, 32, 'px'),
            $this->numberField('buttons.button_radius', 'Buton Köşe Yuvarlaklığı', 0, 9999, 'px'),
            Select::make('buttons.button_font_weight')
                ->label('Buton Font Kalınlığı')
                ->options(['400' => '400 - Regular', '500' => '500 - Medium', '600' => '600 - Semibold', '700' => '700 - Bold']),
        ];
    }

    private function cardFields(): array
    {
        return [
            $this->numberField('cards.card_radius', 'Kart Köşe Yuvarlaklığı', 0, 48, 'px'),
            $this->numberField('cards.card_border_width', 'Kart Kenarlık Kalınlığı', 0, 8, 'px'),
            Select::make('cards.card_shadow')
                ->label('Kart Gölgesi')
                ->options(['none' => 'Yok', 'sm' => 'Küçük', 'md' => 'Orta', 'lg' => 'Büyük']),
            Toggle::make('cards.card_hover_enabled')->label('Hover Efekti Aktif'),
            $this->numberField('cards.card_hover_scale', 'Hover Büyütme Oranı', 1, 1.3),
        ];
    }

    private function headerFields(): array
    {
        return [
            Toggle::make('header.sticky_header')->label('Sabit (Sticky) Header'),
            Toggle::make('header.transparent_header')->label('Şeffaf Header'),
            Toggle::make('header.show_search')->label('Arama Göster'),
            Toggle::make('header.show_live_button')->label('Canlı TV Butonu Göster'),
            Toggle::make('header.show_radio_button')->label('Canlı Radyo Butonu Göster'),
            Toggle::make('header.show_social_icons')->label('Sosyal Medya İkonları Göster'),
            $this->numberField('header.logo_width_desktop', 'Masaüstü Logo Genişliği', 16, 200, 'px'),
            $this->numberField('header.logo_width_mobile', 'Mobil Logo Genişliği', 16, 160, 'px'),
            Select::make('header.menu_alignment')
                ->label('Menü Hizalaması')
                ->options(['left' => 'Sol', 'center' => 'Orta', 'right' => 'Sağ']),
        ];
    }

    private function footerFields(): array
    {
        return [
            Toggle::make('footer.show_footer_logo')->label('Footer Logosu Göster'),
            Toggle::make('footer.show_footer_description')->label('Footer Açıklaması Göster'),
            Toggle::make('footer.show_footer_social')->label('Footer Sosyal İkonları Göster'),
            Toggle::make('footer.show_footer_contact')->label('Footer İletişim Bilgisi Göster'),
            Toggle::make('footer.show_footer_legal')->label('Footer Yasal Bağlantıları Göster'),
            $this->numberField('footer.footer_columns', 'Footer Kolon Sayısı', 1, 6),
            $this->numberField('footer.footer_spacing', 'Footer Boşluğu', 0, 120, 'px'),
        ];
    }

    private function animationFields(): array
    {
        return [
            Toggle::make('animations.animations_enabled')->label('Animasyonlar Aktif'),
            $this->numberField('animations.transition_duration', 'Geçiş Süresi', 0, 2000, 'ms'),
            $this->numberField('animations.hover_scale', 'Hover Büyütme Oranı', 1, 1.3),
            Toggle::make('animations.carousel_autoplay')->label('Carousel Otomatik Oynatma'),
            $this->numberField('animations.carousel_interval', 'Carousel Aralığı', 1000, 20000, 'ms'),
        ];
    }

    private function accessibilityFields(): array
    {
        return [
            Toggle::make('accessibility.reduced_motion_support')->label('Azaltılmış Hareket Desteği'),
            $this->numberField('accessibility.minimum_touch_target', 'Minimum Dokunma Alanı', 24, 64, 'px')
                ->helperText(fn ($state) => $state !== null && (float) $state < 44
                    ? '⚠ WCAG, dokunma hedefleri için en az 44px önerir.'
                    : 'WCAG önerisi: en az 44px.'),
            $this->numberField('accessibility.focus_ring_width', 'Focus Halkası Kalınlığı', 1, 8, 'px'),
            Toggle::make('accessibility.high_contrast_warning')->label('Düşük Kontrast Uyarısı'),
        ];
    }

    private function colorField(string $key, string $label): ColorPicker
    {
        return ColorPicker::make($key)
            ->label($label)
            ->live()
            ->rules(['regex:'.self::COLOR_REGEX]);
    }

    private function numberField(string $key, string $label, ?float $min = null, ?float $max = null, string $suffix = ''): TextInput
    {
        return TextInput::make($key)
            ->label($label)
            ->numeric()
            ->live()
            ->minValue($min)
            ->maxValue($max)
            ->suffix($suffix ?: null)
            ->helperText($min !== null && $max !== null ? "İzin verilen aralık: {$min} – {$max}" : null);
    }

    private function fontSelect(string $key, string $label): Select
    {
        return Select::make($key)
            ->label($label)
            ->options(fn () => FontFamily::query()->where('is_active', true)->orderBy('name')->pluck('name', 'name'))
            ->searchable();
    }
}
