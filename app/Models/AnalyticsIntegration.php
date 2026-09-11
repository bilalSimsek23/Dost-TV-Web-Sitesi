<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalyticsIntegration extends Model
{
    protected $fillable = [
        'provider',
        'is_enabled',
        'property_id',
        'credentials',
        'last_synced_at',
        'last_error',
        'metrics_snapshot',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'credentials' => 'encrypted',
        'last_synced_at' => 'datetime',
        'metrics_snapshot' => 'array',
    ];
}
