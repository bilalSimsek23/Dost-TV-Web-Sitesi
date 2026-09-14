<?php

namespace App\Models;

use App\Support\SiteCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class YoutubeChannel extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'handle',
        'url',
        'logo',
        'description',
        'channel_id',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getAvatarUrlAttribute(): string
    {
        if (blank($this->logo)) {
            return asset('images/placeholder.jpg');
        }

        if (str_starts_with($this->logo, 'http://') || str_starts_with($this->logo, 'https://')) {
            return $this->logo;
        }

        return Storage::disk('public')->url($this->logo);
    }

    protected static function booted(): void
    {
        static::saved(function () {
            SiteCache::forgetHomepage();
        });

        static::deleted(function () {
            SiteCache::forgetHomepage();
        });
    }
}
