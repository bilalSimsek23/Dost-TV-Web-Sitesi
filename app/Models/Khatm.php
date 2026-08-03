<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Khatm extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'start_date',
        'end_date',
        'total_juz',
        'status',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'total_juz' => 'integer',
    ];

    public const STATUSES = [
        'active' => 'Aktif',
        'completed' => 'Tamamlandı',
        'cancelled' => 'İptal Edildi',
    ];

    protected static function booted(): void
    {
        static::saving(function (Khatm $khatm) {
            if (blank($khatm->slug) && filled($khatm->title)) {
                $khatm->slug = Str::slug($khatm->title);
            }
        });

        static::created(function (Khatm $khatm) {
            $khatm->seedJuzClaims();
        });
    }

    public function juzClaims(): HasMany
    {
        return $this->hasMany(JuzClaim::class)->orderBy('juz_number');
    }

    public function seedJuzClaims(): void
    {
        if ($this->juzClaims()->count() === 0) {
            $total = $this->total_juz ?: 30;
            for ($i = 1; $i <= $total; $i++) {
                JuzClaim::create([
                    'khatm_id' => $this->id,
                    'juz_number' => $i,
                    'status' => 'empty',
                ]);
            }
        }
    }

    public function getClaimedCountAttribute(): int
    {
        return $this->juzClaims()->whereIn('status', ['assigned', 'completed'])->count();
    }

    public function getCompletedCountAttribute(): int
    {
        return $this->juzClaims()->where('status', 'completed')->count();
    }
}
