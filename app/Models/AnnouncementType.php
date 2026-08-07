<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class AnnouncementType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (AnnouncementType $type) {
            if (empty($type->slug) && ! empty($type->name)) {
                $type->slug = Str::slug($type->name);
            }
        });
    }

    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class, 'announcement_type_id');
    }
}
