<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstagramReelsSchedule extends Model
{
    protected $fillable = [
        'monday_category_id',
        'tuesday_category_id',
        'wednesday_category_id',
        'thursday_category_id',
        'friday_category_id',
        'saturday_category_id',
        'sunday_category_id',
        'sort_mode',
    ];

    public function mondayCategory(): BelongsTo
    {
        return $this->belongsTo(InstagramCategory::class, 'monday_category_id');
    }

    public function tuesdayCategory(): BelongsTo
    {
        return $this->belongsTo(InstagramCategory::class, 'tuesday_category_id');
    }

    public function wednesdayCategory(): BelongsTo
    {
        return $this->belongsTo(InstagramCategory::class, 'wednesday_category_id');
    }

    public function thursdayCategory(): BelongsTo
    {
        return $this->belongsTo(InstagramCategory::class, 'thursday_category_id');
    }

    public function fridayCategory(): BelongsTo
    {
        return $this->belongsTo(InstagramCategory::class, 'friday_category_id');
    }

    public function saturdayCategory(): BelongsTo
    {
        return $this->belongsTo(InstagramCategory::class, 'saturday_category_id');
    }

    public function sundayCategory(): BelongsTo
    {
        return $this->belongsTo(InstagramCategory::class, 'sunday_category_id');
    }

    /**
     * Singleton accessor for InstagramReelsSchedule.
     */
    public static function current(): self
    {
        return static::query()->first() ?? static::query()->create([
            'id' => 1,
            'sort_mode' => 'latest',
        ]);
    }
}
