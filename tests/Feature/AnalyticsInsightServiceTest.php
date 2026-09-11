<?php

namespace Tests\Feature;

use App\Filament\Pages\AnalyticsCenterPage;
use App\Models\AnalyticsIntegration;
use App\Models\NotFoundLog;
use App\Models\SearchLog;
use App\Models\SiteEvent;
use App\Models\User;
use App\Services\Analytics\AnalyticsInsightService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use Tests\TestCase;

class AnalyticsInsightServiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_zero_result_search_generates_warning_insight(): void
    {
        for ($i = 0; $i < 5; $i++) {
            SearchLog::create([
                'search_query' => 'Tefsir Dersleri',
                'normalized_query' => 'tefsir dersleri',
                'result_count' => 0,
                'searched_at' => now(),
            ]);
        }

        $service = app(AnalyticsInsightService::class);
        $insights = $service->generateInsights(30);

        $this->assertNotEmpty($insights['all']);
        $warning = current(array_filter($insights['all'], fn ($i) => str_contains($i['title'], 'Tefsir Dersleri')));
        $this->assertNotFalse($warning);
        $this->assertEquals('warning', $warning['severity']);
        $this->assertEquals('Site Araması', $warning['source']);
        $this->assertEquals('searches', $warning['action_target']['tab']);
    }

    public function test_rising_404_log_generates_technical_warning(): void
    {
        NotFoundLog::create([
            'path' => '/programlar/eski-canli-yayin',
            'hit_count' => 15,
            'last_occurred_at' => now(),
        ]);

        $service = app(AnalyticsInsightService::class);
        $insights = $service->generateInsights(30);

        $techInsight = current(array_filter($insights['all'], fn ($i) => $i['category'] === 'technical'));
        $this->assertNotFalse($techInsight);
        $this->assertEquals('warning', $techInsight['severity']);
        $this->assertStringContainsString('/programlar/eski-canli-yayin', $techInsight['title']);
    }

    public function test_meta_ads_high_frequency_generates_warning(): void
    {
        AnalyticsIntegration::create([
            'provider' => 'meta_ads',
            'is_enabled' => true,
            'property_id' => '1234567890',
            'metrics_snapshot' => [
                '30' => [
                    'spend' => 1500,
                    'impressions' => 30000,
                    'reach' => 8000,
                    'clicks' => 500,
                    'frequency' => 3.75, // > 3.0 threshold
                ],
            ],
        ]);

        $service = app(AnalyticsInsightService::class);
        $insights = $service->generateInsights(30);

        $metaInsight = current(array_filter($insights['all'], fn ($i) => $i['id'] === 'meta_high_frequency'));
        $this->assertNotFalse($metaInsight);
        $this->assertEquals('warning', $metaInsight['severity']);
        $this->assertStringContainsString('Yüksek Frekans Uyarısı', $metaInsight['title']);
    }

    public function test_stale_sync_generates_system_warning(): void
    {
        AnalyticsIntegration::create([
            'provider' => 'ga4',
            'is_enabled' => true,
            'property_id' => 'ga4_321',
            'last_synced_at' => now()->subHours(5), // > 3 hours
        ]);

        $service = app(AnalyticsInsightService::class);
        $insights = $service->generateInsights(30);

        $staleInsight = current(array_filter($insights['all'], fn ($i) => str_contains($i['id'], 'sync_stale')));
        $this->assertNotFalse($staleInsight);
        $this->assertEquals('info', $staleInsight['severity']);
        $this->assertStringContainsString('Verisi Güncellenmedi', $staleInsight['title']);
    }

    public function test_fixture_mode_disabled_in_production(): void
    {
        Config::set('app.env', 'production');

        $service = app(AnalyticsInsightService::class);
        $insights = $service->generateInsights(30, true);

        // Production environment must never output mock fixture items
        $fixtureItem = current(array_filter($insights['all'], fn ($i) => str_contains($i['id'], 'fix_')));
        $this->assertFalse($fixtureItem);
    }

    public function test_insights_tab_rendered_on_analytics_center_page(): void
    {
        SearchLog::create([
            'search_query' => 'Kuran Dersi',
            'normalized_query' => 'kuran dersi',
            'result_count' => 0,
            'searched_at' => now(),
        ]);

        Livewire::test(AnalyticsCenterPage::class)
            ->set('activeTab', 'insights')
            ->assertSee('Bugün Neye Bakmalıyım?')
            ->assertSee('Fırsatlar')
            ->assertSee('Dikkat Edilmesi Gerekenler')
            ->assertSee('Kategoriye Göre Tüm İçgörüler');
    }
}
