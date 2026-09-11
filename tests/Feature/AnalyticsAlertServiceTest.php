<?php

namespace Tests\Feature;

use App\Models\AnalyticsAlert;
use App\Models\AnalyticsIntegration;
use App\Models\NotFoundLog;
use App\Models\SearchLog;
use App\Models\User;
use App\Services\Analytics\AnalyticsAlertService;
use App\Services\Analytics\AnalyticsInsightService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AnalyticsAlertServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        User::factory()->create([
            'role' => 'administrator',
            'is_active' => true,
        ]);
    }

    public function test_warning_and_critical_insights_create_persistent_alerts()
    {
        // Create 3 zero result searches
        for ($i = 0; $i < 3; $i++) {
            SearchLog::create([
                'search_query' => 'Tefsir Dersleri',
                'normalized_query' => 'tefsir dersleri',
                'result_count' => 0,
                'searched_at' => now(),
            ]);
        }

        $service = app(AnalyticsAlertService::class);
        $result = $service->evaluateAlerts(30, false);

        $this->assertGreaterThan(0, $result['created']);
        $this->assertDatabaseHas('analytics_alerts', [
            'fingerprint' => 'search:zero-result:tefsir-dersleri',
            'severity' => 'warning',
            'status' => 'open',
            'category' => 'search',
        ]);
    }

    public function test_duplicate_fingerprint_updates_last_detected_at_without_duplicate_row()
    {
        for ($i = 0; $i < 3; $i++) {
            SearchLog::create([
                'search_query' => 'Tefsir Dersleri',
                'normalized_query' => 'tefsir dersleri',
                'result_count' => 0,
                'searched_at' => now(),
            ]);
        }

        $service = app(AnalyticsAlertService::class);

        // First evaluation
        $service->evaluateAlerts(30, false);
        $countAfterFirst = AnalyticsAlert::query()->where('fingerprint', 'search:zero-result:tefsir-dersleri')->count();
        $this->assertEquals(1, $countAfterFirst);

        // Second evaluation
        $service->evaluateAlerts(30, false);
        $countAfterSecond = AnalyticsAlert::query()->where('fingerprint', 'search:zero-result:tefsir-dersleri')->count();
        $this->assertEquals(1, $countAfterSecond);
    }

    public function test_auto_resolve_closes_open_alerts_when_issue_disappears()
    {
        // 1. Create alert
        $alert = AnalyticsAlert::create([
            'fingerprint' => 'search:zero-result:eski-arama',
            'type' => 'insight_alert',
            'category' => 'search',
            'severity' => 'warning',
            'title' => 'Sonuçsuz Arama',
            'description' => 'Arama uyarısı',
            'source' => 'Site Araması',
            'status' => 'open',
            'first_detected_at' => now()->subDay(),
            'last_detected_at' => now()->subHours(2),
        ]);

        $service = app(AnalyticsAlertService::class);
        // Evaluate real data (no zero search logs exist)
        $service->evaluateAlerts(30, false);

        $alert->refresh();
        $this->assertEquals('resolved', $alert->status);
        $this->assertNotNull($alert->resolved_at);
    }

    public function test_dismissed_alert_remains_muted_and_can_be_reopened()
    {
        $alert = AnalyticsAlert::create([
            'fingerprint' => 'search:zero-result:tefsir-dersleri',
            'type' => 'insight_alert',
            'category' => 'search',
            'severity' => 'warning',
            'title' => 'Sonuçsuz Arama',
            'description' => 'Arama uyarısı',
            'source' => 'Site Araması',
            'status' => 'dismissed',
            'first_detected_at' => now()->subDays(2),
            'last_detected_at' => now()->subDay(),
            'dismissed_at' => now()->subDay(),
        ]);

        for ($i = 0; $i < 3; $i++) {
            SearchLog::create([
                'search_query' => 'Tefsir Dersleri',
                'normalized_query' => 'tefsir dersleri',
                'result_count' => 0,
                'searched_at' => now(),
            ]);
        }

        $service = app(AnalyticsAlertService::class);
        $service->evaluateAlerts(30, false);

        $alert->refresh();
        // Should remain dismissed because admin manually muted it
        $this->assertEquals('dismissed', $alert->status);
    }

    public function test_stale_active_integration_creates_alert_but_inactive_does_not()
    {
        // Stale active integration
        AnalyticsIntegration::create([
            'provider' => 'ga4',
            'is_enabled' => true,
            'property_id' => '123456',
            'last_synced_at' => now()->subHours(5),
            'last_error' => 'Connection timeout',
        ]);

        // Inactive integration
        AnalyticsIntegration::create([
            'provider' => 'google_ads',
            'is_enabled' => false,
            'property_id' => '999999',
            'last_synced_at' => now()->subDays(10),
        ]);

        $service = app(AnalyticsAlertService::class);
        $service->evaluateAlerts(30, false);

        $this->assertDatabaseHas('analytics_alerts', [
            'fingerprint' => 'integration:error:ga4',
            'status' => 'open',
        ]);

        $this->assertDatabaseMissing('analytics_alerts', [
            'fingerprint' => 'integration:stale:google_ads',
        ]);
    }

    public function test_prune_command_does_not_delete_open_alerts()
    {
        // Old OPEN alert (300 days old)
        $openAlert = AnalyticsAlert::create([
            'fingerprint' => 'search:zero-result:open-query',
            'type' => 'insight_alert',
            'category' => 'search',
            'severity' => 'warning',
            'title' => 'Açık Uyarı',
            'description' => 'Açık kalmalı',
            'source' => 'Site Araması',
            'status' => 'open',
            'first_detected_at' => now()->subDays(300),
            'last_detected_at' => now()->subDays(300),
        ]);
        AnalyticsAlert::where('id', $openAlert->id)->update(['updated_at' => now()->subDays(300)]);

        // Old RESOLVED alert (200 days old)
        $resolvedAlert = AnalyticsAlert::create([
            'fingerprint' => 'search:zero-result:old-resolved',
            'type' => 'insight_alert',
            'category' => 'search',
            'severity' => 'warning',
            'title' => 'Eski Çözülen',
            'description' => 'Silinmeli',
            'source' => 'Site Araması',
            'status' => 'resolved',
            'first_detected_at' => now()->subDays(200),
            'last_detected_at' => now()->subDays(200),
            'resolved_at' => now()->subDays(200),
        ]);
        AnalyticsAlert::where('id', $resolvedAlert->id)->update(['updated_at' => now()->subDays(200)]);

        $this->artisan('analytics:prune --days-alerts=180')->assertExitCode(0);

        // OPEN alert must NOT be deleted
        $this->assertDatabaseHas('analytics_alerts', ['id' => $openAlert->id]);
        // RESOLVED alert beyond 180 days MUST be deleted
        $this->assertDatabaseMissing('analytics_alerts', ['id' => $resolvedAlert->id]);
    }

    public function test_livewire_analytics_center_page_actions_tab()
    {
        NotFoundLog::create([
            'path' => '/eski-sayfa',
            'hit_count' => 10,
            'last_occurred_at' => now(),
        ]);

        $alert = AnalyticsAlert::create([
            'fingerprint' => 'technical:404:eski-sayfa',
            'type' => 'insight_alert',
            'category' => 'technical',
            'severity' => 'warning',
            'title' => 'Yüksek 404 Hatası: /eski-sayfa',
            'description' => '/eski-sayfa adresi 404 verdi.',
            'source' => '404',
            'status' => 'open',
            'first_detected_at' => now(),
            'last_detected_at' => now(),
        ]);

        Livewire::test(\App\Filament\Pages\AnalyticsCenterPage::class)
            ->set('activeTab', 'actions')
            ->assertSee('Açık Aksiyonlar')
            ->assertSee('Yüksek 404 Hatası')
            ->call('dismissAlert', $alert->id)
            ->assertNotified('Aksiyon Kapatıldı');

        $alert->refresh();
        $this->assertEquals('dismissed', $alert->status);

        Livewire::test(\App\Filament\Pages\AnalyticsCenterPage::class)
            ->set('activeTab', 'actions')
            ->call('reopenAlert', $alert->id)
            ->assertNotified('Aksiyon Tekrar Açıldı');

        $alert->refresh();
        $this->assertEquals('open', $alert->status);
    }

    public function test_auto_resolve_protection_when_integration_has_error()
    {
        // Google Ads open alert
        $alert = AnalyticsAlert::create([
            'fingerprint' => 'google_ads:high-spend-zero-conv',
            'type' => 'insight_alert',
            'category' => 'ads',
            'severity' => 'warning',
            'title' => 'Google Ads Harcama Uyarısı',
            'description' => 'Harcama var dönüşüm yok',
            'source' => 'Google Ads',
            'status' => 'open',
            'first_detected_at' => now()->subDay(),
            'last_detected_at' => now()->subDay(),
        ]);

        // Integration is enabled but has last_error
        AnalyticsIntegration::create([
            'provider' => 'google_ads',
            'is_enabled' => true,
            'property_id' => '123456',
            'last_error' => 'API quota exceeded',
            'metrics_snapshot' => [],
        ]);

        $service = app(AnalyticsAlertService::class);
        $service->evaluateAlerts(30, false);

        $alert->refresh();
        // MUST NOT be falsely auto-resolved because integration had sync error
        $this->assertEquals('open', $alert->status);
    }
}

