<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThemeVersion extends Model
{
    public const STATUSES = [
        'draft' => 'Taslak',
        'published' => 'Yayında',
        'archived' => 'Arşivlendi',
    ];

    protected $fillable = [
        'name',
        'settings',
        'status',
        'published_at',
        'published_by',
        'created_by',
    ];

    protected $casts = [
        'settings' => 'array',
        'published_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }
}
