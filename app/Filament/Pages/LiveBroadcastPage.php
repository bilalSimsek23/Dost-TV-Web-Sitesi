<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Http;

class LiveBroadcastPage extends Page implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSignal;

    protected static string|\UnitEnum|null $navigationGroup = 'Yayın Yönetimi';

    protected static ?string $navigationLabel = 'Canlı Yayın';

    protected static ?int $navigationSort = 30;

    protected static ?string $title = 'Canlı Yayın';

    protected static ?string $slug = 'live-broadcast';

    protected string $view = 'filament.pages.live-broadcast';

    public string $activeTab = 'tv'; // 'tv' or 'fm'

    // Form data arrays
    public ?array $tvData = [];
    public ?array $fmData = [];

    public function mount(): void
    {
        $settings = SiteSetting::current();

        $this->tvForm->fill([
            'live_tv_is_active' => (bool) ($settings->live_tv_is_active ?? true),
            'live_tv_title' => $settings->live_tv_title ?: 'Dost TV Canlı Yayın',
            'live_tv_description' => $settings->live_tv_description,
            'live_tv_type' => $settings->live_tv_type ?: 'hls',
            'live_tv_url' => $settings->live_tv_url,
            'live_tv_backup_url' => $settings->live_tv_backup_url,
            'live_tv_poster' => $settings->live_tv_poster,
            'live_tv_maintenance_message' => $settings->live_tv_maintenance_message,
            'live_tv_error_message' => $settings->live_tv_error_message,
            'live_tv_is_public' => (bool) ($settings->live_tv_is_public ?? true),
        ]);

        $this->fmForm->fill([
            'radio_is_active' => (bool) ($settings->radio_is_active ?? true),
            'radio_name' => $settings->radio_name ?: 'Dost FM Canlı Radyo',
            'radio_description' => $settings->radio_description,
            'radio_stream_url' => $settings->radio_stream_url,
            'radio_backup_url' => $settings->radio_backup_url,
            'radio_image' => $settings->radio_image,
            'radio_maintenance_message' => $settings->radio_maintenance_message,
            'radio_error_message' => $settings->radio_error_message,
            'radio_is_public' => (bool) ($settings->radio_is_public ?? true),
        ]);
    }

    protected function getForms(): array
    {
        return [
            'tvForm',
            'fmForm',
        ];
    }

    public function tvForm(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema
            ->statePath('tvData')
            ->components([
                Section::make('Dost TV Canlı Yayın Ayarları')
                    ->description('Canlı TV oynatıcısı, video akış kaynağı ve durum parametreleri.')
                    ->schema([
                        Grid::make(2)->schema([
                            Toggle::make('live_tv_is_active')
                                ->label('Yayın Aktif')
                                ->helperText('Kapalı olduğunda player yerine bakım mesajı gösterilir.')
                                ->default(true),

                            Toggle::make('live_tv_is_public')
                                ->label('Public Sitede Göster')
                                ->helperText('Kapalı olduğunda yayın ziyaretçilere sunulmaz.')
                                ->default(true),
                        ]),

                        TextInput::make('live_tv_title')
                            ->label('Yayın Başlığı')
                            ->placeholder('Dost TV Canlı Yayın')
                            ->helperText('Public sayfada gösterilecek ana yayın başlığı.')
                            ->required(),

                        Textarea::make('live_tv_description')
                            ->label('Kısa Açıklama')
                            ->placeholder('Yayın hakkında kısa bilgi...')
                            ->helperText('Public sayfada başlığın altında gösterilecek kısa açıklama.')
                            ->rows(2),

                        Grid::make(2)->schema([
                            Select::make('live_tv_type')
                                ->label('Yayın Kaynağı Türü')
                                ->options([
                                    'hls' => 'HLS Stream (.m3u8)',
                                    'iframe' => 'iFrame / Gömülü Oyuncu',
                                    'custom' => 'Harici Bağlantı',
                                ])
                                ->required(),

                            TextInput::make('live_tv_url')
                                ->label('Ana Yayın Bağlantısı')
                                ->placeholder('https://...')
                                ->helperText('HLS yayınlarında .m3u8 bağlantısı kullanılır.')
                                ->url()
                                ->required(),
                        ]),

                        TextInput::make('live_tv_backup_url')
                            ->label('Yedek Yayın Bağlantısı')
                            ->placeholder('https://...')
                            ->helperText('Ana yayın açılamazsa otomatik olarak bu bağlantı denenir.')
                            ->url(),

                        Grid::make(2)->schema([
                            Textarea::make('live_tv_maintenance_message')
                                ->label('Bakım Mesajı')
                                ->placeholder('Canlı yayın şu anda bakımdadır...')
                                ->helperText('Yayın manuel olarak kapatıldığında ziyaretçiye gösterilir.')
                                ->rows(2),

                            Textarea::make('live_tv_error_message')
                                ->label('Hata Mesajı')
                                ->placeholder('Canlı yayın şu anda yüklenemiyor. Lütfen daha sonra tekrar deneyin.')
                                ->helperText('Ana ve yedek yayın açılamadığında ziyaretçiye gösterilir.')
                                ->rows(2),
                        ]),
                    ]),
            ]);
    }

    public function fmForm(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema
            ->statePath('fmData')
            ->components([
                Section::make('Dost FM Canlı Radyo Ayarları')
                    ->description('Canlı radyo ses akış bağlantısı ve kanal parametreleri.')
                    ->schema([
                        Grid::make(2)->schema([
                            Toggle::make('radio_is_active')
                                ->label('Yayın Aktif')
                                ->helperText('Kapalı olduğunda radyo çalar yerine bakım mesajı gösterilir.')
                                ->default(true),

                            Toggle::make('radio_is_public')
                                ->label('Public Sitede Göster')
                                ->helperText('Kapalı olduğunda radyo yayını ziyaretçilere sunulmaz.')
                                ->default(true),
                        ]),

                        TextInput::make('radio_name')
                            ->label('Radyo Adı')
                            ->placeholder('Dost FM Canlı Radyo')
                            ->helperText('Public sayfada gösterilecek ana radyo başlığı.')
                            ->required(),

                        Textarea::make('radio_description')
                            ->label('Kısa Açıklama')
                            ->placeholder('Radyo yayını hakkında açıklama...')
                            ->helperText('Public sayfada başlığın altında gösterilecek kısa açıklama.')
                            ->rows(2),

                        Grid::make(2)->schema([
                            TextInput::make('radio_stream_url')
                                ->label('Ana Ses Akışı Bağlantısı')
                                ->placeholder('https://...')
                                ->url()
                                ->required(),

                            TextInput::make('radio_backup_url')
                                ->label('Yedek Ses Akışı Bağlantısı')
                                ->placeholder('https://...')
                                ->helperText('Ana ses akışı açılamazsa otomatik olarak bu bağlantı denenir.')
                                ->url(),
                        ]),

                        Grid::make(2)->schema([
                            Textarea::make('radio_maintenance_message')
                                ->label('Bakım Mesajı')
                                ->placeholder('Radyo yayını şu anda bakımdadır...')
                                ->helperText('Radyo manuel olarak kapatıldığında ziyaretçiye gösterilir.')
                                ->rows(2),

                            Textarea::make('radio_error_message')
                                ->label('Hata Mesajı')
                                ->placeholder('Radyo akışı yüklenemedi...')
                                ->helperText('Ana ve yedek ses akışı açılamadığında ziyaretçiye gösterilir.')
                                ->rows(2),
                        ]),
                    ]),
            ]);
    }

    public function saveTv(): void
    {
        $data = $this->tvForm->getState();

        $settings = SiteSetting::current();
        $settings->update([
            'live_tv_is_active' => (bool) ($data['live_tv_is_active'] ?? true),
            'live_tv_title' => $data['live_tv_title'] ?? 'Dost TV Canlı Yayın',
            'live_tv_description' => $data['live_tv_description'] ?? null,
            'live_tv_type' => $data['live_tv_type'] ?? 'hls',
            'live_tv_url' => $data['live_tv_url'] ?? null,
            'live_tv_backup_url' => $data['live_tv_backup_url'] ?? null,
            'live_tv_poster' => $settings->live_tv_poster,
            'live_tv_maintenance_message' => $data['live_tv_maintenance_message'] ?? null,
            'live_tv_error_message' => $data['live_tv_error_message'] ?? null,
            'live_tv_is_public' => (bool) ($data['live_tv_is_public'] ?? true),
        ]);

        Notification::make()
            ->title('Dost TV canlı yayın ayarları başarıyla kaydedildi.')
            ->success()
            ->send();
    }

    public function saveFm(): void
    {
        $data = $this->fmForm->getState();

        $settings = SiteSetting::current();
        $settings->update([
            'radio_is_active' => (bool) ($data['radio_is_active'] ?? true),
            'radio_name' => $data['radio_name'] ?? 'Dost FM Canlı Radyo',
            'radio_description' => $data['radio_description'] ?? null,
            'radio_stream_url' => $data['radio_stream_url'] ?? null,
            'radio_backup_url' => $data['radio_backup_url'] ?? null,
            'radio_image' => $settings->radio_image,
            'radio_maintenance_message' => $data['radio_maintenance_message'] ?? null,
            'radio_error_message' => $data['radio_error_message'] ?? null,
            'radio_is_public' => (bool) ($data['radio_is_public'] ?? true),
        ]);

        Notification::make()
            ->title('Dost FM canlı radyo ayarları başarıyla kaydedildi.')
            ->success()
            ->send();
    }

    public function testTvConnection(): void
    {
        $url = $this->tvData['live_tv_url'] ?? null;

        if (blank($url) || ! filter_var($url, FILTER_VALIDATE_URL)) {
            Notification::make()
                ->title('URL Biçimi Geçersiz')
                ->body('Lütfen geçerli bir canlı yayın URL adresi giriniz.')
                ->danger()
                ->send();

            return;
        }

        try {
            $response = Http::timeout(4)->get($url);
            if ($response->successful() || $response->status() === 206 || $response->status() === 302) {
                Notification::make()
                    ->title('Bağlantı Çalışıyor')
                    ->body('Dost TV yayın kaynağına başarıyla erişildi. (HTTP Status: ' . $response->status() . ')')
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Bağlantıya Ulaşılamadı')
                    ->body('Yayın sunucusu beklenmeyen yanıt döndürdü (HTTP ' . $response->status() . ').')
                    ->warning()
                    ->send();
            }
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Kaynak Yanıt Vermiyor')
                ->body('Yayın bağlantısına ulaşılamadı. Sunucu erişilemez durumda olabilir.')
                ->danger()
                ->send();
        }
    }

    public function testFmConnection(): void
    {
        $url = $this->fmData['radio_stream_url'] ?? null;

        if (blank($url) || ! filter_var($url, FILTER_VALIDATE_URL)) {
            Notification::make()
                ->title('URL Biçimi Geçersiz')
                ->body('Lütfen geçerli bir radyo ses akışı URL adresi giriniz.')
                ->danger()
                ->send();

            return;
        }

        try {
            $response = Http::timeout(4)->get($url);
            if ($response->successful() || $response->status() === 206 || $response->status() === 302) {
                Notification::make()
                    ->title('Bağlantı Çalışıyor')
                    ->body('Dost FM ses akışına başarıyla erişildi. (HTTP Status: ' . $response->status() . ')')
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Bağlantıya Ulaşılamadı')
                    ->body('Radyo sunucusu beklenmeyen yanıt döndürdü (HTTP ' . $response->status() . ').')
                    ->warning()
                    ->send();
            }
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Kaynak Yanıt Vermiyor')
                ->body('Radyo ses akışı bağlantısına ulaşılamadı.')
                ->danger()
                ->send();
        }
    }
}
