<?php

namespace Tests\Feature;

use App\Models\AnalyticsIntegration;
use App\Models\User;
use App\Services\Analytics\GoogleAdsDataFetchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class AnalyticsGoogleAdsIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected array $sampleCredentials;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sampleCredentials = [
            'login_customer_id' => '987-654-3210',
            'developer_token' => 'DEV_TOKEN_12345',
            'client_id' => '123456.apps.googleusercontent.com',
            'client_secret' => 'GOCSXX-secret-key-99',
            'refresh_token' => '1//04refreshtokenval999',
        ];
    }

    public function test_test_connection_returns_success_when_api_responds_ok(): void
    {
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'ya29.mock_gads_token_123',
                'expires_in' => 3600,
            ], 200),
            'https://googleads.googleapis.com/v19/customers/1234567890/googleAds:search' => Http::response([
                'results' => [
                    [
                        'customer' => [
                            'id' => '1234567890',
                            'descriptiveName' => 'DOST TV Ads Account',
                            'currencyCode' => 'TRY',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = app(GoogleAdsDataFetchService::class);
        $result = $service->testConnection('123-456-7890', '987-654-3210', json_encode($this->sampleCredentials));

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('bağlantısı başarılı', $result['message']);
    }

    public function test_test_connection_sanitizes_secret_tokens_on_error(): void
    {
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'error' => 'invalid_grant',
                'error_description' => 'Bad Refresh Token 1//04refreshtokenval999 with secret GOCSXX-secret-key-99',
            ], 400),
        ]);

        $service = app(GoogleAdsDataFetchService::class);
        $result = $service->testConnection('123-456-7890', null, json_encode($this->sampleCredentials));

        $this->assertFalse($result['success']);
        $this->assertStringNotContainsString('GOCSXX-secret-key-99', $result['message']);
        $this->assertStringNotContainsString('1//04refreshtokenval999', $result['message']);
        $this->assertStringContainsString('[REDACTED', $result['message']);
    }

    public function test_sync_metrics_fetches_and_saves_7_30_90_day_snapshots_with_micros_conversion(): void
    {
        $integration = AnalyticsIntegration::create([
            'provider' => 'google_ads',
            'is_enabled' => true,
            'property_id' => '123-456-7890',
            'credentials' => json_encode($this->sampleCredentials),
        ]);

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'ya29.mock_token',
                'expires_in' => 3600,
            ], 200),
            'https://googleads.googleapis.com/v19/customers/1234567890/googleAds:search' => Http::response([
                'results' => [
                    [
                        'campaign' => [
                            'id' => '987654321',
                            'name' => 'DOST TV Ramazan Kampanyası',
                            'status' => 'ENABLED',
                        ],
                        'customer' => [
                            'currencyCode' => 'TRY',
                        ],
                        'metrics' => [
                            'impressions' => '50000',
                            'clicks' => '1500',
                            'costMicros' => '750000000', // 750.00 TRY
                            'conversions' => 120.0,
                            'conversionsValue' => 0.0,
                            'ctr' => 0.03,
                            'averageCpc' => 500000.0,
                        ],
                        'segments' => [
                            'date' => '2026-09-01',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = app(GoogleAdsDataFetchService::class);
        $success = $service->syncMetrics();

        $this->assertTrue($success);

        $fresh = $integration->fresh();
        $this->assertNotNull($fresh->last_synced_at);
        $this->assertNull($fresh->last_error);

        $snapshot = $fresh->metrics_snapshot;
        $this->assertArrayHasKey('7', $snapshot);
        $this->assertArrayHasKey('30', $snapshot);
        $this->assertArrayHasKey('90', $snapshot);

        $m30 = $snapshot['30'];
        $this->assertEquals(50000, $m30['impressions']);
        $this->assertEquals(1500, $m30['clicks']);
        $this->assertEquals(750.00, $m30['cost']);
        $this->assertEquals('TRY', $m30['currency']);
        $this->assertEquals(3.0, $m30['ctr']); // 1500 / 50000 * 100
        $this->assertEquals(0.50, $m30['average_cpc']); // 750 / 1500

        // Test Campaign mapping
        $this->assertCount(1, $m30['campaigns']);
        $this->assertEquals('DOST TV Ramazan Kampanyası', $m30['campaigns'][0]['name']);
        $this->assertEquals('Aktif', $m30['campaigns'][0]['status_label']);
        $this->assertEquals(750.00, $m30['campaigns'][0]['cost']);
    }

    public function test_sync_metrics_preserves_last_valid_snapshot_when_error_occurs(): void
    {
        $existingSnapshot = [
            '30' => [
                'cost' => 500.00,
                'clicks' => 1000,
            ],
        ];

        $integration = AnalyticsIntegration::create([
            'provider' => 'google_ads',
            'is_enabled' => true,
            'property_id' => '123-456-7890',
            'credentials' => json_encode($this->sampleCredentials),
            'metrics_snapshot' => $existingSnapshot,
        ]);

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['error' => 'invalid_grant'], 400),
        ]);

        $service = app(GoogleAdsDataFetchService::class);
        $success = $service->syncMetrics();

        $this->assertFalse($success);

        $fresh = $integration->fresh();
        $this->assertNotNull($fresh->last_error);
        $this->assertStringContainsString('invalid_grant', $fresh->last_error);
        // Existing snapshot MUST be preserved
        $this->assertEquals($existingSnapshot, $fresh->metrics_snapshot);
    }

    public function test_google_ads_sync_does_not_affect_ga4_snapshot(): void
    {
        $ga4Snapshot = ['30' => ['total_users' => 12345]];

        $ga4Integration = AnalyticsIntegration::create([
            'provider' => 'ga4',
            'is_enabled' => true,
            'property_id' => '312456789',
            'metrics_snapshot' => $ga4Snapshot,
        ]);

        $gadsIntegration = AnalyticsIntegration::create([
            'provider' => 'google_ads',
            'is_enabled' => true,
            'property_id' => '123-456-7890',
            'credentials' => json_encode($this->sampleCredentials),
        ]);

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['error' => 'developer_token_invalid'], 400),
        ]);

        $service = app(GoogleAdsDataFetchService::class);
        $service->syncMetrics();

        // GA4 snapshot must remain 100% untouched
        $this->assertEquals($ga4Snapshot, $ga4Integration->fresh()->metrics_snapshot);
    }

    public function test_admin_form_masks_credentials_plaintext_in_dom(): void
    {
        $admin = User::factory()->create([
            'role' => 'administrator',
            'is_active' => true,
        ]);

        AnalyticsIntegration::create([
            'provider' => 'google_ads',
            'is_enabled' => true,
            'property_id' => '123-456-7890',
            'credentials' => json_encode($this->sampleCredentials),
        ]);

        $component = Livewire::actingAs($admin)
            ->test(\App\Filament\Pages\AnalyticsCenterPage::class)
            ->set('activeTab', 'integrations');

        // Secret form fields should display placeholder mask instead of raw secret key
        $component->assertSet('data.gads_developer_token', '•••••••• [Credential Kayıtlı]');
        $component->assertSet('data.gads_client_secret', '•••••••• [Credential Kayıtlı]');
        $component->assertSet('data.gads_refresh_token', '•••••••• [Credential Kayıtlı]');
    }
}
