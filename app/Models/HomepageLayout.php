<?php

namespace App\Models;

use App\Support\SiteCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class HomepageLayout extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'page_type',
        'target_id',
        'is_active',
        'draft_sections',
        'published_sections',
        'published_at',
        'created_by',
        'updated_by',
    ];

    protected $attributes = [
        'page_type' => 'home',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'draft_sections' => 'array',
        'published_sections' => 'array',
        'published_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::deleting(function (HomepageLayout $layout) {
            if ($layout->is_active) {
                throw new \Exception('Aktif (canlı) düzen doğrudan silinemez. Lütfen önce başka bir düzeni canlı yapın.');
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePageType($query, string $type)
    {
        return $query->where('page_type', $type);
    }

    /**
     * Activates this layout safely within a transaction,
     * ensuring exactly ONE layout is active for this page_type.
     */
    public function activate(): bool
    {
        return DB::transaction(function () {
            $type = $this->page_type ?? 'home';
            static::query()
                ->where('page_type', $type)
                ->where('id', '!=', $this->id)
                ->update(['is_active' => false]);

            $this->is_active = true;
            $saved = $this->save();

            if ($type === 'home') {
                SiteCache::forgetHomepage();
            }

            return $saved;
        });
    }

    /**
     * Publishes draft configuration to published configuration.
     */
    public function publish(bool $andActivate = false): bool
    {
        return DB::transaction(function () use ($andActivate) {
            $type = $this->page_type ?? 'home';
            $this->published_sections = $this->draft_sections ?? [];
            $this->published_at = now();

            if ($andActivate) {
                static::query()
                    ->where('page_type', $type)
                    ->where('id', '!=', $this->id)
                    ->update(['is_active' => false]);
                $this->is_active = true;
            }

            $saved = $this->save();

            if ($type === 'home') {
                SiteCache::forgetHomepage();
            }

            return $saved;
        });
    }

    /**
     * Creates a deep copy of this layout record with fresh section UUIDs.
     */
    public function duplicate(): static
    {
        return DB::transaction(function () {
            $duplicate = $this->replicate();
            $duplicate->name = "{$this->name} - Kopya";
            $duplicate->is_active = false;
            $duplicate->published_at = null;

            // Deep copy & regenerate section UUIDs for draft_sections
            if (! empty($duplicate->draft_sections) && is_array($duplicate->draft_sections)) {
                $duplicate->draft_sections = array_map(function ($section) {
                    if (is_array($section)) {
                        $section['uuid'] = (string) \Illuminate\Support\Str::uuid();
                    }

                    return $section;
                }, $duplicate->draft_sections);
            }

            // Deep copy & regenerate section UUIDs for published_sections
            if (! empty($duplicate->published_sections) && is_array($duplicate->published_sections)) {
                $duplicate->published_sections = array_map(function ($section) {
                    if (is_array($section)) {
                        $section['uuid'] = (string) \Illuminate\Support\Str::uuid();
                    }

                    return $section;
                }, $duplicate->published_sections);
            }

            $duplicate->save();

            return $duplicate;
        });
    }
}
