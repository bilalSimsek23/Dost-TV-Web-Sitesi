<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use App\Support\Youtube;

class Program extends Model
{
    /** @use HasFactory<\Database\Factories\ProgramFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'status',
        'short_description',
        'hero_text',
        'description',
        'cover_image',
        'horizontal_image',
        'mobile_hero_image',
        'program_logo',
        'default_episode_image',
        'trailer_url',
        'youtube_channel_url',
        'youtube_playlist_url',
        'last_youtube_sync_at',
        'is_active',
        'is_featured',
        'hero_days',
        'hero_focus_settings',
        'show_on_public',
        'sort_order',
        'meta_title',
        'meta_description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'hero_days' => 'array',
        'hero_focus_settings' => 'array',
        'show_on_public' => 'boolean',
        'last_youtube_sync_at' => 'datetime',
    ];

    public function getHeroFocusCoords(string $device = 'desktop'): array
    {
        $settings = (array) ($this->hero_focus_settings ?? []);

        $defaultX = 50;
        $defaultY = 30;

        $x = $settings["{$device}_x"] ?? $settings['desktop_x'] ?? $defaultX;
        $y = $settings["{$device}_y"] ?? $settings['desktop_y'] ?? $defaultY;

        return [
            'x' => max(0, min(100, (int) $x)),
            'y' => max(0, min(100, (int) $y)),
        ];
    }

    public function getHeroFocusStyle(string $device = 'desktop'): string
    {
        $coords = $this->getHeroFocusCoords($device);
        return "{$coords['x']}% {$coords['y']}%";
    }

    public const STATUSES = [
        'active' => 'Aktif',
        'season_break' => 'Sezon Arasında',
        'completed' => 'Sona Erdi',
        'archived' => 'Arşivlenmiş',
    ];

    protected static function booted(): void
    {
        static::saving(function (Program $program) {
            if (blank($program->slug)) {
                $program->slug = Str::slug($program->name);
            }

            if (blank($program->status)) {
                $program->status = 'active';
            }

            if (is_null($program->show_on_public)) {
                $program->show_on_public = true;
            }

            // Sync is_active with status & show_on_public (Single source of truth derivation)
            // A program is active/visible if show_on_public is true and its status is active or season_break.
            $program->is_active = (bool) $program->show_on_public && in_array($program->status, ['active', 'season_break'], true);
        });

        static::saved(function (Program $program) {
            \App\Services\Menu\ProgramMegaMenuService::forgetCache();
            \App\Support\SiteCache::forgetHomeFeaturedPrograms();
            \App\Support\SiteCache::forgetHomeHeroPrograms();

            if (filled($program->youtube_channel_url)) {
                app(\App\Services\YouTube\ProgramYoutubeChannelSyncService::class)->syncFromProgram($program);
            }
        });
        static::deleted(function () {
            \App\Services\Menu\ProgramMegaMenuService::forgetCache();
            \App\Support\SiteCache::forgetHomeFeaturedPrograms();
            \App\Support\SiteCache::forgetHomeHeroPrograms();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function resolveRouteBinding($value, $field = null)
    {
        if ($field) {
            return $this->where($field, $value)->firstOrFail();
        }

        return $this->where('id', $value)->orWhere('slug', $value)->firstOrFail();
    }

    public function schedules(): HasMany

    {
        return $this->hasMany(Schedule::class);
    }

    public function episodes(): HasMany
    {
        return $this->hasMany(Episode::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function programCollections(): BelongsToMany
    {
        return $this->belongsToMany(ProgramCollection::class, 'program_collection_program')
            ->withPivot('id', 'sort_order', 'is_pinned')
            ->withTimestamps();
    }

    public function youtubeSyncLogs(): HasMany
    {
        return $this->hasMany(YoutubeSyncLog::class);
    }

    public function programSeasons(): HasMany
    {
        return $this->hasMany(ProgramSeason::class);
    }

    public function programSeries(): HasMany
    {
        return $this->hasMany(ProgramSeries::class);
    }

    public function getSeasonPlaylistUrl(?int $seasonNumber = null, ?string $seasonYear = null): ?string
    {
        return ProgramSeason::resolvePlaylistUrl($this, $seasonNumber, $seasonYear);
    }

    public function getTrailerEmbedUrlAttribute(): ?string
    {
        return Youtube::embedUrl($this->trailer_url);
    }
}

