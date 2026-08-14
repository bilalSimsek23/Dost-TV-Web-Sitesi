<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProgramSeason extends Model
{
    use HasFactory;

    protected $table = 'program_seasons';

    protected $fillable = [
        'program_id',
        'season_number',
        'season_year',
        'youtube_playlist_url',
        'youtube_playlist_title',
        'last_youtube_sync_at',
    ];

    protected $casts = [
        'season_number' => 'integer',
        'season_year' => 'string',
        'last_youtube_sync_at' => 'datetime',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function programSeries(): HasMany
    {
        return $this->hasMany(ProgramSeries::class, 'program_season_id');
    }

    /**
     * Compute the public display label based on priority rule:
     * A) season_year if filled
     * B) series.name if filled
     * C) "Sezon {$season_number}" fallback
     */
    public function getPublicLabelAttribute(): string
    {
        if (filled($this->season_year)) {
            return (string) $this->season_year;
        }

        $seriesName = $this->programSeries()->first()?->name;
        if (filled($seriesName)) {
            return (string) $seriesName;
        }

        if (filled($this->season_number)) {
            return 'Sezon ' . $this->season_number;
        }

        return 'Genel';
    }

    /**
     * Find a program season record matching program_id, season_number and season_year.
     */
    public static function findSeason(int $programId, ?int $seasonNumber = null, ?string $seasonYear = null): ?self
    {
        $query = static::where('program_id', $programId);

        if ($seasonNumber !== null) {
            $query->where('season_number', (int) $seasonNumber);
        } else {
            $query->whereNull('season_number');
        }

        if (filled($seasonYear) && $seasonYear !== 'none') {
            $query->where('season_year', (string) $seasonYear);
        } else {
            $query->whereNull('season_year');
        }

        return $query->first();
    }

    /**
     * Resolve the playlist URL for a program and specific season, falling back to program-level if single season.
     */
    public static function resolvePlaylistUrl(?Program $program, ?int $seasonNumber = null, ?string $seasonYear = null): ?string
    {
        if (! $program) {
            return null;
        }

        $season = static::findSeason($program->id, $seasonNumber, $seasonYear);
        if ($season && filled($season->youtube_playlist_url)) {
            return $season->youtube_playlist_url;
        }

        // Fallback: If program has a program-level URL and only 1 season in DB
        if (filled($program->youtube_playlist_url)) {
            $distinctSeasonsCount = Episode::where('program_id', $program->id)
                ->whereNotNull('season_number')
                ->distinct()
                ->count('season_number');

            if ($distinctSeasonsCount <= 1) {
                return $program->youtube_playlist_url;
            }
        }

        return null;
    }
}
