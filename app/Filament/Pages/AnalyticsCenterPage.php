<?php

namespace App\Filament\Pages;

use App\Models\AnalyticsIntegration;
use App\Models\NotFoundLog;
use App\Models\SearchLog;
use App\Models\SiteEvent;
use App\Services\Analytics\Ga4DataFetchService;
use Illuminate\Support\Str;
use Filament\Schemas\Components\Section;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;

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

    public string $dateRange = '30'; // 7, 30, 90

    public ?array $ga4Data = [];

    public function mount(): void
    {
        $integration = AnalyticsIntegration::query()->firstOrCreate(
            ['provider' => 'ga4'],
            [
                'is_enabled' => false,
                'property_id' => '',
                'credentials' => '',
            ]
        );

        $this->form->fill([
            'ga4_enabled' => (bool) $integration->is_enabled,
            'ga4_property_id' => $integration->property_id ?? '',
            'ga4_credentials' => $integration->credentials ?? '',
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
            ])
            ->statePath('data');
    }

    public function saveGa4Settings(): void
    {
        $data = $this->form->getState();

        $integration = AnalyticsIntegration::query()->firstOrCreate(['provider' => 'ga4']);
        $integration->update([
            'is_enabled' => (bool) ($data['ga4_enabled'] ?? false),
            'property_id' => $data['ga4_property_id'] ?? '',
            'credentials' => $data['ga4_credentials'] ?? '',
        ]);

        Notification::make()
            ->title('GA4 Entegrasyon Ayarları Kaydedildi')
            ->success()
            ->send();
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

    public function getViewData(): array
    {
        $days = (int) $this->dateRange;
        $fromDate = now()->subDays($days);

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

        return [
            'days' => $days,
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
        ];
    }
}
