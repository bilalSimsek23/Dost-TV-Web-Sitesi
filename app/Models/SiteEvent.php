<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteEvent extends Model
{
    protected $fillable = [
        'event_name',
        'entity_type',
        'entity_id',
        'metadata',
        'occurred_at',
    ];

    protected $casts = [
        'entity_id' => 'integer',
        'metadata' => 'array',
        'occurred_at' => 'datetime',
    ];
}
