<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $fillable = [
        'site_name',
        'logo',
        'live_tv_type',
        'live_tv_url',
        'radio_stream_url',
        'radio_name',
    ];

    public static function current(): self
    {
        return static::query()->first() ?? static::query()->forceCreate([
            'id' => 1,
            'site_name' => 'Dost TV',
            'live_tv_type' => 'iframe',
        ]);
    }
}
