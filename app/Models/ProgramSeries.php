<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ProgramSeries extends Model
{
    use HasFactory;

    protected $table = 'program_series';

    protected $fillable = [
        'program_id',
        'program_season_id',
        'name',
        'slug',
        'youtube_playlist_url',
        'youtube_playlist_title',
        'last_youtube_sync_at',
        'sort_order',
    ];

    protected $casts = [
        'program_id' => 'integer',
        'program_season_id' => 'integer',
        'last_youtube_sync_at' => 'datetime',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (ProgramSeries $series) {
            if (blank($series->slug) && filled($series->name)) {
                $series->slug = Str::slug($series->name);
            }
            if ($series->sort_order === null) {
                $series->sort_order = 0;
            }
        });
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function programSeason(): BelongsTo
    {
        return $this->belongsTo(ProgramSeason::class, 'program_season_id');
    }

    public function episodes(): HasMany
    {
        return $this->hasMany(Episode::class, 'program_series_id');
    }

    /**
     * Compute the public display label based on priority rule:
     * A) season_year if filled
     * B) series.name if filled
     * C) "Sezon {$season_number}" fallback
     */
    public function getPublicLabelAttribute(): string
    {
        if (filled($this->name)) {
            return (string) $this->name;
        }

        if (filled($this->programSeason?->season_year)) {
            return (string) $this->programSeason->season_year;
        }

        if (filled($this->programSeason?->season_number)) {
            return 'Sezon ' . $this->programSeason->season_number;
        }

        return 'Genel';
    }

    /**
     * Find a program series matching program_id, program_season_id (optional), and series name.
     */
    public static function findSeries(int $programId, ?int $programSeasonId, string $name): ?self
    {
        $cleanName = trim($name);
        if (blank($cleanName)) {
            return null;
        }

        $query = static::where('program_id', $programId)
            ->where(function ($q) use ($cleanName) {
                $q->where('name', $cleanName)
                    ->orWhereRaw('LOWER(name) = ?', [mb_strtolower($cleanName, 'UTF-8')]);
            });

        if ($programSeasonId !== null) {
            $query->where(function ($q) use ($programSeasonId) {
                $q->where('program_season_id', $programSeasonId)
                    ->orWhereNull('program_season_id');
            });
        }

        return $query->first();
    }

    /**
     * Find or create a program series matching program_id, program_season_id (optional), and series name.
     */
    public static function findOrCreateSeries(
        int $programId,
        ?int $programSeasonId,
        string $name,
        ?string $playlistUrl = null,
        ?string $playlistTitle = null
    ): self {
        $cleanName = trim($name);
        $existing = static::findSeries($programId, $programSeasonId, $cleanName);

        if ($existing) {
            $updates = [];
            if ($programSeasonId !== null && $existing->program_season_id === null) {
                $updates['program_season_id'] = $programSeasonId;
            }
            if (filled($playlistUrl) && blank($existing->youtube_playlist_url)) {
                $updates['youtube_playlist_url'] = trim($playlistUrl);
            }
            if (filled($playlistTitle) && blank($existing->youtube_playlist_title)) {
                $updates['youtube_playlist_title'] = trim($playlistTitle);
            }

            if (! empty($updates)) {
                $existing->update($updates);
            }

            return $existing;
        }

        return static::create([
            'program_id' => $programId,
            'program_season_id' => $programSeasonId,
            'name' => $cleanName,
            'slug' => Str::slug($cleanName),
            'youtube_playlist_url' => filled($playlistUrl) ? trim($playlistUrl) : null,
            'youtube_playlist_title' => filled($playlistTitle) ? trim($playlistTitle) : null,
        ]);
    }
}
