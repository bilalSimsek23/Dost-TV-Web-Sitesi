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
