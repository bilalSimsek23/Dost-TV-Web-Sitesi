<?php

namespace App\Models;

use App\Support\Youtube;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Episode extends Model
{
    /** @use HasFactory<\Database\Factories\EpisodeFactory> */
    use HasFactory;

    protected $fillable = [
        'program_id',
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
        'season_year' => 'integer',
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

    public function getYoutubeEmbedUrlAttribute(): ?string
    {
        return Youtube::embedUrl($this->youtube_url);
    }
}
