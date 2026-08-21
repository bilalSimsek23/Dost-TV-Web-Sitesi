<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    public const ACTIONS = [
        'created' => 'Oluşturuldu',
        'updated' => 'Düzenlendi',
        'deleted' => 'Silindi',
        'archived' => 'Arşivlendi',
        'restored' => 'Geri Yüklendi',
        'published' => 'Yayınlandı',
        'unpublished' => 'Yayından Kaldırıldı',
        'imported' => 'İçe Aktarıldı',
        'synced' => 'Senkronize Edildi',
        'activated' => 'Aktifleştirildi',
        'deactivated' => 'Pasife Alındı',
        'role_changed' => 'Rol Değiştirildi',
        'invited' => 'Davet Edildi',
        'invitation_resent' => 'Davet Yenilendi',
        'invitation_cancelled' => 'Davet İptal Edildi',
    ];

    protected $fillable = [
        'user_id',
        'user_name_snapshot',
        'action',
        'subject_type',
        'subject_id',
        'subject_label',
        'message',
        'is_destructive',
        'metadata',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'is_destructive' => 'boolean',
            'metadata' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo('subject', 'subject_type', 'subject_id');
    }

    public function getActionLabelAttribute(): string
    {
        return self::ACTIONS[$this->action] ?? $this->action;
    }

    public function getActionColorAttribute(): string
    {
        if ($this->is_destructive) {
            return 'danger';
        }

        return match ($this->action) {
            'created', 'published', 'activated', 'restored' => 'success',
            'updated', 'synced', 'imported', 'role_changed', 'invited' => 'info',
            'deactivated', 'unpublished', 'archived', 'invitation_resent' => 'warning',
            'deleted' => 'danger',
            'invitation_cancelled' => 'gray',
            default => 'gray',
        };
    }
}
