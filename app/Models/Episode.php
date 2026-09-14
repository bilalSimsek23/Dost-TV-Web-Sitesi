<?php

namespace App\Models;

use App\Support\Youtube;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

class Episode extends Model
{
    /** @use HasFactory<\Database\Factories\EpisodeFactory> */
    use HasFactory;

    protected $fillable = [
        'program_id',
        'program_series_id',
        'episode_number',
        'season_number',
        'season_year',
        'title',
        'slug',
        'description',
        'thumbnail',
        'horizontal_image',
        'social_image',
        'video_source',
        'youtube_url',
        'video_path',
        'status',
        'is_active',
        'show_on_public',
        'duration',
        'view_count',
        'like_count',
        'comment_count',
        'aired_at',
        'sort_order',
        'meta_title',
        'meta_description',
    ];

    protected $attributes = [
        'status' => 'published',
        'show_on_public' => true,
        'is_active' => true,
        'sort_order' => 0,
    ];

    protected $casts = [
        'aired_at' => 'date',
        'is_active' => 'boolean',
        'show_on_public' => 'boolean',
        'episode_number' => 'integer',
        'season_number' => 'integer',
        'season_year' => 'string',
        'program_series_id' => 'integer',
        'view_count' => 'integer',
        'like_count' => 'integer',
        'comment_count' => 'integer',
    ];


    public const STATUSES = [
        'draft' => 'Taslak',
        'ready' => 'Yayına Hazır',
        'published' => 'Yayında',
        'archived' => 'Arşivlendi',
    ];

    protected static function booted(): void
    {
        static::saving(function (Episode $episode) {
            if (blank($episode->status)) {
                $episode->status = 'published';
            }
            if (is_null($episode->show_on_public)) {
                $episode->show_on_public = true;
            }
            if (is_null($episode->is_active)) {
                $episode->is_active = true;
            }
            if (is_null($episode->sort_order)) {
                $episode->sort_order = 0;
            }

            if (blank($episode->slug)) {
                $progSlug = $episode->program?->slug ?? 'program';
                $epTitleSlug = \Illuminate\Support\Str::slug($episode->title ?: "bolum-{$episode->id}");
                $baseSlug = \Illuminate\Support\Str::slug("{$progSlug}-{$epTitleSlug}");
                $slug = $baseSlug ?: "bolum-{$episode->id}";

                $counter = 1;
                while (static::where('slug', $slug)->where('id', '!=', $episode->id ?? 0)->exists()) {
                    $counter++;
                    $slug = "{$baseSlug}-{$counter}";
                }

                $episode->slug = $slug;
            }

            if ($episode->status === 'published' && $episode->show_on_public) {
                $episode->is_active = true;
            } elseif ($episode->status === 'draft' || $episode->status === 'archived' || ! $episode->show_on_public) {
                $episode->is_active = false;
            }
        });
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function programSeries(): BelongsTo
    {
        return $this->belongsTo(ProgramSeries::class, 'program_series_id');
    }

    public function videoCollections(): BelongsToMany
    {
        return $this->belongsToMany(VideoCollection::class, 'episode_video_collection')
            ->withPivot('id', 'sort_order')
            ->withTimestamps();
    }

    public function getYoutubeEmbedUrlAttribute(): ?string
    {
        return Youtube::embedUrl($this->youtube_url);
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        if (filled($this->thumbnail)) {
            if (str_starts_with($this->thumbnail, 'http://') || str_starts_with($this->thumbnail, 'https://')) {
                return $this->thumbnail;
            }

            return Storage::disk('public')->url($this->thumbnail);
        }

        if ($this->video_source === 'youtube' && filled($this->youtube_url)) {
            return Youtube::thumbnailUrl($this->youtube_url);
        }

        return null;
    }

    public function getPublicUrlAttribute(): string
    {
        $programSlug = $this->program?->slug;

        if (! $programSlug && $this->program_id) {
            $programSlug = Program::where('id', $this->program_id)->value('slug');
        }

        $programSlug = $programSlug ?? 'program';

        $params = [
            'program' => $programSlug,
            'episode' => $this->id,
        ];

        if (filled($this->season_year)) {
            $params['year'] = $this->season_year;
        }

        if (filled($this->season_number)) {
            $params['season'] = $this->season_number;
        }

        if (filled($this->program_series_id)) {
            $params['series'] = $this->program_series_id;
        }

        return route('programs.show', $params);
    }
}
