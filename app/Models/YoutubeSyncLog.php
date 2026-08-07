<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class YoutubeSyncLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'program_id',
        'playlist_url',
        'status',
        'checked_videos',
        'new_videos',
        'created_episodes',
        'skipped_videos',
        'error_message',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'checked_videos' => 'integer',
        'new_videos' => 'integer',
        'created_episodes' => 'integer',
        'skipped_videos' => 'integer',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public const STATUS_LABELS = [
        'success' => 'Başarılı',
        'partial' => 'Kısmi',
        'failed' => 'Hata',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return static::STATUS_LABELS[$this->status] ?? $this->status;
    }
}
