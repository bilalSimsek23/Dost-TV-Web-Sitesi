<?php

namespace App\Models;

use App\Services\Home\HomepageDataService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ProgramCollection extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'source_type',
        'category_id',
        'is_active',
        'sort_order',
        'public_settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'public_settings' => 'array',
    ];

    public function resolvePublicSettings(): array
    {
        $defaults = [
            'display_variant' => 'grid',
            'desktop_columns' => 4,
            'tablet_columns' => 3,
            'mobile_columns' => 2,
            'gap_size' => 'medium',
            'page_size' => 'all',
            'show_description' => true,
            'custom_responsive' => false,
            'responsive_settings' => [
                'tablet' => [],
                'mobile' => [],
            ],
        ];

        if (blank($this->public_settings)) {
            return $defaults;
        }

        $settings = array_merge($defaults, (array) $this->public_settings);
        $resolvedResponsive = \App\Services\Home\HomepageBlockRegistry::resolveResponsiveSettings($settings);

        $settings['desktop_columns'] = $resolvedResponsive['desktop']['columns'];
        $settings['tablet_columns'] = $resolvedResponsive['tablet']['columns'];
        $settings['mobile_columns'] = $resolvedResponsive['mobile']['columns'];

        return $settings;
    }

    public function getResolvedPageDesignAttribute(): array
    {
        return \App\Support\CollectionPageDesignResolver::resolve($this, 'program');
    }

    public const SOURCE_TYPES = [
        'active_period_schedule' => 'Güncel Programlar — Aktif Yayın Dönemindeki Tüm Programlar',
        'category' => 'Kategoriye Bağlı (Otomatik)',
        'all_programs' => 'Tüm Programlar — Tüm Aktif Programlar',
        'manual' => 'Manuel Seçim',
        'active_period_live_programs' => 'Öne Çıkanlar — Aktif Yayın Dönemindeki Canlı Programlar',
        'archive_programs' => 'Arşiv Programları — Arşiv Kategorisine Bağlı (Otomatik)',
        'hybrid' => 'Hibrit',
        'featured' => 'Öne Çıkanlar (Eski)',
    ];

    public static function getFormSourceTypeOptions(): array
    {
        return [
            'active_period_schedule' => 'Güncel Programlar — Aktif Yayın Dönemindeki Tüm Programlar',
            'category' => 'Kategoriye Bağlı (Otomatik)',
            'all_programs' => 'Tüm Programlar — Tüm Aktif Programlar',
            'manual' => 'Manuel Seçim',
            'active_period_live_programs' => 'Öne Çıkanlar — Aktif Yayın Dönemindeki Canlı Programlar',
            'archive_programs' => 'Arşiv Programları — Arşiv Kategorisine Bağlı (Otomatik)',
            'hybrid' => 'Hibrit',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (ProgramCollection $collection) {
            if (blank($collection->slug)) {
                $collection->slug = Str::slug($collection->name);
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function programs(): BelongsToMany
    {
        return $this->belongsToMany(Program::class, 'program_collection_program')
            ->withPivot('id', 'sort_order', 'is_pinned')
            ->withTimestamps()
            ->orderBy('program_collection_program.sort_order');
    }

    /**
     * Resolves programs attached to the central 'Arşiv' category safely.
     */
    public function resolveArchiveCategoryPrograms(?int $limit = null): Collection
    {
        $archiveCategory = Category::query()
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('slug', 'arsiv')
                    ->orWhere('slug', 'archive')
                    ->orWhere('name', 'like', '%Arşiv%');
            })
            ->first();

        if (! $archiveCategory) {
            return collect();
        }

        $programs = Program::query()
            ->with(['categories'])
            ->where('show_on_public', true)
            ->where('is_active', true)
            ->whereHas('categories', fn ($q) => $q->where('categories.id', $archiveCategory->id))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return $limit === null ? $programs : $programs->take($limit);
    }

    public function applyCustomOrdering(Collection $programs): Collection
    {
        $customOrder = $this->public_settings['custom_program_order'] ?? null;

        if (empty($customOrder) || ! is_array($customOrder) || $programs->isEmpty()) {
            return $programs;
        }

        $orderMap = array_flip(array_values(array_map('intval', $customOrder)));

        return $programs->sortBy(function (Program $program) use ($orderMap) {
            return $orderMap[$program->id] ?? 999999;
        })->values();
    }

    /**
     * Resolves unique public/active programs for this collection based on source_type.
     */
    public function resolvePrograms(?int $limit = null): Collection
    {
        if (! $this->is_active) {
            return collect();
        }

        $baseQuery = Program::query()
            ->with(['categories'])
            ->where('show_on_public', true)
            ->where('is_active', true);

        $programs = match ($this->source_type) {
            'active_period_schedule' => app(HomepageDataService::class)->resolveActivePeriodWeeklyPrograms(),

            'category' => $this->category_id
                ? (clone $baseQuery)
                    ->whereHas('categories', fn ($q) => $q->where('categories.id', $this->category_id))
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->get()
                : collect(),

            'all_programs' => (clone $baseQuery)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),

            'manual' => $this->programs()
                ->where('show_on_public', true)
                ->where('is_active', true)
                ->get(),

            'active_period_live_programs' => app(HomepageDataService::class)->resolveActivePeriodWeeklyLivePrograms(),

            'archive_programs' => $this->resolveArchiveCategoryPrograms(),

            'featured' => (clone $baseQuery)
                ->where('is_featured', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),

            'hybrid' => (function () use ($baseQuery) {
                $pinnedPrograms = $this->programs()
                    ->where('show_on_public', true)
                    ->where('is_active', true)
                    ->get();

                $pinnedIds = $pinnedPrograms->pluck('id')->all();

                $autoQuery = clone $baseQuery;
                if ($this->category_id) {
                    $autoQuery->whereHas('categories', fn ($q) => $q->where('categories.id', $this->category_id));
                }
                if (! empty($pinnedIds)) {
                    $autoQuery->whereNotIn('id', $pinnedIds);
                }

                $autoPrograms = $autoQuery->orderBy('sort_order')->orderBy('name')->get();
                $autoPrograms = $this->applyCustomOrdering($autoPrograms);

                return $pinnedPrograms->concat($autoPrograms)->unique('id')->values();
            })(),

            default => collect(),
        };

        if ($this->source_type !== 'manual' && $this->source_type !== 'hybrid') {
            $programs = $this->applyCustomOrdering($programs);
        }

        return $limit === null ? $programs : $programs->take($limit);
    }
}
