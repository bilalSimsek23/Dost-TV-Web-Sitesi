<?php

namespace App\Services\Analytics;

use App\Models\AnalyticsIntegration;
use App\Models\NotFoundLog;
use App\Models\SearchLog;
use App\Models\SiteEvent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AnalyticsInsightService
{
    /**
     * Generates and returns structured insights for Analiz Merkezi.
     */
    public function generateInsights(int $days = 30, bool $useFixture = false): array
    {
        $days = in_array($days, [7, 30, 90], true) ? $days : 30;

        // Never allow fixture mode in production
        if (config('app.env') === 'production') {
            $useFixture = false;
        }

        $cacheKey = "analytics_insights_{$days}_".($useFixture ? 'fixture' : 'real');
        $ttl = config('analytics.insights.cache_ttl_seconds', 600);

        return Cache::remember($cacheKey, $ttl, function () use ($days, $useFixture) {
            return $this->buildInsights($days, $useFixture);
        });
    }

    /**
     * Clears all cached insight calculations.
     */
    public static function clearCache(): void
    {
        foreach ([7, 30, 90] as $d) {
            Cache::forget("analytics_insights_{$d}_real");
            Cache::forget("analytics_insights_{$d}_fixture");
        }
    }

    /**
     * Helper to compute percentage change safely.
     */
    public function percentageChange($current, $previous): array
    {
        $curr = (float) $current;
        $prev = (float) $previous;

        if ($prev == 0.0) {
            if ($curr > 0) {
                return ['val' => 100.0, 'label' => 'Yeni', 'direction' => 'up'];
            }

            return ['val' => 0.0, 'label' => '0%', 'direction' => 'neutral'];
        }

        $diff = (($curr - $prev) / $prev) * 100;
        $val = round(abs($diff), 1);
        $direction = $diff > 0 ? 'up' : ($diff < 0 ? 'down' : 'neutral');
        $sign = $diff > 0 ? '+' : ($diff < 0 ? '-' : '');

        return [
            'val' => $val,
            'label' => "{$sign}{$val}%",
            'direction' => $direction,
        ];
    }

    /**
     * Internal insight builder logic.
     */
    protected function buildInsights(int $days, bool $useFixture): array
    {
        $allInsights = [];

        // 1. Search Behavior Insights
        $searchInsights = $this->generateSearchInsights($days);
        $allInsights = array_merge($allInsights, $searchInsights);

        // 2. 404 Technical Insights
        $technicalInsights = $this->generateTechnicalInsights($days);
        $allInsights = array_merge($allInsights, $technicalInsights);

        // 3. Site Event & Content Performance Insights
        $contentInsights = $this->generateContentInsights($days);
        $allInsights = array_merge($allInsights, $contentInsights);

        // 4. GA4 Integration Insights
        $ga4Insights = $this->generateGa4Insights($days);
        $allInsights = array_merge($allInsights, $ga4Insights);

        // 5. Google Ads Integration Insights
        $gadsInsights = $this->generateGoogleAdsInsights($days);
        $allInsights = array_merge($allInsights, $gadsInsights);

        // 6. Meta Ads Integration Insights
        $metaInsights = $this->generateMetaAdsInsights($days);
        $allInsights = array_merge($allInsights, $metaInsights);

        // 7. System Sync Health Insights
        $syncInsights = $this->generateSyncHealthInsights();
        $allInsights = array_merge($allInsights, $syncInsights);

        // 8. Fixture / Demo Data (Non-production only when requested)
        if ($useFixture && empty($allInsights)) {
            $allInsights = $this->generateFixtureInsights($days);
        }

        // Priority sorting (critical -> warning -> positive -> info)
        $severityWeight = [
            'critical' => 1,
            'warning' => 2,
            'positive' => 3,
            'info' => 4,
        ];

        usort($allInsights, fn ($a, $b) => ($severityWeight[$a['severity']] ?? 5) <=> ($severityWeight[$b['severity']] ?? 5));

        $topPriority = array_slice($allInsights, 0, 3);
        $opportunities = array_values(array_filter($allInsights, fn ($i) => in_array($i['severity'], ['positive', 'info'], true)));
        $attentionNeeded = array_values(array_filter($allInsights, fn ($i) => in_array($i['severity'], ['warning', 'critical'], true)));

        $byCategory = [
            'content' => array_values(array_filter($allInsights, fn ($i) => $i['category'] === 'content')),
            'search' => array_values(array_filter($allInsights, fn ($i) => $i['category'] === 'search')),
            'traffic' => array_values(array_filter($allInsights, fn ($i) => $i['category'] === 'traffic')),
            'ads' => array_values(array_filter($allInsights, fn ($i) => $i['category'] === 'ads')),
            'technical' => array_values(array_filter($allInsights, fn ($i) => $i['category'] === 'technical')),
        ];

        return [
            'summary' => [
                'critical_count' => count(array_filter($allInsights, fn ($i) => $i['severity'] === 'critical')),
                'warning_count' => count(array_filter($allInsights, fn ($i) => $i['severity'] === 'warning')),
                'positive_count' => count(array_filter($allInsights, fn ($i) => $i['severity'] === 'positive')),
                'total_count' => count($allInsights),
            ],
            'top_priority' => $topPriority,
            'opportunities' => $opportunities,
            'attention_needed' => $attentionNeeded,
            'by_category' => $byCategory,
            'all' => $allInsights,
        ];
    }

    /**
     * Generates insights based on search_logs.
     */
    protected function generateSearchInsights(int $days): array
    {
        $insights = [];
        $fromDate = now()->subDays($days);
        $prevDate = now()->subDays($days * 2);

        // High volume zero-result searches
        $zeroMin = config('analytics.insights.search_zero_warning_min', 3);
        $zeroSearches = SearchLog::query()
            ->where('searched_at', '>=', $fromDate)
            ->where('result_count', 0)
            ->select('normalized_query', 'search_query', DB::raw('count(*) as total_count'))
            ->groupBy('normalized_query', 'search_query')
            ->having('total_count', '>=', $zeroMin)
            ->orderByDesc('total_count')
            ->limit(3)
            ->get();

        foreach ($zeroSearches as $z) {
            $insights[] = [
                'id' => 'search_zero_'.Str::slug($z->search_query),
                'fingerprint' => 'search:zero-result:'.Str::slug($z->search_query),
                'category' => 'search',
                'severity' => 'warning',
                'title' => "Sonuçsuz Arama: \"{$z->search_query}\"",
                'description' => "Kullanıcılar \"{$z->search_query}\" kelimesini son {$days} günde {$z->total_count} kez aradı ancak hiç sonuç bulunamadı.",
                'metric' => "{$z->total_count} arama",
                'change' => '0 sonuç',
                'source' => 'Site Araması',
                'action_label' => 'Aramaları İncele',
                'action_target' => ['tab' => 'searches'],
            ];
        }

        // Top searched keyword
        $topSearch = SearchLog::query()
            ->where('searched_at', '>=', $fromDate)
            ->select('search_query', DB::raw('count(*) as total_count'))
            ->groupBy('search_query')
            ->orderByDesc('total_count')
            ->first();

        if ($topSearch && $topSearch->total_count >= 5) {
            // Check previous period for trend
            $prevCount = SearchLog::query()
                ->whereBetween('searched_at', [$prevDate, $fromDate])
                ->where('search_query', $topSearch->search_query)
                ->count();

            $pct = $this->percentageChange($topSearch->total_count, $prevCount);

            $insights[] = [
                'id' => 'search_top_'.Str::slug($topSearch->search_query),
                'fingerprint' => 'search:top-query:'.Str::slug($topSearch->search_query),
                'category' => 'search',
                'severity' => 'positive',
                'title' => "Lider Arama: \"{$topSearch->search_query}\"",
                'description' => "\"{$topSearch->search_query}\" kelimesi son {$days} günde {$topSearch->total_count} arama ile en çok ilgi gören sorgu oldu.",
                'metric' => "{$topSearch->total_count} arama",
                'change' => $pct['label'],
                'source' => 'Site Araması',
                'action_label' => 'Arama Detayları',
                'action_target' => ['tab' => 'searches'],
            ];
        }

        // Simple Near-duplicate / Typo grouping (e.g. 'hikmet arayislari' vs 'hikmet arayışları')
        $allQueries = SearchLog::query()
            ->where('searched_at', '>=', $fromDate)
            ->select('normalized_query', 'search_query', DB::raw('count(*) as total_count'))
            ->groupBy('normalized_query', 'search_query')
            ->orderByDesc('total_count')
            ->limit(20)
            ->get();

        $seenGroups = [];
        foreach ($allQueries as $q1) {
            foreach ($allQueries as $q2) {
                if ($q1->search_query !== $q2->search_query) {
                    $s1 = Str::ascii(mb_strtolower($q1->search_query));
                    $s2 = Str::ascii(mb_strtolower($q2->search_query));
                    if ($s1 === $s2 && ! isset($seenGroups[$s1])) {
                        $seenGroups[$s1] = true;
                        $insights[] = [
                            'id' => 'search_typo_'.$s1,
                            'fingerprint' => 'search:typo:'.$s1,
                            'category' => 'search',
                            'severity' => 'info',
                            'title' => "Arama Varyasyonu: \"{$q1->search_query}\"",
                            'description' => "Kullanıcılar \"{$q1->search_query}\" ve \"{$q2->search_query}\" kelimelerini farklı yazımlarla arıyor. Arama synonym / eşleştirmesi güçlendirilebilir.",
                            'metric' => ($q1->total_count + $q2->total_count).' toplam arama',
                            'change' => 'Eşleştirme',
                            'source' => 'Site Araması',
                            'action_label' => 'Aramalara Git',
                            'action_target' => ['tab' => 'searches'],
                        ];
                    }
                }
            }
        }

        return $insights;
    }

    /**
     * Generates insights based on not_found_logs.
     */
    protected function generateTechnicalInsights(int $days): array
    {
        $insights = [];

        $top404 = NotFoundLog::query()
            ->orderByDesc('hit_count')
            ->first();

        if ($top404 && $top404->hit_count >= 5) {
            $insights[] = [
                'id' => 'tech_404_'.Str::slug($top404->path),
                'fingerprint' => 'technical:404:'.Str::slug($top404->path),
                'category' => 'technical',
                'severity' => 'warning',
                'title' => "Yüksek 404 Hatası: {$top404->path}",
                'description' => "\"{$top404->path}\" bağlantısı ziyaretçiler tarafından {$top404->hit_count} kez 404 (bulunamadı) hatasıyla karşılaştı.",
                'metric' => "{$top404->hit_count} hit",
                'change' => '404 Hatası',
                'source' => '404',
                'action_label' => '404 Loglarını İncele',
                'action_target' => ['tab' => 'technical'],
            ];
        }

        return $insights;
    }

    /**
     * Generates insights based on site_events.
     */
    protected function generateContentInsights(int $days): array
    {
        $insights = [];
        $fromDate = now()->subDays($days);
        $prevDate = now()->subDays($days * 2);

        $topEvent = SiteEvent::query()
            ->where('occurred_at', '>=', $fromDate)
            ->select('event_name', DB::raw('count(*) as total_count'))
            ->groupBy('event_name')
            ->orderByDesc('total_count')
            ->first();

        if ($topEvent) {
            $prevCount = SiteEvent::query()
                ->whereBetween('occurred_at', [$prevDate, $fromDate])
                ->where('event_name', $topEvent->event_name)
                ->count();

            $pct = $this->percentageChange($topEvent->total_count, $prevCount);

            $labelMap = [
                'hero_program_click' => 'Hero Manşet Tıklaması',
                'live_tv_click' => 'Canlı Yayın İzleme',
                'program_card_click' => 'Program Kartı Tıklaması',
                'video_card_click' => 'Video İzleme Tıklaması',
            ];
            $eventLabel = $labelMap[$topEvent->event_name] ?? Str::headline($topEvent->event_name);

            $insights[] = [
                'id' => 'content_event_'.$topEvent->event_name,
                'fingerprint' => 'content:event:'.$topEvent->event_name,
                'category' => 'content',
                'severity' => 'positive',
                'title' => "En Çok Etkileşim: {$eventLabel}",
                'description' => "Kullanıcılar son {$days} günde {$eventLabel} işlemine {$topEvent->total_count} kez tıkladı.",
                'metric' => "{$topEvent->total_count} tıklama",
                'change' => $pct['label'],
                'source' => 'Site Tıklamaları',
                'action_label' => 'İçerik Raporu',
                'action_target' => ['tab' => 'content'],
            ];
        }

        return $insights;
    }

    /**
     * Generates insights based on GA4 integration snapshot.
     */
    protected function generateGa4Insights(int $days): array
    {
        $insights = [];
        $ga4 = AnalyticsIntegration::query()->where('provider', 'ga4')->first();

        if (! $ga4 || ! $ga4->is_enabled || empty($ga4->metrics_snapshot)) {
            return $insights;
        }

        $snapshot = $ga4->metrics_snapshot[(string) $days] ?? $ga4->metrics_snapshot;

        // 1. Top Program
        $topPrograms = $snapshot['top_programs'] ?? [];
        if (! empty($topPrograms[0])) {
            $prog = $topPrograms[0];
            $progName = $prog['name'] ?? $prog['program_name'] ?? 'Bilinmeyen Program';
            $views = number_format($prog['views'] ?? 0);

            $insights[] = [
                'id' => 'ga4_top_program_'.Str::slug($progName),
                'fingerprint' => 'ga4:top-program:'.Str::slug($progName),
                'category' => 'content',
                'severity' => 'positive',
                'title' => "En Çok İzlenen Program: \"{$progName}\"",
                'description' => "\"{$progName}\", GA4 verilerine göre son {$days} günde {$views} sayfa görüntülemesi ile 1. sırada yer aldı.",
                'metric' => "{$views} izlenme",
                'change' => 'GA4 Lideri',
                'source' => 'GA4',
                'action_label' => 'Genel Bakışa Git',
                'action_target' => ['tab' => 'overview'],
            ];
        }

        // 2. Traffic Overview
        $totalUsers = $snapshot['total_users'] ?? null;
        $pageviews = $snapshot['pageviews'] ?? null;

        if ($totalUsers !== null) {
            $insights[] = [
                'id' => 'ga4_traffic_users',
                'fingerprint' => 'ga4:traffic-users',
                'category' => 'traffic',
                'severity' => 'positive',
                'title' => "GA4 Ziyaretçi Hacmi: {$totalUsers} Kullanıcı",
                'description' => "Son {$days} günde DOST TV web sitesini toplam ".number_format($totalUsers).' tekil ziyaretçi inceledi.',
                'metric' => number_format($totalUsers).' kullanıcı',
                'change' => 'GA4 Verisi',
                'source' => 'GA4',
                'action_label' => 'Trafiği İncele',
                'action_target' => ['tab' => 'overview'],
            ];
        }

        return $insights;
    }

    /**
     * Generates insights based on Google Ads integration snapshot.
     */
    protected function generateGoogleAdsInsights(int $days): array
    {
        $insights = [];
        $gads = AnalyticsIntegration::query()->where('provider', 'google_ads')->first();

        if (! $gads || ! $gads->is_enabled || empty($gads->metrics_snapshot)) {
            return $insights;
        }

        $snapshot = $gads->metrics_snapshot[(string) $days] ?? $gads->metrics_snapshot;

        $cost = (float) ($snapshot['cost'] ?? 0);
        $conversions = (int) ($snapshot['conversions'] ?? 0);
        $ctr = (float) ($snapshot['ctr'] ?? 0);
        $currency = $snapshot['currency'] ?? 'TRY';

        // Warning: High spend but zero conversions
        if ($cost >= 100.0 && $conversions === 0) {
            $insights[] = [
                'id' => 'gads_high_spend_zero_conv',
                'fingerprint' => 'google_ads:high-spend-zero-conv',
                'category' => 'ads',
                'severity' => 'warning',
                'title' => 'Google Ads Harcama & Dönüşüm Uyarısı',
                'description' => "Google Ads kampanyalarında son {$days} günde ".number_format($cost, 2)." {$currency} harcanmasına rağmen dönüşüm kaydedilmedi.",
                'metric' => number_format($cost, 2)." {$currency}",
                'change' => '0 dönüşüm',
                'source' => 'Google Ads',
                'action_label' => 'Google Ads Raporu',
                'action_target' => ['tab' => 'ads', 'source' => 'google_ads'],
            ];
        }

        // Top campaign by CTR
        $campaigns = $snapshot['campaigns'] ?? [];
        if (! empty($campaigns[0])) {
            $camp = $campaigns[0];
            $insights[] = [
                'id' => 'gads_top_camp_'.Str::slug($camp['name'] ?? 'camp'),
                'fingerprint' => 'google_ads:top-campaign:'.Str::slug($camp['name'] ?? 'camp'),
                'category' => 'ads',
                'severity' => 'positive',
                'title' => "Google Ads Lider Kampanya: \"{$camp['name']}\"",
                'description' => "\"{$camp['name']}\" kampanyası %{$camp['ctr']} CTR ve ".number_format($camp['clicks']).' tıklama ile en performanslı kampanya oldu.',
                'metric' => "%{$camp['ctr']} CTR",
                'change' => number_format($camp['clicks']).' tık',
                'source' => 'Google Ads',
                'action_label' => 'Google Ads Raporu',
                'action_target' => ['tab' => 'ads', 'source' => 'google_ads'],
            ];
        }

        return $insights;
    }

    /**
     * Generates insights based on Meta Ads integration snapshot.
     */
    protected function generateMetaAdsInsights(int $days): array
    {
        $insights = [];
        $meta = AnalyticsIntegration::query()->where('provider', 'meta_ads')->first();

        if (! $meta || ! $meta->is_enabled || empty($meta->metrics_snapshot)) {
            return $insights;
        }

        $snapshot = $meta->metrics_snapshot[(string) $days] ?? $meta->metrics_snapshot;

        $frequency = (float) ($snapshot['frequency'] ?? 0);
        $freqLimit = (float) config('analytics.insights.meta_frequency_warning', 3.0);

        // Warning: High frequency (Ad fatigue)
        if ($frequency >= $freqLimit) {
            $insights[] = [
                'id' => 'meta_high_frequency',
                'fingerprint' => 'meta_ads:high-frequency',
                'category' => 'ads',
                'severity' => 'warning',
                'title' => 'Meta Ads Yüksek Frekans Uyarısı',
                'description' => "Ortalama gösterim frekansı {$frequency} seviyesine ulaştı. Aynı kullanıcılar reklamları tekrar tekrar görüyor olabilir (Reklam Yorgunluğu).",
                'metric' => "{$frequency} frekans",
                'change' => 'Frekans Artışı',
                'source' => 'Meta Ads',
                'action_label' => 'Meta Ads Raporu',
                'action_target' => ['tab' => 'ads', 'source' => 'meta_ads'],
            ];
        }

        // Top campaign by reach
        $campaigns = $snapshot['campaigns'] ?? [];
        if (! empty($campaigns[0])) {
            $camp = $campaigns[0];
            $insights[] = [
                'id' => 'meta_top_camp_'.Str::slug($camp['name'] ?? 'camp'),
                'fingerprint' => 'meta_ads:top-campaign:'.Str::slug($camp['name'] ?? 'camp'),
                'category' => 'ads',
                'severity' => 'positive',
                'title' => "Öne Çıkan Meta Kampanyası: \"{$camp['name']}\"",
                'description' => "\"{$camp['name']}\" kampanyası son {$days} günde ".number_format($camp['reach'] ?? 0).' tekil kişiye ulaştı ve '.number_format($camp['clicks']).' tıklama elde etti.',
                'metric' => number_format($camp['reach'] ?? 0).' erişim',
                'change' => number_format($camp['clicks']).' tık',
                'source' => 'Meta Ads',
                'action_label' => 'Meta Ads Raporu',
                'action_target' => ['tab' => 'ads', 'source' => 'meta_ads'],
            ];
        }

        return $insights;
    }

    /**
     * Generates sync health warnings for active integrations.
     */
    protected function generateSyncHealthInsights(): array
    {
        $insights = [];
        $staleHours = (int) config('analytics.insights.sync_stale_hours', 3);

        $providers = [
            'ga4' => 'Google Analytics 4',
            'google_ads' => 'Google Ads',
            'meta_ads' => 'Meta Ads',
        ];

        foreach ($providers as $providerKey => $label) {
            $integration = AnalyticsIntegration::query()->where('provider', $providerKey)->first();
            if ($integration && $integration->is_enabled) {
                if ($integration->last_error) {
                    $insights[] = [
                        'id' => 'sync_error_'.$providerKey,
                        'fingerprint' => 'integration:error:'.$providerKey,
                        'category' => 'technical',
                        'severity' => 'warning',
                        'title' => "{$label} Senkronizasyon Hatası",
                        'description' => "{$label} entegrasyonunda hata alındı: ".Str::limit($integration->last_error, 100),
                        'metric' => 'Hata Kaydı',
                        'change' => 'Başarısız',
                        'source' => 'Sistem',
                        'action_label' => 'Ayarları Düzenle',
                        'action_target' => ['tab' => 'integrations'],
                    ];
                } elseif ($integration->last_synced_at && $integration->last_synced_at->lt(now()->subHours($staleHours))) {
                    $insights[] = [
                        'id' => 'sync_stale_'.$providerKey,
                        'fingerprint' => 'integration:stale:'.$providerKey,
                        'category' => 'technical',
                        'severity' => 'info',
                        'title' => "{$label} Verisi Güncellenmedi",
                        'description' => "{$label} verileri {$staleHours} saatten uzun süredir güncellenmedi. Son sync: ".$integration->last_synced_at->diffForHumans(),
                        'metric' => $integration->last_synced_at->diffForHumans(),
                        'change' => 'Gecikme',
                        'source' => 'Sistem',
                        'action_label' => 'Şimdi Senkronize Et',
                        'action_target' => ['tab' => 'integrations'],
                    ];
                }
            }
        }

        return $insights;
    }

    /**
     * Generates fixture demo insights strictly for dev/test environments.
     */
    protected function generateFixtureInsights(int $days): array
    {
        return [
            [
                'id' => 'fix_zero_search',
                'fingerprint' => 'search:zero-result:tefsir-dersleri',
                'category' => 'search',
                'severity' => 'warning',
                'title' => 'Sonuçsuz Arama: "Tefsir Dersleri"',
                'description' => 'Kullanıcılar "Tefsir Dersleri" kelimesini son 30 günde 28 kez aradı ancak sonuç bulunamadı.',
                'metric' => '28 arama',
                'change' => '0 sonuç',
                'source' => 'Site Araması',
                'action_label' => 'Aramaları İncele',
                'action_target' => ['tab' => 'searches'],
            ],
            [
                'id' => 'fix_rising_program',
                'fingerprint' => 'content:event:ruyalarin-dili',
                'category' => 'content',
                'severity' => 'positive',
                'title' => 'Yükselen Program: "Rüyaların Dili"',
                'description' => '"Rüyaların Dili" program kartı tıklamaları önceki döneme göre %34 artış gösterdi.',
                'metric' => '1.420 tıklama',
                'change' => '+34%',
                'source' => 'Site Tıklamaları',
                'action_label' => 'İçeriği Gör',
                'action_target' => ['tab' => 'content'],
            ],
            [
                'id' => 'fix_404_url',
                'fingerprint' => 'technical:404:programlar-eski-canli-yayin',
                'category' => 'technical',
                'severity' => 'warning',
                'title' => 'Yüksek 404 Hatası: /programlar/eski-canli-yayin',
                'description' => '/programlar/eski-canli-yayin adresi 42 kez 404 hatası aldı. Sayfa yönlendirmesi eklenebilir.',
                'metric' => '42 hit',
                'change' => '404 Hatası',
                'source' => '404',
                'action_label' => '404 Loglarına Git',
                'action_target' => ['tab' => 'technical'],
            ],
        ];
    }
}
