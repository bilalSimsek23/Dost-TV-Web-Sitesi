<?php

namespace Tests\Feature;

use App\Models\AnalyticsIntegration;
use App\Models\Program;
use App\Models\User;
use App\Services\Analytics\Ga4DataFetchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class AnalyticsGa4IntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected string $sampleServiceAccountJson;

    protected function setUp(): void
    {
        parent::setUp();

        // Generate mock RSA key pair for testing OpenSSL JWT signing
        $res = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        openssl_pkey_export($res, $privKey);

        $this->sampleServiceAccountJson = json_encode([
            'type' => 'service_account',
            'project_id' => 'dost-tv-test',
            'private_key_id' => '1234567890abcdef',
            'private_key' => $privKey,
            'client_email' => 'ga4-service@dost-tv-test.iam.gserviceaccount.com',
            'client_id' => '123456789',
            'token_uri' => 'https://oauth2.googleapis.com/token',
        ]);
    }

    public function test_test_connection_returns_success_when_api_responds_ok(): void
    {
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'ya29.mock_access_token_12345',
                'expires_in' => 3600,
            ], 200),
            'https://analyticsdata.googleapis.com/v1beta/properties/312456789:runReport' => Http::response([
                'rows' => [
                    ['metricValues' => [['value' => '42']]],
                ],
            ], 200),
        ]);

        $service = app(Ga4DataFetchService::class);
        $result = $service->testConnection('312456789', $this->sampleServiceAccountJson);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('bağlantısı başarılı', $result['message']);
    }

    public function test_test_connection_sanitizes_secret_keys_and_tokens_on_error(): void
    {
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'ya29.secret_token_val_9999',
                'expires_in' => 3600,
            ], 200),
            'https://analyticsdata.googleapis.com/v1beta/properties/312456789:runReport' => Http::response([
                'error' => [
                    'message' => 'Access denied for private key -----BEGIN PRIVATE KEY----- SECRET_DATA -----END PRIVATE KEY----- and token ya29.secret_token_val_9999',
                ],
            ], 403),
        ]);

        $service = app(Ga4DataFetchService::class);
        $result = $service->testConnection('312456789', $this->sampleServiceAccountJson);

        $this->assertFalse($result['success']);
        $this->assertStringNotContainsString('SECRET_DATA', $result['message']);
        $this->assertStringNotContainsString('ya29.secret_token_val_9999', $result['message']);
        $this->assertStringContainsString('[REDACTED', $result['message']);
    }

    public function test_sync_metrics_fetches_and_saves_7_30_90_day_snapshots_with_program_mapping(): void
    {
        // Create actual Program model in DB
        $program = Program::create([
            'name' => 'Gönül Dostları',
            'slug' => 'gonul-dostlari',
            'status' => 'active',
            'show_on_public' => true,
        ]);

        $integration = AnalyticsIntegration::create([
            'provider' => 'ga4',
            'is_enabled' => true,
            'property_id' => '312456789',
            'credentials' => $this->sampleServiceAccountJson,
        ]);

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'ya29.mock_token',
                'expires_in' => 3600,
            ], 200),
            'https://analyticsdata.googleapis.com/v1beta/properties/312456789:batchRunReports' => Http::response([
                'reports' => [
                    // Report 0: KPIs
                    [
                        'rows' => [
                            [
                                'metricValues' => [
                                    ['value' => '12500'], // totalUsers
                                    ['value' => '1420'],  // activeUsers
                                    ['value' => '18400'], // sessions
                                    ['value' => '45200'], // pageviews
                                    ['value' => '92.4'],  // averageSessionDuration (92.4 secs => 1:32)
                                ],
                            ],
                        ],
                    ],
                    // Report 1: Timeseries
                    [
                        'rows' => [
                            [
                                'dimensionValues' => [['value' => '20260901']],
                                'metricValues' => [['value' => '420'], ['value' => '1500']],
                            ],
                            [
                                'dimensionValues' => [['value' => '20260902']],
                                'metricValues' => [['value' => '480'], ['value' => '1650']],
                            ],
                        ],
                    ],
                    // Report 2: Devices
                    [
                        'rows' => [
                            [
                                'dimensionValues' => [['value' => 'mobile']],
                                'metricValues' => [['value' => '60']],
                            ],
                            [
                                'dimensionValues' => [['value' => 'desktop']],
                                'metricValues' => [['value' => '30']],
                            ],
                            [
                                'dimensionValues' => [['value' => 'tablet']],
                                'metricValues' => [['value' => '10']],
                            ],
                        ],
                    ],
                    // Report 3: Traffic
                    [
                        'rows' => [
                            [
                                'dimensionValues' => [['value' => 'Organic Search']],
                                'metricValues' => [['value' => '5200']],
                            ],
                            [
                                'dimensionValues' => [['value' => 'Direct']],
                                'metricValues' => [['value' => '4100']],
                            ],
                        ],
                    ],
                    // Report 4: Top Pages
                    [
                        'rows' => [
                            [
                                'dimensionValues' => [['value' => '/programlar/gonul-dostlari'], ['value' => 'Gönül Dostları Detay']],
                                'metricValues' => [['value' => '14200']],
                            ],
                            [
                                'dimensionValues' => [['value' => '/canli-tv'], ['value' => 'Canlı TV']],
                                'metricValues' => [['value' => '8900']],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = app(Ga4DataFetchService::class);
        $success = $service->syncMetrics();

        $this->assertTrue($success);

        $fresh = $integration->fresh();
        $this->assertNotNull($fresh->last_synced_at);
        $this->assertNull($fresh->last_error);

        $snapshot = $fresh->metrics_snapshot;
        $this->assertArrayHasKey('7', $snapshot);
        $this->assertArrayHasKey('30', $snapshot);
        $this->assertArrayHasKey('90', $snapshot);

        $metrics30 = $snapshot['30'];
        $this->assertEquals(12500, $metrics30['total_users']);
        $this->assertEquals(1420, $metrics30['active_users']);
        $this->assertEquals('1:32', $metrics30['avg_session_duration']);

        // Test devices calculation
        $this->assertEquals(60, $metrics30['devices']['mobile']['percentage']);
        $this->assertEquals(30, $metrics30['devices']['desktop']['percentage']);

        // Test top pages & program mapping
        $this->assertTrue($metrics30['top_pages'][0]['is_program']);
        $this->assertEquals('Gönül Dostları', $metrics30['top_pages'][0]['program_name']);
        $this->assertCount(1, $metrics30['top_programs']);
        $this->assertEquals('Gönül Dostları', $metrics30['top_programs'][0]['name']);
    }

    public function test_sync_metrics_preserves_last_valid_snapshot_when_error_occurs(): void
    {
        $existingSnapshot = [
            '30' => [
                'total_users' => 9999,
                'pageviews' => 8888,
            ],
        ];

        $integration = AnalyticsIntegration::create([
            'provider' => 'ga4',
            'is_enabled' => true,
            'property_id' => '312456789',
            'credentials' => $this->sampleServiceAccountJson,
            'metrics_snapshot' => $existingSnapshot,
        ]);

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['error' => 'invalid_grant'], 400),
        ]);

        $service = app(Ga4DataFetchService::class);
        $success = $service->syncMetrics();

        $this->assertFalse($success);

        $fresh = $integration->fresh();
        $this->assertNotNull($fresh->last_error);
        $this->assertStringContainsString('invalid_grant', $fresh->last_error);
        // Existing snapshot MUST be preserved
        $this->assertEquals($existingSnapshot, $fresh->metrics_snapshot);
    }

    public function test_admin_form_masks_credentials_plaintext_in_dom(): void
    {
        $admin = User::factory()->create([
            'role' => 'administrator',
            'is_active' => true,
        ]);

        AnalyticsIntegration::create([
            'provider' => 'ga4',
            'is_enabled' => true,
            'property_id' => '312456789',
            'credentials' => $this->sampleServiceAccountJson,
        ]);

        $component = Livewire::actingAs($admin)
            ->test(\App\Filament\Pages\AnalyticsCenterPage::class)
            ->set('activeTab', 'integrations');

        // Form field should display placeholder mask instead of raw secret key JSON
        $component->assertSet('data.ga4_credentials', '•••••••• [Credential Kayıtlı]');
    }
}
