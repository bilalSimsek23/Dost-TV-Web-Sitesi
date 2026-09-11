<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotFoundLog extends Model
{
    protected $fillable = [
        'path',
        'referer',
        'hit_count',
        'last_occurred_at',
    ];

    protected $casts = [
        'hit_count' => 'integer',
        'last_occurred_at' => 'datetime',
    ];
}
