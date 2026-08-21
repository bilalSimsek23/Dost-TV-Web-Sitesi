<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use App\Support\SiteCache;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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

class SiteSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.site-settings';

    protected static ?string $navigationLabel = 'Site Ayarları';

    protected static string|\UnitEnum|null $navigationGroup = 'Sistem';

    protected static ?int $navigationSort = 10;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $title = 'Site Ayarları';

    protected static ?string $slug = 'site-settings';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public function getSubheading(): ?string
    {
        return 'Site genelindeki sistem, global SEO ve entegrasyon parametrelerini tek merkezden yönetin.';
    }

    public function mount(): void
    {
        $this->form->fill(SiteSetting::current()->toArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('site-settings-tabs')
                    ->tabs([
                        // 1. Genel Sekmesi
                        Tab::make('Genel')
                            ->icon('heroicon-o-cog-6-tooth')
                            ->schema([
                                TextInput::make('title_suffix')
                                    ->label('Başlık Son Eki (Title Suffix)')
                                    ->placeholder('| DOST TV')
                                    ->helperText('Tüm sayfa başlıklarının sonuna otomatik eklenir (Örn: Programlar | DOST TV).')
                                    ->maxLength(100),

                                TextInput::make('system_email')
                                    ->label('Sistem / Bildirim E-postası')
                                    ->email()
                                    ->placeholder('sistem@dosttv.com')
                                    ->helperText('İletişim ve sistem bildirimlerinin yönlendirileceği e-posta adresi.')
                                    ->maxLength(150),

                                FileUpload::make('default_og_image')
                                    ->label('Varsayılan Global Paylaşım Görseli (OpenGraph)')
                                    ->image()
                                    ->disk('public')
                                    ->directory('seo')
                                    ->maxSize(5120)
                                    ->helperText('Özel paylaşım görseli bulunmayan sayfalarda ve ana sayfada sosyal medyada (Facebook, X, WhatsApp) kullanılır. (Önerilen: 1200×630 px)'),
                            ]),

                        // 2. SEO & Arama Motorları Sekmesi
                        Tab::make('SEO & Arama Motorları')
                            ->icon('heroicon-o-magnifying-glass')
                            ->schema([
                                Textarea::make('default_meta_description')
                                    ->label('Varsayılan Meta Açıklaması')
                                    ->placeholder('Dost TV - Uydu üzerinden yayın yapan Türkçe tematik televizyon kanalı...')
                                    ->rows(3)
                                    ->maxLength(350)
                                    ->helperText('Özel meta açıklaması tanımlanmamış sayfalarda ve ana sayfada arama motorları için fallback olarak kullanılır.'),

                                Toggle::make('search_engine_indexing')
                                    ->label('Arama Motorlarında İndeksleme (Robots Meta)')
                                    ->helperText('Açık: index, follow (Google ve diğer arama motorları siteyi dizine ekler). Kapalı: noindex, nofollow (Arama motorlarının siteyi dizine eklemesi engellenir).')
                                    ->default(true),

                                Select::make('canonical_url_mode')
                                    ->label('Canonical URL Davranışı')
                                    ->options([
                                        'current_url' => 'Geçerli Sayfa URL\'i (Önerilen)',
                                        'domain_root' => 'Ana Alan Adı Tabanlı',
                                    ])
                                    ->default('current_url')
                                    ->helperText('Arama motorlarına bildirilecek standart kopya (canonical) bağlantı kuralı.'),
                            ]),

                        // 3. Entegrasyonlar & Doğrulama Sekmesi
                        Tab::make('Entegrasyonlar & Doğrulama')
                            ->icon('heroicon-o-code-bracket')
                            ->schema([
                                TextInput::make('google_analytics_id')
                                    ->label('Google Analytics Ölçüm Kimliği (GA4)')
                                    ->placeholder('G-XXXXXXXXXX')
                                    ->helperText('Google Analytics 4 (gtag.js) ölçüm kimliği.')
                                    ->maxLength(50),

                                TextInput::make('google_tag_manager_id')
                                    ->label('Google Tag Manager ID')
                                    ->placeholder('GTM-XXXXXXX')
                                    ->helperText('Google Tag Manager kapsayıcı kimliği.')
                                    ->maxLength(50),

                                TextInput::make('google_site_verification')
                                    ->label('Google Search Console Doğrulama Kodu')
                                    ->placeholder('Google doğrulama anahtarı veya tam <meta> etiketi')
                                    ->helperText('Google Arama Konsolu mülk doğrulama kodu veya meta etiketi.'),

                                Textarea::make('custom_head_code')
                                    ->label('Head İçi Özel Kodlar (<head>)')
                                    ->placeholder('<!-- Yandex, Bing veya diğer doğrulama meta etiketleri -->')
                                    ->rows(4)
                                    ->helperText('<head> etiketinin kapanışından hemen önce public sayfalara enjekte edilir.'),

                                Textarea::make('custom_body_code')
                                    ->label('Body Sonu Özel Kodlar (</body>)')
                                    ->placeholder('<!-- Özel widget, chat veya takip scriptleri -->')
                                    ->rows(4)
                                    ->helperText('</body> etiketinin kapanışından hemen önce public sayfalara enjekte edilir.'),
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
            ->title('Site ayarları başarıyla kaydedildi.')
            ->success()
            ->send();
    }
}
