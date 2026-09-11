<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SearchLog extends Model
{
    protected $fillable = [
        'search_query',
        'normalized_query',
        'result_count',
        'device_type',
        'source_page',
        'searched_at',
    ];

    protected $casts = [
        'result_count' => 'integer',
        'searched_at' => 'datetime',
    ];
}
