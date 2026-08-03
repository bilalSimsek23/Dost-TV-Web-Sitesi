<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleVersionHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'schedule_template_id',
        'version_number',
        'snapshot_data',
        'published_by_user_id',
        'change_summary',
    ];

    protected $casts = [
        'version_number' => 'integer',
        'snapshot_data' => 'array',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(ScheduleTemplate::class, 'schedule_template_id');
    }

    public function publishedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by_user_id');
    }
}
