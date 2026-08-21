<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserInvitation extends Model
{
    use HasFactory;

    protected $table = 'user_invitations';

    protected $fillable = [
        'user_id',
        'email',
        'token_hash',
        'expires_at',
        'accepted_at',
        'cancelled_at',
        'created_by',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isAccepted(): bool
    {
        return ! is_null($this->accepted_at);
    }

    public function isCancelled(): bool
    {
        return ! is_null($this->cancelled_at);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isValid(): bool
    {
        return ! $this->isAccepted() && ! $this->isCancelled() && ! $this->isExpired();
    }

    public function getStatusAttribute(): string
    {
        if ($this->isAccepted()) {
            return 'accepted';
        }

        if ($this->isCancelled()) {
            return 'cancelled';
        }

        if ($this->isExpired()) {
            return 'expired';
        }

        return 'pending';
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'accepted' => 'Kabul Edildi',
            'cancelled' => 'İptal Edildi',
            'expired' => 'Süresi Doldu',
            'pending' => 'Bekliyor',
            default => '—',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'accepted' => 'success',
            'cancelled' => 'gray',
            'expired' => 'danger',
            'pending' => 'amber',
            default => 'gray',
        };
    }
}
