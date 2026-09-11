<?php

namespace App\Services\Analytics;

use App\Models\AnalyticsAlert;
use App\Models\AnalyticsIntegration;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AnalyticsAlertService
{
    public function __construct(
        protected AnalyticsInsightService $insightService
    ) {}

    /**
     * Evaluates active insights and updates persistent AnalyticsAlert records.
     */
    public function evaluateAlerts(int $days = 30, bool $useFixture = false): array
    {
        if (! config('analytics.alerts.enabled', true)) {
            return ['processed' => 0, 'created' => 0, 'resolved' => 0];
        }

        // Never allow fixture mode in production
        if (config('app.env') === 'production') {
            $useFixture = false;
        }

        $insightsData = $this->insightService->generateInsights($days, $useFixture);
        $attentionNeeded = $insightsData['attention_needed'] ?? [];

        $activeFingerprints = [];
        $createdCount = 0;
        $updatedCount = 0;

        foreach ($attentionNeeded as $insight) {
            $fingerprint = $insight['fingerprint'] ?? $insight['id'] ?? null;
            if (! $fingerprint) {
                continue;
            }

            $activeFingerprints[] = $fingerprint;

            $alert = AnalyticsAlert::query()->where('fingerprint', $fingerprint)->first();

            if (! $alert) {
                // Create new open persistent alert
                $alert = AnalyticsAlert::create([
                    'fingerprint' => $fingerprint,
                    'type' => $insight['type'] ?? 'insight_alert',
                    'category' => $insight['category'] ?? 'technical',
                    'severity' => $insight['severity'] ?? 'warning',
                    'title' => $insight['title'] ?? 'Analiz Uyarısı',
                    'description' => $insight['description'] ?? '',
                    'source' => $insight['source'] ?? 'Analiz Merkezi',
                    'metric' => $insight['metric'] ?? null,
                    'change' => $insight['change'] ?? null,
                    'action_label' => $insight['action_label'] ?? null,
                    'action_target' => $insight['action_target'] ?? null,
                    'status' => 'open',
                    'first_detected_at' => now(),
                    'last_detected_at' => now(),
                    'notified_at' => now(),
                    'metadata' => [
                        'days' => $days,
                        'source' => $insight['source'] ?? null,
                    ],
                ]);

                $createdCount++;
                $this->notifyAdmins($alert, 'new');
            } else {
                $oldSeverity = $alert->severity;
                $oldStatus = $alert->status;

                $alert->title = $insight['title'] ?? $alert->title;
                $alert->description = $insight['description'] ?? $alert->description;
                $alert->metric = $insight['metric'] ?? $alert->metric;
                $alert->change = $insight['change'] ?? $alert->change;
                $alert->action_label = $insight['action_label'] ?? $alert->action_label;
                $alert->action_target = $insight['action_target'] ?? $alert->action_target;
                $alert->last_detected_at = now();

                // Handle severity escalation (warning -> critical)
                if ($oldSeverity === 'warning' && $insight['severity'] === 'critical') {
                    $alert->severity = 'critical';
                    $alert->notified_at = now();
                    $this->notifyAdmins($alert, 'escalated');
                }

                // Handle status lifecycle
                if ($oldStatus === 'resolved') {
                    // Re-open alert because problem returned
                    $alert->status = 'open';
                    $alert->resolved_at = null;
                    $alert->notified_at = now();
                    $this->notifyAdmins($alert, 'reopened');
                } elseif ($oldStatus === 'dismissed') {
                    // Muted by admin - keep dismissed, do not re-notify
                    $alert->status = 'dismissed';
                } else {
                    $alert->status = 'open';
                }

                $alert->save();
                $updatedCount++;
            }
        }

        // Auto-resolve open alerts that are no longer detected (only during real evaluations when sources are eligible)
        $autoResolvedCount = 0;
        if (! $useFixture) {
            $ga4 = AnalyticsIntegration::query()->where('provider', 'ga4')->first();
            $gads = AnalyticsIntegration::query()->where('provider', 'google_ads')->first();
            $meta = AnalyticsIntegration::query()->where('provider', 'meta_ads')->first();

            $ga4Eligible = $ga4 && $ga4->is_enabled && empty($ga4->last_error) && ! empty($ga4->metrics_snapshot);
            $gadsEligible = $gads && $gads->is_enabled && empty($gads->last_error) && ! empty($gads->metrics_snapshot);
            $metaEligible = $meta && $meta->is_enabled && empty($meta->last_error) && ! empty($meta->metrics_snapshot);

            $openAlertsToResolve = AnalyticsAlert::query()
                ->where('status', 'open')
                ->whereNotIn('fingerprint', $activeFingerprints)
                ->get();

            foreach ($openAlertsToResolve as $alertToResolve) {
                $fp = $alertToResolve->fingerprint;

                // Protect open alerts from false resolution if provider integration was not cleanly evaluated
                if (Str::startsWith($fp, 'google_ads:') && ! $gadsEligible) {
                    continue;
                }
                if (Str::startsWith($fp, 'meta_ads:') && ! $metaEligible) {
                    continue;
                }
                if (Str::startsWith($fp, 'ga4:') && ! $ga4Eligible) {
                    continue;
                }

                $alertToResolve->update([
                    'status' => 'resolved',
                    'resolved_at' => now(),
                ]);
                $autoResolvedCount++;
            }
        }

        return [
            'processed' => count($attentionNeeded),
            'created' => $createdCount,
            'updated' => $updatedCount,
            'auto_resolved' => $autoResolvedCount,
        ];
    }

    /**
     * Send Filament notification to active panel admins.
     */
    protected function notifyAdmins(AnalyticsAlert $alert, string $event): void
    {
        try {
            $users = User::query()->where('is_active', true)->get();
            if ($users->isEmpty()) {
                return;
            }

            $prefix = match ($event) {
                'new' => '[Yeni Aksiyon]',
                'escalated' => '[Kritik Seviye Artışı]',
                'reopened' => '[Tekrar Açıldı]',
                default => '[Analiz Uyarısı]',
            };

            $title = "{$prefix} {$alert->title}";
            $body = Str::limit($alert->description, 150);

            $notification = Notification::make()
                ->title($title)
                ->body($body);

            if ($alert->severity === 'critical') {
                $notification->danger();
            } else {
                $notification->warning();
            }

            // In request context, send notification
            $notification->send();

            // If database notification table exists, notify models
            try {
                $notification->sendToDatabase($users);
            } catch (\Throwable $e) {
                // Ignore database notification table missing in unit tests
            }
        } catch (\Throwable $e) {
            // Silently suppress notification failures in background environments
        }
    }
}
