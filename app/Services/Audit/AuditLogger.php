<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditLogger
{
    /**
     * Log a human-readable user activity in the audit logs.
     */
    public static function log(
        string $action,
        string $message,
        ?Model $subject = null,
        ?string $subjectLabel = null,
        ?User $user = null,
        bool $isDestructive = false,
        ?array $metadata = null
    ): AuditLog {
        $currentUser = $user ?? auth()->user();
        $userName = $currentUser?->name ?? 'Sistem';

        return AuditLog::create([
            'user_id' => $currentUser?->id,
            'user_name_snapshot' => $userName,
            'action' => $action,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject?->getKey(),
            'subject_label' => $subjectLabel,
            'message' => $message,
            'is_destructive' => $isDestructive || in_array($action, ['deleted', 'force_deleted'], true),
            'metadata' => $metadata,
        ]);
    }
}
