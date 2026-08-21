<?php

namespace App\Filament\Pages\SiteLayout;

use App\Filament\Resources\Pages\PageResource;
use App\Models\Page as PageModel;
use App\Models\SiteSetting;
use App\Support\SiteCache;
use BackedEnum;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;

class FooterLayoutPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.site-layout.footer-layout';

    protected static ?string $navigationLabel = 'Footer';

    protected static string|\UnitEnum|null $navigationGroup = 'Site Düzeni';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static ?string $title = 'Footer Yönetimi';

    protected static ?string $slug = 'site-layout/footer';

    public string $search = '';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'administrator', 'designer', 'editor']) ?? false;
    }

    public function getSubheading(): ?string
    {
        return 'Footer\'da gösterilecek içerikleri yönetin ve sıralamasını düzenleyin.';
    }


    public function mount(): void
    {
        $settings = SiteSetting::current();
        $this->form->fill($settings->toArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Tabs::make('footer-layout-tabs')
                    ->tabs([
                        // 1. Kurumsal Sekmesi
                        Tab::make('Kurumsal')
                            ->icon('heroicon-o-building-office-2')
                            ->schema([
                                Placeholder::make('corporate_pages_list')
                                    ->hiddenLabel()
                                    ->content(fn () => view('filament.pages.site-layout.partials.corporate-pages-table', [
                                        'corporatePages' => $this->corporatePages,
                                        'search' => $this->search,
                                    ]))
                                    ->columnSpanFull(),
                            ]),

                        // 2. İletişim Sekmesi
                        Tab::make('İletişim')
                            ->icon('heroicon-o-phone')
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('phone')
                                        ->label('Telefon Numarası')
                                        ->placeholder('+90 (312) 341 21 21')
                                        ->maxLength(50)
                                        ->helperText('Footer İletişim sütununda tel: bağlantısı olarak gösterilir.'),

                                    TextInput::make('email')
                                        ->label('E-Posta Adresi')
                                        ->email()
                                        ->placeholder('iletisim@dosttv.com')
                                        ->maxLength(100)
                                        ->helperText('Footer İletişim sütununda mailto: bağlantısı olarak gösterilir.'),
                                ]),
                            ]),

                        // 3. Sosyal Medya Sekmesi
                        Tab::make('Sosyal Medya')
                            ->icon('heroicon-o-share')
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('instagram_url')
                                        ->label('Instagram')
                                        ->url()
                                        ->placeholder('https://instagram.com/dosttv')
                                        ->helperText('Instagram profil bağlantısı.'),

                                    TextInput::make('facebook_url')
                                        ->label('Facebook')
                                        ->url()
                                        ->placeholder('https://facebook.com/dosttv')
                                        ->helperText('Facebook sayfa bağlantısı.'),

                                    TextInput::make('youtube_url')
                                        ->label('YouTube')
                                        ->url()
                                        ->placeholder('https://youtube.com/@DostRadyoTV')
                                        ->helperText('YouTube kanal bağlantısı.'),

                                    TextInput::make('x_url')
                                        ->label('X / Twitter')
                                        ->url()
                                        ->placeholder('https://x.com/dosttv')
                                        ->helperText('X (Twitter) profil bağlantısı.'),

                                    TextInput::make('whatsapp_url')
                                        ->label('WhatsApp')
                                        ->url()
                                        ->placeholder('https://wa.me/903120000000')
                                        ->helperText('WhatsApp doğrudan mesajlaşma bağlantısı.'),

                                    TextInput::make('telegram_url')
                                        ->label('Telegram')
                                        ->url()
                                        ->placeholder('https://t.me/dosttv')
                                        ->helperText('Telegram kanal bağlantısı.'),
                                ]),
                            ]),

                        // 4. Alt Bilgi Sekmesi
                        Tab::make('Alt Bilgi')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                TextInput::make('copyright_text')
                                    ->label('Telif Metni')
                                    ->placeholder('© {year} Dost TV. Tüm hakları saklıdır.')
                                    ->helperText('Footer alt satırında yalnız bir kez gösterilir. {year} otomatik yıla dönüşür.')
                                    ->maxLength(150),
                            ]),

                        // 5. Önizleme Sekmesi
                        Tab::make('Önizleme')
                            ->icon('heroicon-o-eye')
                            ->schema([
                                Placeholder::make('footer_preview')
                                    ->hiddenLabel()
                                    ->content(fn (callable $get) => view('filament.pages.site-layout.partials.footer-preview-card', [
                                        'phone' => $get('phone'),
                                        'email' => $get('email'),
                                        'facebookUrl' => $get('facebook_url'),
                                        'instagramUrl' => $get('instagram_url'),
                                        'xUrl' => $get('x_url'),
                                        'youtubeUrl' => $get('youtube_url'),
                                        'whatsappUrl' => $get('whatsapp_url'),
                                        'telegramUrl' => $get('telegram_url'),
                                        'copyrightText' => $get('copyright_text'),
                                    ]))
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public function getCorporatePagesProperty(): Collection
    {
        $query = PageModel::query()->where('page_type', 'corporate');

        if (filled($this->search)) {
            $searchTerm = trim($this->search);
            $query->where('title', 'like', '%' . $searchTerm . '%');
        }

        return $query
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();
    }

    public function reorderCorporatePages(array $orderedIds): void
    {
        foreach ($orderedIds as $index => $id) {
            PageModel::where('id', $id)->update(['sort_order' => $index]);
        }

        SiteCache::forgetSiteSetting();

        Notification::make()
            ->title('Kurumsal sayfalar sıralaması güncellendi.')
            ->success()
            ->send();
    }

    public function resetForm(): void
    {
        $settings = SiteSetting::current();
        $this->form->fill($settings->toArray());

        Notification::make()
            ->title('Değişiklikler sıfırlandı.')
            ->info()
            ->send();
    }

    public function save(): void
    {
        $data = $this->form->getState();
        SiteSetting::current()->update($data);
        SiteCache::forgetSiteSetting();

        Notification::make()
            ->title('Footer ayarları başarıyla kaydedildi.')
            ->success()
            ->send();
    }
}
