<?php

namespace Tests\Feature;

use App\Filament\Pages\AnalyticsCenterPage;
use App\Models\AnalyticsIntegration;
use App\Models\User;
use App\Services\Analytics\MetaAdsDataFetchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class AnalyticsMetaAdsIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected array $sampleCredentials = [
        'app_id' => '123456789012345',
        'app_secret' => 'meta_app_secret_val_99',
        'access_token' => 'EAAB123456789MetaTokenSecretValue',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_test_connection_returns_success_when_api_responds_ok(): void
    {
        Http::fake([
            'https://graph.facebook.com/*' => Http::response([
                'id' => 'act_1234567890',
                'name' => 'DOST TV Meta Reklam Hesabı',
                'currency' => 'TRY',
                'account_status' => 1,
            ], 200),
        ]);

        $service = app(MetaAdsDataFetchService::class);
        $result = $service->testConnection('1234567890', json_encode($this->sampleCredentials));

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('bağlantısı başarılı', $result['message']);
        $this->assertStringContainsString('DOST TV Meta Reklam Hesabı', $result['message']);
    }

    public function test_test_connection_sanitizes_secret_tokens_on_error(): void
    {
        Http::fake([
            'https://graph.facebook.com/*' => Http::response([
                'error' => [
                    'message' => 'Unexpected Graph Error with token EAAB123456789MetaTokenSecretValue and secret meta_app_secret_val_99',
                    'code' => 500,
                ],
            ], 400),
        ]);

        $service = app(MetaAdsDataFetchService::class);
        $result = $service->testConnection('1234567890', json_encode($this->sampleCredentials));

        $this->assertFalse($result['success']);
        $this->assertStringNotContainsString('EAAB123456789MetaTokenSecretValue', $result['message']);
        $this->assertStringContainsString('[REDACTED', $result['message']);
    }

    public function test_sync_metrics_fetches_and_saves_7_30_90_day_snapshots(): void
    {
        $integration = AnalyticsIntegration::create([
            'provider' => 'meta_ads',
            'is_enabled' => true,
            'property_id' => '1234567890',
            'credentials' => json_encode($this->sampleCredentials),
        ]);

        Http::fake([
            'https://graph.facebook.com/v22.0/act_1234567890?fields=id%2Cname%2Ccurrency*' => Http::response([
                'id' => 'act_1234567890',
                'name' => 'DOST TV Meta Account',
                'currency' => 'TRY',
            ], 200),

            'https://graph.facebook.com/v22.0/act_1234567890/insights*level=campaign*' => Http::response([
                'data' => [
                    [
                        'campaign_id' => 'c101',
                        'campaign_name' => 'Ramadan Special Campaign',
                        'spend' => '1250.50',
                        'impressions' => '15000',
                        'reach' => '12000',
                        'clicks' => '950',
                        'ctr' => '6.33',
                        'cpc' => '1.32',
                        'cpm' => '83.37',
                        'frequency' => '1.25',
                        'actions' => [
                            ['action_type' => 'link_click', 'value' => '800'],
                            ['action_type' => 'landing_page_view', 'value' => '650'],
                        ],
                    ],
                ],
            ], 200),

            'https://graph.facebook.com/v22.0/act_1234567890/insights*time_increment=1*' => Http::response([
                'data' => [
                    [
                        'date_start' => '2026-09-04',
                        'spend' => '180.00',
                        'impressions' => '2100',
                        'reach' => '1800',
                        'clicks' => '140',
                    ],
                ],
            ], 200),

            'https://graph.facebook.com/v22.0/act_1234567890/campaigns*' => Http::response([
                'data' => [
                    ['id' => 'c101', 'name' => 'Ramadan Special Campaign', 'effective_status' => 'ACTIVE'],
                ],
            ], 200),

            'https://graph.facebook.com/v22.0/act_1234567890/insights*' => Http::response([
                'data' => [
                    [
                        'spend' => '3450.75',
                        'impressions' => '42000',
                        'reach' => '31000',
                        'clicks' => '2800',
                        'ctr' => '6.67',
                        'cpc' => '1.23',
                        'cpm' => '82.16',
                        'frequency' => '1.35',
                        'actions' => [
                            ['action_type' => 'link_click', 'value' => '2400'],
                            ['action_type' => 'landing_page_view', 'value' => '1800'],
                        ],
                        'action_values' => [
                            ['action_type' => 'purchase', 'value' => '0'],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = app(MetaAdsDataFetchService::class);
        $success = $service->syncMetrics();

        $this->assertTrue($success);

        $integration->refresh();
        $this->assertNotNull($integration->last_synced_at);
        $this->assertNull($integration->last_error);

        $snapshot = $integration->metrics_snapshot;
        $this->assertArrayHasKey('7', $snapshot);
        $this->assertArrayHasKey('30', $snapshot);
        $this->assertArrayHasKey('90', $snapshot);

        $this->assertEquals(3450.75, $snapshot['30']['spend']);
        $this->assertEquals('TRY', $snapshot['30']['currency']);
        $this->assertEquals(42000, $snapshot['30']['impressions']);
        $this->assertEquals(31000, $snapshot['30']['reach']);
        $this->assertEquals(2800, $snapshot['30']['clicks']);
        $this->assertCount(1, $snapshot['30']['campaigns']);
        $this->assertEquals('Ramadan Special Campaign', $snapshot['30']['campaigns'][0]['name']);
    }

    public function test_sync_metrics_preserves_last_valid_snapshot_when_error_occurs(): void
    {
        $oldSnapshot = [
            '30' => [
                'spend' => 999.00,
                'impressions' => 10000,
            ],
        ];

        $integration = AnalyticsIntegration::create([
            'provider' => 'meta_ads',
            'is_enabled' => true,
            'property_id' => '1234567890',
            'credentials' => json_encode($this->sampleCredentials),
            'metrics_snapshot' => $oldSnapshot,
        ]);

        Http::fake([
            'https://graph.facebook.com/*' => Http::response([
                'error' => [
                    'message' => 'OAuth token expired access_token=EAAB123456789MetaTokenSecretValue',
                    'code' => 190,
                ],
            ], 400),
        ]);

        $service = app(MetaAdsDataFetchService::class);
        $success = $service->syncMetrics();

        $this->assertFalse($success);

        $integration->refresh();
        $this->assertEquals($oldSnapshot, $integration->metrics_snapshot);
        $this->assertNotNull($integration->last_error);
        $this->assertStringNotContainsString('EAAB123456789MetaTokenSecretValue', $integration->last_error);
    }

    public function test_meta_ads_sync_does_not_affect_google_ads_or_ga4_snapshot(): void
    {
        $ga4 = AnalyticsIntegration::create([
            'provider' => 'ga4',
            'is_enabled' => true,
            'property_id' => 'ga4_999',
            'metrics_snapshot' => ['30' => ['total_users' => 5000]],
        ]);

        $gads = AnalyticsIntegration::create([
            'provider' => 'google_ads',
            'is_enabled' => true,
            'property_id' => '123-456-7890',
            'metrics_snapshot' => ['30' => ['cost' => 1200]],
        ]);

        $meta = AnalyticsIntegration::create([
            'provider' => 'meta_ads',
            'is_enabled' => true,
            'property_id' => '1234567890',
            'credentials' => json_encode($this->sampleCredentials),
        ]);

        Http::fake([
            'https://graph.facebook.com/*' => Http::response([
                'id' => 'act_1234567890',
                'currency' => 'TRY',
                'data' => [
                    ['spend' => '500.00', 'impressions' => '5000', 'reach' => '4000', 'clicks' => '300'],
                ],
            ], 200),
        ]);

        $service = app(MetaAdsDataFetchService::class);
        $service->syncMetrics();

        $ga4->refresh();
        $gads->refresh();

        $this->assertEquals(['30' => ['total_users' => 5000]], $ga4->metrics_snapshot);
        $this->assertEquals(['30' => ['cost' => 1200]], $gads->metrics_snapshot);
    }

    public function test_admin_form_masks_credentials_plaintext_in_dom(): void
    {
        AnalyticsIntegration::create([
            'provider' => 'meta_ads',
            'is_enabled' => true,
            'property_id' => '1234567890',
            'credentials' => json_encode($this->sampleCredentials),
        ]);

        $component = Livewire::test(AnalyticsCenterPage::class)
            ->set('activeTab', 'integrations');

        $component->assertSet('data.meta_app_secret', '•••••••• [Credential Kayıtlı]');
        $component->assertSet('data.meta_access_token', '•••••••• [Credential Kayıtlı]');
    }
}


