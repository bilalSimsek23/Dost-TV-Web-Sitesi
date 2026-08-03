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
        'description',
        'cover_image',
        'trailer_url',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (Program $program) {
            if (blank($program->slug)) {
                $program->slug = Str::slug($program->name);
            }
        });

        static::saved(fn () => \App\Services\Menu\ProgramMegaMenuService::forgetCache());
        static::deleted(fn () => \App\Services\Menu\ProgramMegaMenuService::forgetCache());
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
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

    public function getTrailerEmbedUrlAttribute(): ?string
    {
        return Youtube::embedUrl($this->trailer_url);
    }
}
