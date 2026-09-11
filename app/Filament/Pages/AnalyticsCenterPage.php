<?php

namespace App\Filament\Pages;

use App\Models\AnalyticsIntegration;
use App\Models\NotFoundLog;
use App\Models\SearchLog;
use App\Models\SiteEvent;
use App\Services\Analytics\AnalyticsInsightService;
use App\Services\Analytics\Ga4DataFetchService;
use App\Services\Analytics\GoogleAdsDataFetchService;
use App\Services\Analytics\MetaAdsDataFetchService;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AnalyticsCenterPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Analiz';

    protected static ?string $title = 'Analiz Merkezi';

    protected static string|\UnitEnum|null $navigationGroup = 'Sistem';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.pages.analytics-center-page';

    public ?array $data = [];

    public string $activeTab = 'overview';

    public string $adsSource = 'google_ads'; // google_ads, meta_ads

    public string $dateRange = '30'; // 7, 30, 90

    public bool $useFixture = false;

    public function mount(): void
    {
        $ga4Integration = AnalyticsIntegration::query()->firstOrCreate(
            ['provider' => 'ga4'],
            [
                'is_enabled' => false,
                'property_id' => '',
                'credentials' => '',
            ]
        );

        $gadsIntegration = AnalyticsIntegration::query()->firstOrCreate(
            ['provider' => 'google_ads'],
            [
                'is_enabled' => false,
                'property_id' => '',
                'credentials' => '',
            ]
        );

        $gadsCreds = is_array($gadsIntegration->credentials)
            ? $gadsIntegration->credentials
            : (json_decode($gadsIntegration->credentials ?? '', true) ?? []);

        $metaIntegration = AnalyticsIntegration::query()->firstOrCreate(
            ['provider' => 'meta_ads'],
            [
                'is_enabled' => false,
                'property_id' => '',
                'credentials' => '',
            ]
        );

        $metaCreds = is_array($metaIntegration->credentials)
            ? $metaIntegration->credentials
            : (json_decode($metaIntegration->credentials ?? '', true) ?? []);

        $this->form->fill([
            'ga4_enabled' => (bool) $ga4Integration->is_enabled,
            'ga4_property_id' => $ga4Integration->property_id ?? '',
            'ga4_credentials' => filled($ga4Integration->credentials) ? '•••••••• [Credential Kayıtlı]' : '',

            'gads_enabled' => (bool) $gadsIntegration->is_enabled,
            'gads_customer_id' => $gadsIntegration->property_id ?? '',
            'gads_login_customer_id' => $gadsCreds['login_customer_id'] ?? '',
            'gads_developer_token' => filled($gadsCreds['developer_token'] ?? null) ? '•••••••• [Credential Kayıtlı]' : '',
            'gads_client_id' => filled($gadsCreds['client_id'] ?? null) ? '•••••••• [Credential Kayıtlı]' : '',
            'gads_client_secret' => filled($gadsCreds['client_secret'] ?? null) ? '•••••••• [Credential Kayıtlı]' : '',
            'gads_refresh_token' => filled($gadsCreds['refresh_token'] ?? null) ? '•••••••• [Credential Kayıtlı]' : '',

            'meta_enabled' => (bool) $metaIntegration->is_enabled,
            'meta_account_id' => $metaIntegration->property_id ?? '',
            'meta_app_id' => $metaCreds['app_id'] ?? '',
            'meta_app_secret' => filled($metaCreds['app_secret'] ?? null) ? '•••••••• [Credential Kayıtlı]' : '',
            'meta_access_token' => filled($metaCreds['access_token'] ?? null) ? '•••••••• [Credential Kayıtlı]' : '',
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Google Analytics 4 (GA4) API Entegrasyonu')
                    ->description('GA4 Data API metriklerini senkronize etmek için Service Account JSON bilgilerinizi tanımlayın.')
                    ->schema([
                        Toggle::make('ga4_enabled')
                            ->label('GA4 Data API Entegrasyonunu Aktifleştir'),

                        TextInput::make('ga4_property_id')
                            ->label('GA4 Property ID')
                            ->placeholder('Örn: 312456789')
                            ->required(fn ($get) => (bool) $get('ga4_enabled')),

                        Textarea::make('ga4_credentials')
                            ->label('Service Account JSON Key')
                            ->placeholder('{"type": "service_account", "project_id": "...", "private_key": "..."}')
                            ->rows(6)
                            ->helperText('Veritabanında Laravel Encrypted (AES-256) şifrelemesiyle güvenli saklanır.')
                            ->required(fn ($get) => (bool) $get('ga4_enabled')),
                    ]),

                Section::make('Google Ads API Entegrasyonu')
                    ->description('Google Ads performans verilerini (harcama, tıklama, gösterim, dönüşüm) çekmek için OAuth ve Developer Token alanlarını doldurun.')
                    ->schema([
                        Toggle::make('gads_enabled')
                            ->label('Google Ads API Entegrasyonunu Aktifleştir'),

                        TextInput::make('gads_customer_id')
                            ->label('Customer ID')
                            ->placeholder('Örn: 123-456-7890')
                            ->required(fn ($get) => (bool) $get('gads_enabled')),

                        TextInput::make('gads_login_customer_id')
                            ->label('Login Customer ID / MCC (Opsiyonel)')
                            ->placeholder('Örn: 987-654-3210 (Manager Account var ise)'),

                        TextInput::make('gads_developer_token')
                            ->label('Developer Token')
                            ->placeholder('Google Ads Developer Token')
                            ->required(fn ($get) => (bool) $get('gads_enabled')),

                        TextInput::make('gads_client_id')
                            ->label('OAuth Client ID')
                            ->placeholder('xxxxxx.apps.googleusercontent.com')
                            ->required(fn ($get) => (bool) $get('gads_enabled')),

                        TextInput::make('gads_client_secret')
                            ->label('OAuth Client Secret')
                            ->placeholder('GOCSXX-xxxxxxxx')
                            ->required(fn ($get) => (bool) $get('gads_enabled')),

                        Textarea::make('gads_refresh_token')
                            ->label('OAuth Refresh Token')
                            ->placeholder('1//0xxxxxxxxxxxx...')
                            ->rows(3)
                            ->helperText('Veritabanında Laravel Encrypted (AES-256) şifrelemesiyle güvenli saklanır.')
                            ->required(fn ($get) => (bool) $get('gads_enabled')),
                    ]),

                Section::make('Meta Ads (Facebook & Instagram) API Entegrasyonu')
                    ->description('Meta Marketing API / Graph API üzerinden Facebook ve Instagram reklam performans verilerini çekmek için gerekli bilgileri tanımlayın.')
                    ->schema([
                        Toggle::make('meta_enabled')
                            ->label('Meta Ads API Entegrasyonunu Aktifleştir'),

                        TextInput::make('meta_account_id')
                            ->label('Meta Ad Account ID')
                            ->placeholder('Örn: act_1234567890 veya 1234567890')
                            ->required(fn ($get) => (bool) $get('meta_enabled')),

                        TextInput::make('meta_app_id')
                            ->label('App ID')
                            ->placeholder('Meta App ID (Örn: 123456789012345)')
                            ->required(fn ($get) => (bool) $get('meta_enabled')),

                        TextInput::make('meta_app_secret')
                            ->label('App Secret')
                            ->placeholder('Meta App Secret')
                            ->required(fn ($get) => (bool) $get('meta_enabled')),

                        Textarea::make('meta_access_token')
                            ->label('User / System User Access Token')
                            ->placeholder('EAAB... veya EAA...')
                            ->rows(3)
                            ->helperText('Veritabanında Laravel Encrypted (AES-256) şifrelemesiyle güvenli saklanır. Uzun ömürlü token veya System User token tercih edin.')
                            ->required(fn ($get) => (bool) $get('meta_enabled')),
                    ]),
            ])
            ->statePath('data');
    }


    public function saveGa4Settings(): void
    {
        $data = $this->form->getState();

        $integration = AnalyticsIntegration::query()->firstOrCreate(['provider' => 'ga4']);

        $updateData = [
            'is_enabled' => (bool) ($data['ga4_enabled'] ?? false),
            'property_id' => $data['ga4_property_id'] ?? '',
        ];

        $rawCreds = trim($data['ga4_credentials'] ?? '');

        if (filled($rawCreds) && ! str_contains($rawCreds, 'Credential Kayıtlı')) {
            $updateData['credentials'] = $rawCreds;
        }

        $integration->update($updateData);

        $this->form->fill(array_merge($this->form->getState(), [
            'ga4_enabled' => (bool) $integration->is_enabled,
            'ga4_property_id' => $integration->property_id ?? '',
            'ga4_credentials' => filled($integration->credentials) ? '•••••••• [Credential Kayıtlı]' : '',
        ]));

        Notification::make()
            ->title('GA4 Entegrasyon Ayarları Kaydedildi')
            ->success()
            ->send();
    }

    public function saveGoogleAdsSettings(): void
    {
        $data = $this->form->getState();

        $integration = AnalyticsIntegration::query()->firstOrCreate(['provider' => 'google_ads']);
        $existingCreds = is_array($integration->credentials)
            ? $integration->credentials
            : (json_decode($integration->credentials ?? '', true) ?? []);

        $newCreds = [
            'login_customer_id' => $data['gads_login_customer_id'] ?? '',
            'developer_token' => str_contains($data['gads_developer_token'] ?? '', 'Credential Kayıtlı')
                ? ($existingCreds['developer_token'] ?? '')
                : ($data['gads_developer_token'] ?? ''),
            'client_id' => str_contains($data['gads_client_id'] ?? '', 'Credential Kayıtlı')
                ? ($existingCreds['client_id'] ?? '')
                : ($data['gads_client_id'] ?? ''),
            'client_secret' => str_contains($data['gads_client_secret'] ?? '', 'Credential Kayıtlı')
                ? ($existingCreds['client_secret'] ?? '')
                : ($data['gads_client_secret'] ?? ''),
            'refresh_token' => str_contains($data['gads_refresh_token'] ?? '', 'Credential Kayıtlı')
                ? ($existingCreds['refresh_token'] ?? '')
                : ($data['gads_refresh_token'] ?? ''),
        ];

        $integration->update([
            'is_enabled' => (bool) ($data['gads_enabled'] ?? false),
            'property_id' => $data['gads_customer_id'] ?? '',
            'credentials' => json_encode($newCreds),
        ]);

        $this->form->fill(array_merge($this->form->getState(), [
            'gads_enabled' => (bool) $integration->is_enabled,
            'gads_customer_id' => $integration->property_id ?? '',
            'gads_login_customer_id' => $newCreds['login_customer_id'],
            'gads_developer_token' => filled($newCreds['developer_token']) ? '•••••••• [Credential Kayıtlı]' : '',
            'gads_client_id' => filled($newCreds['client_id']) ? '•••••••• [Credential Kayıtlı]' : '',
            'gads_client_secret' => filled($newCreds['client_secret']) ? '•••••••• [Credential Kayıtlı]' : '',
            'gads_refresh_token' => filled($newCreds['refresh_token']) ? '•••••••• [Credential Kayıtlı]' : '',
        ]));

        Notification::make()
            ->title('Google Ads Entegrasyon Ayarları Kaydedildi')
            ->success()
            ->send();
    }

    public function testGa4Connection(): void
    {
        $data = $this->form->getState();
        $propertyId = $data['ga4_property_id'] ?? '';
        $rawCreds = trim($data['ga4_credentials'] ?? '');

        $integration = AnalyticsIntegration::query()->where('provider', 'ga4')->first();

        if (str_contains($rawCreds, 'Credential Kayıtlı') && $integration) {
            $credentialsJson = $integration->credentials ?? '';
        } else {
            $credentialsJson = $rawCreds;
        }

        if (blank($propertyId) || blank($credentialsJson)) {
            Notification::make()
                ->title('Eksik Bilgi')
                ->body('Lütfen Property ID ve Service Account JSON bilgilerinizi girin.')
                ->warning()
                ->send();

            return;
        }

        $service = app(Ga4DataFetchService::class);
        $result = $service->testConnection($propertyId, $credentialsJson);

        if ($result['success']) {
            Notification::make()
                ->title('Bağlantı Başarılı')
                ->body($result['message'])
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Bağlantı Hatası')
                ->body($result['message'])
                ->danger()
                ->send();
        }
    }

    public function testGoogleAdsConnection(): void
    {
        $data = $this->form->getState();
        $customerId = $data['gads_customer_id'] ?? '';
        $loginCustomerId = $data['gads_login_customer_id'] ?? '';

        $integration = AnalyticsIntegration::query()->where('provider', 'google_ads')->first();
        $existingCreds = is_array($integration?->credentials)
            ? $integration->credentials
            : (json_decode($integration?->credentials ?? '', true) ?? []);

        $creds = [
            'login_customer_id' => $loginCustomerId,
            'developer_token' => str_contains($data['gads_developer_token'] ?? '', 'Credential Kayıtlı')
                ? ($existingCreds['developer_token'] ?? '')
                : ($data['gads_developer_token'] ?? ''),
            'client_id' => str_contains($data['gads_client_id'] ?? '', 'Credential Kayıtlı')
                ? ($existingCreds['client_id'] ?? '')
                : ($data['gads_client_id'] ?? ''),
            'client_secret' => str_contains($data['gads_client_secret'] ?? '', 'Credential Kayıtlı')
                ? ($existingCreds['client_secret'] ?? '')
                : ($data['gads_client_secret'] ?? ''),
            'refresh_token' => str_contains($data['gads_refresh_token'] ?? '', 'Credential Kayıtlı')
                ? ($existingCreds['refresh_token'] ?? '')
                : ($data['gads_refresh_token'] ?? ''),
        ];

        if (blank($customerId) || blank($creds['developer_token']) || blank($creds['client_id']) || blank($creds['client_secret']) || blank($creds['refresh_token'])) {
            Notification::make()
                ->title('Eksik Bilgi')
                ->body('Lütfen Customer ID, Developer Token, Client ID, Client Secret ve Refresh Token alanlarını doldurun.')
                ->warning()
                ->send();

            return;
        }

        $service = app(GoogleAdsDataFetchService::class);
        $result = $service->testConnection($customerId, $loginCustomerId, json_encode($creds));

        if ($result['success']) {
            Notification::make()
                ->title('Bağlantı Başarılı')
                ->body($result['message'])
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Bağlantı Hatası')
                ->body($result['message'])
                ->danger()
                ->send();
        }
    }

    public function syncGa4Now(): void
    {
        $this->saveGa4Settings();

        $service = app(Ga4DataFetchService::class);
        $success = $service->syncMetrics();

        if ($success) {
            Notification::make()
                ->title('GA4 Metrikleri Başarıyla Senkronize Edildi')
                ->success()
                ->send();
        } else {
            $integration = AnalyticsIntegration::query()->where('provider', 'ga4')->first();
            Notification::make()
                ->title('GA4 Senkronizasyon Hatası')
                ->body($integration?->last_error ?? 'Bağlantı kurulamadı veya entegrasyon pasif.')
                ->danger()
                ->send();
        }
    }

    public function saveMetaAdsSettings(): void
    {
        $data = $this->form->getState();

        $integration = AnalyticsIntegration::query()->firstOrCreate(['provider' => 'meta_ads']);
        $existingCreds = is_array($integration->credentials)
            ? $integration->credentials
            : (json_decode($integration->credentials ?? '', true) ?? []);

        $newCreds = [
            'app_id' => $data['meta_app_id'] ?? '',
            'app_secret' => str_contains($data['meta_app_secret'] ?? '', 'Credential Kayıtlı')
                ? ($existingCreds['app_secret'] ?? '')
                : ($data['meta_app_secret'] ?? ''),
            'access_token' => str_contains($data['meta_access_token'] ?? '', 'Credential Kayıtlı')
                ? ($existingCreds['access_token'] ?? '')
                : ($data['meta_access_token'] ?? ''),
        ];

        $integration->update([
            'is_enabled' => (bool) ($data['meta_enabled'] ?? false),
            'property_id' => $data['meta_account_id'] ?? '',
            'credentials' => json_encode($newCreds),
        ]);

        $this->form->fill(array_merge($this->form->getState(), [
            'meta_enabled' => (bool) $integration->is_enabled,
            'meta_account_id' => $integration->property_id ?? '',
            'meta_app_id' => $newCreds['app_id'],
            'meta_app_secret' => filled($newCreds['app_secret']) ? '•••••••• [Credential Kayıtlı]' : '',
            'meta_access_token' => filled($newCreds['access_token']) ? '•••••••• [Credential Kayıtlı]' : '',
        ]));

        Notification::make()
            ->title('Meta Ads Entegrasyon Ayarları Kaydedildi')
            ->success()
            ->send();
    }

    public function testMetaAdsConnection(): void
    {
        $data = $this->form->getState();
        $adAccountId = $data['meta_account_id'] ?? '';

        $integration = AnalyticsIntegration::query()->where('provider', 'meta_ads')->first();
        $existingCreds = is_array($integration?->credentials)
            ? $integration->credentials
            : (json_decode($integration?->credentials ?? '', true) ?? []);

        $creds = [
            'app_id' => $data['meta_app_id'] ?? '',
            'app_secret' => str_contains($data['meta_app_secret'] ?? '', 'Credential Kayıtlı')
                ? ($existingCreds['app_secret'] ?? '')
                : ($data['meta_app_secret'] ?? ''),
            'access_token' => str_contains($data['meta_access_token'] ?? '', 'Credential Kayıtlı')
                ? ($existingCreds['access_token'] ?? '')
                : ($data['meta_access_token'] ?? ''),
        ];

        if (blank($adAccountId) || blank($creds['access_token'])) {
            Notification::make()
                ->title('Eksik Bilgi')
                ->body('Lütfen Meta Ad Account ID, App ID, App Secret ve Access Token alanlarını doldurun.')
                ->warning()
                ->send();

            return;
        }

        $service = app(MetaAdsDataFetchService::class);
        $result = $service->testConnection($adAccountId, json_encode($creds));

        if ($result['success']) {
            Notification::make()
                ->title('Bağlantı Başarılı')
                ->body($result['message'])
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Bağlantı Hatası')
                ->body($result['message'])
                ->danger()
                ->send();
        }
    }

    public function syncGoogleAdsNow(): void
    {
        $this->saveGoogleAdsSettings();

        $service = app(GoogleAdsDataFetchService::class);
        $success = $service->syncMetrics();

        if ($success) {
            Notification::make()
                ->title('Google Ads Metrikleri Başarıyla Senkronize Edildi')
                ->success()
                ->send();
        } else {
            $integration = AnalyticsIntegration::query()->where('provider', 'google_ads')->first();
            Notification::make()
                ->title('Google Ads Senkronizasyon Hatası')
                ->body($integration?->last_error ?? 'Bağlantı kurulamadı veya entegrasyon pasif.')
                ->danger()
                ->send();
        }
    }

    public function syncMetaAdsNow(): void
    {
        $this->saveMetaAdsSettings();

        $service = app(MetaAdsDataFetchService::class);
        $success = $service->syncMetrics();

        if ($success) {
            Notification::make()
                ->title('Meta Ads Metrikleri Başarıyla Senkronize Edildi')
                ->success()
                ->send();
        } else {
            $integration = AnalyticsIntegration::query()->where('provider', 'meta_ads')->first();
            Notification::make()
                ->title('Meta Ads Senkronizasyon Hatası')
                ->body($integration?->last_error ?? 'Bağlantı kurulamadı veya entegrasyon pasif.')
                ->danger()
                ->send();
        }
    }

    public function toggleFixture(): void
    {
        if (config('app.env') === 'production') {
            return;
        }

        $this->useFixture = ! $this->useFixture;
        AnalyticsInsightService::clearCache();
    }

    public function navigateToInsightAction(?array $target): void
    {
        if (! empty($target['tab'])) {
            $this->activeTab = $target['tab'];
        }
        if (! empty($target['source'])) {
            $this->adsSource = $target['source'];
        }
    }

    public string $alertStatusFilter = 'open'; // open, resolved, dismissed, all

    public string $alertSeverityFilter = 'all'; // all, critical, warning

    public string $alertCategoryFilter = 'all'; // all, search, content, traffic, ads, technical

    public function dismissAlert(int $alertId): void
    {
        $alert = \App\Models\AnalyticsAlert::find($alertId);
        if ($alert) {
            $alert->update([
                'status' => 'dismissed',
                'dismissed_at' => now(),
            ]);

            Notification::make()
                ->title('Aksiyon Kapatıldı')
                ->body("\"{$alert->title}\" uyarısı kapatıldı (yok sayıldı).")
                ->info()
                ->send();
        }
    }

    public function reopenAlert(int $alertId): void
    {
        $alert = \App\Models\AnalyticsAlert::find($alertId);
        if ($alert) {
            $alert->update([
                'status' => 'open',
                'resolved_at' => null,
                'dismissed_at' => null,
                'last_detected_at' => now(),
            ]);

            Notification::make()
                ->title('Aksiyon Tekrar Açıldı')
                ->body("\"{$alert->title}\" uyarısı yeniden aktif aksiyon olarak işaretlendi.")
                ->warning()
                ->send();
        }
    }

    public function resolveAlert(int $alertId): void
    {
        $alert = \App\Models\AnalyticsAlert::find($alertId);
        if ($alert) {
            $alert->update([
                'status' => 'resolved',
                'resolved_at' => now(),
            ]);

            Notification::make()
                ->title('Aksiyon Çözüldü')
                ->body("\"{$alert->title}\" uyarısı çözüldü olarak işaretlendi.")
                ->success()
                ->send();
        }
    }

    public function getViewData(): array
    {
        $days = (int) $this->dateRange;
        $fromDate = now()->subDays($days);

        // Auto-evaluate alerts for active view
        $alertService = app(\App\Services\Analytics\AnalyticsAlertService::class);
        $alertService->evaluateAlerts($days, $this->useFixture);

        // Insights Service
        $insightService = app(AnalyticsInsightService::class);
        $insights = $insightService->generateInsights($days, $this->useFixture);

        // Persistent Alerts Query & KPIs
        $totalOpenAlerts = \App\Models\AnalyticsAlert::query()->open()->count();
        $criticalOpenAlerts = \App\Models\AnalyticsAlert::query()->open()->critical()->count();
        $resolvedAlertsCount = \App\Models\AnalyticsAlert::query()->resolved()->count();
        $new24hAlertsCount = \App\Models\AnalyticsAlert::query()->where('first_detected_at', '>=', now()->subHours(24))->count();

        $openAlertFingerprints = \App\Models\AnalyticsAlert::query()->open()->pluck('fingerprint')->toArray();

        $alertsQuery = \App\Models\AnalyticsAlert::query();

        if ($this->alertStatusFilter !== 'all') {
            $alertsQuery->where('status', $this->alertStatusFilter);
        }
        if ($this->alertSeverityFilter !== 'all') {
            $alertsQuery->where('severity', $this->alertSeverityFilter);
        }
        if ($this->alertCategoryFilter !== 'all') {
            $alertsQuery->where('category', $this->alertCategoryFilter);
        }

        $alertsList = $alertsQuery->orderByDesc('last_detected_at')->get();

        // 1. Search Analytics
        $topSearches = SearchLog::query()
            ->where('searched_at', '>=', $fromDate)
            ->select('normalized_query', 'search_query', DB::raw('count(*) as total_count'), DB::raw('AVG(result_count) as avg_results'))
            ->groupBy('normalized_query', 'search_query')
            ->orderByDesc('total_count')
            ->limit(10)
            ->get();

        $zeroResultSearches = SearchLog::query()
            ->where('searched_at', '>=', $fromDate)
            ->where('result_count', 0)
            ->select('normalized_query', 'search_query', DB::raw('count(*) as total_count'))
            ->groupBy('normalized_query', 'search_query')
            ->orderByDesc('total_count')
            ->limit(10)
            ->get();

        $totalSearchesCount = SearchLog::query()->where('searched_at', '>=', $fromDate)->count();
        $zeroResultCount = SearchLog::query()->where('searched_at', '>=', $fromDate)->where('result_count', 0)->count();

        // Device Type Distribution
        $deviceCounts = SearchLog::query()
            ->where('searched_at', '>=', $fromDate)
            ->whereNotNull('device_type')
            ->select('device_type', DB::raw('count(*) as total'))
            ->groupBy('device_type')
            ->pluck('total', 'device_type')
            ->toArray();

        $totalDevices = array_sum($deviceCounts);
        $mobilePct = $totalDevices > 0 ? round((($deviceCounts['mobile'] ?? 0) / $totalDevices) * 100) : 0;
        $desktopPct = $totalDevices > 0 ? round((($deviceCounts['desktop'] ?? 0) / $totalDevices) * 100) : 0;
        $tabletPct = max(0, 100 - ($mobilePct + $desktopPct));

        // 2. Event Analytics with Turkish Labels
        $rawEvents = SiteEvent::query()
            ->where('occurred_at', '>=', $fromDate)
            ->select('event_name', DB::raw('count(*) as total_count'))
            ->groupBy('event_name')
            ->orderByDesc('total_count')
            ->limit(10)
            ->get();

        $eventLabelMap = [
            'hero_program_click' => 'Hero Program Tıklamaları',
            'live_tv_click' => 'Canlı İzle Tıklamaları',
            'program_card_click' => 'Program Kartı Tıklamaları',
            'video_card_click' => 'Video Kartı Tıklamaları',
            'view_all_schedule_click' => 'Tüm Akış Tıklamaları',
            'collection_click' => 'Koleksiyon Tıklamaları',
        ];

        $topEvents = $rawEvents->map(function ($ev) use ($eventLabelMap) {
            $ev->label = $eventLabelMap[$ev->event_name] ?? Str::headline($ev->event_name);

            return $ev;
        });

        // 3. 404 Technical Analytics
        $notFoundLogs = NotFoundLog::query()
            ->orderByDesc('hit_count')
            ->limit(10)
            ->get();

        // 4. GA4 Snapshot
        $ga4Integration = AnalyticsIntegration::query()->where('provider', 'ga4')->first();
        $allSnapshots = $ga4Integration?->metrics_snapshot ?? [];

        $rangeKey = in_array((string) $this->dateRange, ['7', '30', '90'], true) ? (string) $this->dateRange : '30';

        if (isset($allSnapshots[$rangeKey]) && is_array($allSnapshots[$rangeKey])) {
            $activeGa4Metrics = $allSnapshots[$rangeKey];
        } else {
            $activeGa4Metrics = $allSnapshots;
        }

        // 5. Google Ads Snapshot
        $gadsIntegration = AnalyticsIntegration::query()->where('provider', 'google_ads')->first();
        $allGadsSnapshots = $gadsIntegration?->metrics_snapshot ?? [];

        if (isset($allGadsSnapshots[$rangeKey]) && is_array($allGadsSnapshots[$rangeKey])) {
            $activeGadsMetrics = $allGadsSnapshots[$rangeKey];
        } else {
            $activeGadsMetrics = $allGadsSnapshots;
        }

        // 6. Meta Ads Snapshot
        $metaIntegration = AnalyticsIntegration::query()->where('provider', 'meta_ads')->first();
        $allMetaSnapshots = $metaIntegration?->metrics_snapshot ?? [];

        if (isset($allMetaSnapshots[$rangeKey]) && is_array($allMetaSnapshots[$rangeKey])) {
            $activeMetaMetrics = $allMetaSnapshots[$rangeKey];
        } else {
            $activeMetaMetrics = $allMetaSnapshots;
        }

        return [
            'days' => $days,
            'insights' => $insights,
            'useFixture' => $this->useFixture,
            'alertKpis' => [
                'total_open' => $totalOpenAlerts,
                'critical_open' => $criticalOpenAlerts,
                'resolved' => $resolvedAlertsCount,
                'new_24h' => $new24hAlertsCount,
            ],
            'alertsList' => $alertsList,
            'openAlertFingerprints' => $openAlertFingerprints,
            'totalSearchesCount' => $totalSearchesCount,
            'zeroResultCount' => $zeroResultCount,
            'deviceStats' => [
                'mobile_pct' => $mobilePct,
                'desktop_pct' => $desktopPct,
                'tablet_pct' => $tabletPct,
                'total_devices' => $totalDevices,
            ],
            'topSearches' => $topSearches,
            'zeroResultSearches' => $zeroResultSearches,
            'topEvents' => $topEvents,
            'notFoundLogs' => $notFoundLogs,
            'ga4Integration' => $ga4Integration,
            'activeGa4Metrics' => $activeGa4Metrics,
            'gadsIntegration' => $gadsIntegration,
            'activeGadsMetrics' => $activeGadsMetrics,
            'metaIntegration' => $metaIntegration,
            'activeMetaMetrics' => $activeMetaMetrics,
        ];
    }
}




