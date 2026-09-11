<?php

namespace Tests\Feature;

use App\Models\AnalyticsIntegration;
use App\Models\NotFoundLog;
use App\Models\SearchLog;
use App\Models\SiteEvent;
use App\Models\User;
use App\Services\Analytics\AnalyticsService;
use App\Services\Analytics\Ga4DataFetchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AnalyticsCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_log_search_records_anonymous_queries_and_zero_results(): void
    {
        $service = app(AnalyticsService::class);

        $service->logSearch('mektubat', 5, 'desktop', '/search');
        $service->logSearch('ruyalarin dili', 0, 'mobile', '/search');

        $this->assertDatabaseHas('search_logs', [
            'search_query' => 'mektubat',
            'normalized_query' => 'mektubat',
            'result_count' => 5,
            'device_type' => 'desktop',
        ]);

        $this->assertDatabaseHas('search_logs', [
            'search_query' => 'ruyalarin dili',
            'result_count' => 0,
            'device_type' => 'mobile',
        ]);

        $this->assertEquals(1, SearchLog::query()->where('result_count', 0)->count());
    }

    public function test_log_event_records_anonymous_user_interactions(): void
    {
        $service = app(AnalyticsService::class);

        $service->logEvent('hero_program_click', 'program', 12, ['slide_index' => 1]);

        $this->assertDatabaseHas('site_events', [
            'event_name' => 'hero_program_click',
            'entity_type' => 'program',
            'entity_id' => 12,
        ]);
    }

    public function test_track_event_api_endpoint(): void
    {
        $response = $this->postJson(route('api.track-event'), [
            'event_name' => 'live_tv_click',
            'entity_type' => 'live_stream',
            'entity_id' => 1,
            'metadata' => ['source' => 'header_button'],
        ]);

        $response->assertStatus(200)->assertJson(['status' => 'ok']);

        $this->assertDatabaseHas('site_events', [
            'event_name' => 'live_tv_click',
            'entity_type' => 'live_stream',
            'entity_id' => 1,
        ]);
    }

    public function test_log_not_found_increments_hit_count(): void
    {
        $service = app(AnalyticsService::class);

        $service->logNotFound('/gecersiz-sayfa-url', 'https://google.com');
        $service->logNotFound('/gecersiz-sayfa-url', 'https://google.com');

        $this->assertDatabaseHas('not_found_logs', [
            'path' => '/gecersiz-sayfa-url',
            'hit_count' => 2,
        ]);
    }

    public function test_analytics_integration_encrypts_credentials(): void
    {
        $secretJson = '{"type":"service_account","private_key":"SECRET_KEY_12345"}';

        $integration = AnalyticsIntegration::create([
            'provider' => 'ga4',
            'is_enabled' => true,
            'property_id' => '123456789',
            'credentials' => $secretJson,
        ]);

        // Raw database column value should NOT contain plain secret string
        $rawDatabaseRow = \Illuminate\Support\Facades\DB::table('analytics_integrations')->where('id', $integration->id)->first();
        $this->assertFalse(str_contains($rawDatabaseRow->credentials, 'SECRET_KEY_12345'));

        // Decrypted model attribute should return exact secret
        $this->assertEquals($secretJson, $integration->fresh()->credentials);
    }

    public function test_ga4_service_gracefully_records_error_without_crashing(): void
    {
        $integration = AnalyticsIntegration::create([
            'provider' => 'ga4',
            'is_enabled' => true,
            'property_id' => '123456789',
            'credentials' => 'invalid_json_format',
        ]);

        $service = app(Ga4DataFetchService::class);
        $result = $service->syncMetrics();

        $this->assertFalse($result);
        $this->assertNotNull($integration->fresh()->last_error);
    }

    public function test_authorized_admin_can_access_analytics_center_page(): void
    {
        $admin = User::factory()->create([
            'role' => 'administrator',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get('/admin/analytics-center-page');
        $response->assertStatus(200);

        Livewire::actingAs($admin)
            ->test(\App\Filament\Pages\AnalyticsCenterPage::class)
            ->set('activeTab', 'searches')
            ->assertStatus(200);
    }

    public function test_track_event_endpoint_rejects_non_whitelisted_events(): void
    {
        $response = $this->postJson(route('api.track-event'), [
            'event_name' => 'invalid_junk_event',
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('site_events', [
            'event_name' => 'invalid_junk_event',
        ]);
    }

    public function test_404_scanner_bot_filter_ignores_bot_paths(): void
    {
        $this->get('/wp-admin/index.php')->assertStatus(404);
        $this->get('/.env')->assertStatus(404);
        $this->get('/xmlrpc.php')->assertStatus(404);

        $this->assertDatabaseMissing('not_found_logs', ['path' => '/wp-admin/index.php']);
        $this->assertDatabaseMissing('not_found_logs', ['path' => '/.env']);
        $this->assertDatabaseMissing('not_found_logs', ['path' => '/xmlrpc.php']);

        $this->get('/programlar/gecersiz-slug-999')->assertStatus(404);
        $this->assertDatabaseHas('not_found_logs', ['path' => '/programlar/gecersiz-slug-999']);
    }

    public function test_search_pii_masking_masks_emails_phones_and_tcs(): void
    {
        $service = app(AnalyticsService::class);

        $logEmail = $service->logSearch('ali@example.com sohbet', 2);
        $this->assertStringContainsString('***@***', $logEmail->search_query);

        $logPhone = $service->logSearch('arama 05551234567 bilgi', 1);
        $this->assertStringContainsString('***PHONE***', $logPhone->search_query);

        $logTc = $service->logSearch('bilgi 12345678901 arama', 1);
        $this->assertStringContainsString('***TC***', $logTc->search_query);

        $logNormal = $service->logSearch('Hikmet Arayışları', 5);
        $this->assertEquals('Hikmet Arayışları', $logNormal->search_query);
    }

    public function test_search_pagination_prevents_duplicate_logs(): void
    {
        $this->get('/arama?q=hikmet');
        $this->get('/arama?q=hikmet&page=2');
        $this->get('/arama?q=hikmet&page=3');

        $this->assertEquals(1, SearchLog::query()->where('search_query', 'hikmet')->count());
    }

    public function test_analytics_prune_command_deletes_old_records(): void
    {
        SearchLog::create(['search_query' => 'old search', 'normalized_query' => 'old search', 'searched_at' => now()->subDays(200)]);
        SearchLog::create(['search_query' => 'recent search', 'normalized_query' => 'recent search', 'searched_at' => now()->subDays(10)]);

        SiteEvent::create(['event_name' => 'hero_program_click', 'occurred_at' => now()->subDays(200)]);
        SiteEvent::create(['event_name' => 'hero_program_click', 'occurred_at' => now()->subDays(10)]);

        NotFoundLog::create(['path' => '/old-404', 'hit_count' => 1, 'last_occurred_at' => now()->subDays(400)]);
        NotFoundLog::create(['path' => '/new-404', 'hit_count' => 1, 'last_occurred_at' => now()->subDays(10)]);

        $this->artisan('analytics:prune')->assertExitCode(0);

        $this->assertDatabaseMissing('search_logs', ['search_query' => 'old search']);
        $this->assertDatabaseHas('search_logs', ['search_query' => 'recent search']);

        $this->assertDatabaseMissing('not_found_logs', ['path' => '/old-404']);
        $this->assertDatabaseHas('not_found_logs', ['path' => '/new-404']);
    }
}
