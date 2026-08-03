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
        'live_tv_title',
        'live_tv_description',
        'live_tv_backup_url',
        'live_tv_poster',
        'live_tv_maintenance_message',
        'live_tv_error_message',
        'live_tv_is_active',
        'live_tv_is_public',
        'radio_stream_url',
        'radio_name',
        'radio_description',
        'radio_backup_url',
        'radio_image',
        'radio_maintenance_message',
        'radio_error_message',
        'radio_is_active',
        'radio_is_public',
    ];

    protected $casts = [
        'live_tv_is_active' => 'boolean',
        'live_tv_is_public' => 'boolean',
        'radio_is_active' => 'boolean',
        'radio_is_public' => 'boolean',
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
