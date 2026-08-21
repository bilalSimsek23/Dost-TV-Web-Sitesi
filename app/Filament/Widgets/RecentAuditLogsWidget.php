<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\AuditLogs\AuditLogResource;
use App\Models\AuditLog;
use Filament\Widgets\Widget;

class RecentAuditLogsWidget extends Widget
{
    protected string $view = 'filament.widgets.recent-audit-logs-widget';

    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = [
        'default' => 'full',
        'lg' => 1,
    ];

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user && ($user->isSuperAdmin() || $user->isAdministrator());
    }

    protected function getViewData(): array
    {
        $logs = AuditLog::query()
            ->whereNotNull('user_id')
            ->latest()
            ->limit(5)
            ->with('user')
            ->get();

        return [
            'logs' => $logs,
            'hasLogs' => $logs->isNotEmpty(),
            'auditLogsUrl' => AuditLogResource::getUrl(),
        ];
    }
}
