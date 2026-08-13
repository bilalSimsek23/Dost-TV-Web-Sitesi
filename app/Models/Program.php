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
        'description',
        'cover_image',
        'horizontal_image',
        'program_logo',
        'default_episode_image',
        'trailer_url',
        'youtube_channel_url',
        'youtube_playlist_url',
        'last_youtube_sync_at',
        'is_active',
        'is_featured',
        'show_on_public',
        'sort_order',
        'meta_title',
        'meta_description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'show_on_public' => 'boolean',
        'last_youtube_sync_at' => 'datetime',
    ];

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

            // Sync is_active with status & show_on_public for backward compatibility
            if ($program->status === 'active' && $program->show_on_public) {
                $program->is_active = true;
            } elseif ($program->status === 'completed' || $program->status === 'archived' || ! $program->show_on_public) {
                $program->is_active = false;
            }
        });

        static::saved(function () {
            \App\Services\Menu\ProgramMegaMenuService::forgetCache();
            \App\Support\SiteCache::forgetHomeFeaturedPrograms();
        });
        static::deleted(function () {
            \App\Services\Menu\ProgramMegaMenuService::forgetCache();
            \App\Support\SiteCache::forgetHomeFeaturedPrograms();
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

    public function youtubeSyncLogs(): HasMany
    {
        return $this->hasMany(YoutubeSyncLog::class);
    }

    public function programSeasons(): HasMany
    {
        return $this->hasMany(ProgramSeason::class);
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

