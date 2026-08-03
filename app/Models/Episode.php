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
        'title',
        'description',
        'thumbnail',
        'video_source',
        'youtube_url',
        'video_path',
        'aired_at',
        'sort_order',
    ];

    protected $casts = [
        'aired_at' => 'date',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function getYoutubeEmbedUrlAttribute(): ?string
    {
        return Youtube::embedUrl($this->youtube_url);
    }
}
