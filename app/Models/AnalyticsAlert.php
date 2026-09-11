<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnalyticsAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'fingerprint',
        'type',
        'category',
        'severity',
        'title',
        'description',
        'source',
        'metric',
        'change',
        'action_label',
        'action_target',
        'status',
        'first_detected_at',
        'last_detected_at',
        'resolved_at',
        'dismissed_at',
        'notified_at',
        'metadata',
    ];

    protected $casts = [
        'action_target' => 'array',
        'metadata' => 'array',
        'first_detected_at' => 'datetime',
        'last_detected_at' => 'datetime',
        'resolved_at' => 'datetime',
        'dismissed_at' => 'datetime',
        'notified_at' => 'datetime',
    ];

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }

    public function scopeWarning($query)
    {
        return $query->where('severity', 'warning');
    }

    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }

    public function scopeDismissed($query)
    {
        return $query->where('status', 'dismissed');
    }
}
