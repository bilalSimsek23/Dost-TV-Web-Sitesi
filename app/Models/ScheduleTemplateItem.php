<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleTemplateItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'schedule_template_id',
        'program_id',
        'day_of_week',
        'start_time',
        'end_time',
        'custom_title',
        'is_live',
        'is_repeat',
        'is_active',
        'note',
        'image',
        'description',
        'link_type',
        'episode_id',
        'external_url',
        'stream_url',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'is_live' => 'boolean',
        'is_repeat' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(ScheduleTemplate::class, 'schedule_template_id');
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function episode(): BelongsTo
    {
        return $this->belongsTo(Episode::class);
    }

    public function getDayNameAttribute(): string
    {
        return Schedule::DAYS[$this->day_of_week] ?? '';
    }

    public function getDisplayTitleAttribute(): string
    {
        return $this->custom_title ?: ($this->program?->name ?? 'Program');
    }

    public function getEffectiveImageAttribute(): string
    {
        if (filled($this->image)) {
            return asset('storage/' . $this->image);
        }

        if ($this->episode && filled($this->episode->cover_image ?? null)) {
            return $this->episode->cover_image;
        }

        if ($this->program && filled($this->program->cover_image ?? null)) {
            return $this->program->cover_image;
        }

        return 'https://dosttv.com/wp-content/uploads/2022/02/dost_logo.png';
    }

    public function getTargetUrlAttribute(): ?string
    {
        return match ($this->link_type) {
            'program' => $this->program ? route('programs.show', $this->program) : null,
            'episode' => $this->episode ? ($this->program ? route('programs.show', $this->program) : null) : null,
            'external' => $this->external_url,
            'live' => $this->stream_url ?: route('live.tv'),
            'none' => null,
            default => $this->program ? route('programs.show', $this->program) : null,
        };
    }
}
