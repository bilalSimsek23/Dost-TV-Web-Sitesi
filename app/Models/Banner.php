<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    /** @use HasFactory<\Database\Factories\BannerFactory> */
    use HasFactory;

    public const CONTENT_TYPES = [
        'hero' => 'Hero Görseli',
        'banner' => 'Banner',
        'promotion' => 'Tanıtım Görseli',
    ];

    protected $fillable = [
        'title',
        'subtitle',
        'content_type',
        'description',
        'image',
        'mobile_image',
        'alt_text',
        'link_url',
        'button_text',
        'open_in_new_tab',
        'is_active',
        'starts_at',
        'ends_at',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'open_in_new_tab' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saved(function () {
            \App\Support\SiteCache::forgetHomeBanners();
        });
        static::deleted(function () {
            \App\Support\SiteCache::forgetHomeBanners();
        });
    }
}
