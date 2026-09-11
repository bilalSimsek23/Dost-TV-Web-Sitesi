<?php

namespace App\Models;

use App\Services\Home\HomepageDataService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class VideoCollection extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
        'sort_order',
        'source_type',
        'category_id',
        'sort_mode',
        'fallback_source_type',
        'fallback_category_id',
        'public_settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'public_settings' => 'array',
    ];

    public function resolvePublicSettings(): array
    {
        $defaults = [
            'display_variant' => 'grid',
            'desktop_columns' => 4,
            'tablet_columns' => 3,
            'mobile_columns' => 1,
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
        return \App\Support\CollectionPageDesignResolver::resolve($this, 'video');
    }

    public const SOURCE_TYPES = [
        'manual' => 'Manuel Seçim',
        'category' => 'Kategoriye Bağlı (Otomatik)',
        'featured' => 'Öne Çıkan Videolar — Canlı Programlardan',
        'active_period_program_videos' => 'Aktif Yayın Dönemi Programlarının Videoları',
        'hybrid' => 'Hibrit (Sabitlenenler + Otomatik Tamamlama)',
    ];

    public const SORT_MODES = [
        'newest' => 'En Yeni',
        'oldest' => 'En Eski',
        'most_viewed' => 'En Çok İzlenen',
        'most_liked' => 'En Çok Beğenilen',
        'most_commented' => 'En Çok Yorum Alan',
        'manual' => 'Manuel Sıralama',
    ];

    protected static function booted(): void
    {
        static::saving(function (VideoCollection $collection) {
            if (blank($collection->slug)) {
                $collection->slug = Str::slug($collection->name);
            }
            if (blank($collection->source_type)) {
                $collection->source_type = 'manual';
            }
            if (blank($collection->sort_mode)) {
                $collection->sort_mode = 'newest';
            }
            if (is_null($collection->sort_order)) {
                $collection->sort_order = 0;
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

    public function fallbackCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'fallback_category_id');
    }

    public function episodes(): BelongsToMany
    {
        return $this->belongsToMany(Episode::class, 'episode_video_collection')
            ->withPivot('id', 'sort_order')
            ->withTimestamps()
            ->orderBy('episode_video_collection.sort_order');
    }

    /**
     * Resolves Eloquent Builder query for active episodes in this collection.
     */
    public function resolveEpisodesQuery(): \Illuminate\Database\Eloquent\Builder
    {
        if (! $this->is_active) {
            return Episode::query()->whereRaw('1 = 0');
        }

        $sourceType = $this->source_type ?? 'manual';
        $sortMode = $this->sort_mode ?? 'newest';

        if ($sourceType === 'manual') {
            $query = Episode::query()
                ->with(['program.categories'])
                ->where('show_on_public', true)
                ->where('is_active', true)
                ->whereHas('videoCollections', fn ($q) => $q->where('video_collections.id', $this->id));

            return $this->applySortingToQuery($query, $sortMode);
        }

        $baseQuery = Episode::query()
            ->with(['program.categories'])
            ->where('show_on_public', true)
            ->where('is_active', true);

        if ($sourceType === 'category') {
            $catId = $this->category_id;
            if (! $catId) {
                return Episode::query()->whereRaw('1 = 0');
            }

            $query = (clone $baseQuery)->whereHas('program.categories', fn ($q) => $q->where('categories.id', $catId));
            return $this->applySortingToQuery($query, $sortMode);
        }

        if ($sourceType === 'featured') {
            $programs = app(HomepageDataService::class)->resolveActivePeriodWeeklyLivePrograms();
            $programIds = $programs->pluck('id')->all();

            if (empty($programIds)) {
                return Episode::query()->whereRaw('1 = 0');
            }

            $query = (clone $baseQuery)->whereIn('program_id', $programIds);
            return $this->applySortingToQuery($query, $sortMode);
        }

        if ($sourceType === 'active_period_program_videos') {
            $programs = app(HomepageDataService::class)->resolveActivePeriodWeeklyPrograms();
            $programIds = $programs->pluck('id')->all();

            if (empty($programIds)) {
                return Episode::query()->whereRaw('1 = 0');
            }

            $query = (clone $baseQuery)->whereIn('program_id', $programIds);
            return $this->applySortingToQuery($query, $sortMode);
        }

        if ($sourceType === 'hybrid') {
            $pinnedEpisodes = $this->episodes()
                ->with(['program'])
                ->where('show_on_public', true)
                ->where('is_active', true)
                ->get();

            if ($sortMode !== 'manual') {
                $pinnedEpisodes = $this->sortEpisodeCollection($pinnedEpisodes, $sortMode);
            }

            $pinnedIds = $pinnedEpisodes->pluck('id')->all();

            $autoQuery = clone $baseQuery;
            $catId = $this->category_id ?? $this->fallback_category_id;
            if ($catId) {
                $autoQuery->whereHas('program.categories', fn ($q) => $q->where('categories.id', $catId));
            }
            if (! empty($pinnedIds)) {
                $autoQuery->whereNotIn('id', $pinnedIds);
            }

            $autoEpisodes = $this->applySortingAndFetch($autoQuery);
            $mergedIds = $pinnedEpisodes->concat($autoEpisodes)->unique('id')->pluck('id')->all();

            if (empty($mergedIds)) {
                return Episode::query()->whereRaw('1 = 0');
            }

            $query = Episode::query()
                ->with(['program.categories'])
                ->whereIn('id', $mergedIds);

            return $this->applyExplicitIdOrder($query, $mergedIds);
        }

        return Episode::query()->whereRaw('1 = 0');
    }

    protected function applyExplicitIdOrder(\Illuminate\Database\Eloquent\Builder $query, array $ids): \Illuminate\Database\Eloquent\Builder
    {
        if (empty($ids)) {
            return $query;
        }

        $driver = $query->getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            $cases = [];
            $bindings = [];
            foreach ($ids as $index => $id) {
                $cases[] = 'WHEN ? THEN ?';
                $bindings[] = $id;
                $bindings[] = $index;
            }
            $sql = 'CASE id ' . implode(' ', $cases) . ' END ASC';
            return $query->orderByRaw($sql, $bindings);
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        return $query->orderByRaw("FIELD(id, {$placeholders})", $ids);
    }

    protected function applySortingAndFetch($query): Collection
    {
        return $this->applySortingToQuery($query, $this->sort_mode ?? 'newest')->get();
    }

    protected function applySortingToQuery(\Illuminate\Database\Eloquent\Builder $query, string $sortMode): \Illuminate\Database\Eloquent\Builder
    {
        if ($sortMode === 'oldest') {
            return $query->orderBy('aired_at', 'asc')->orderBy('id', 'asc');
        }

        if ($sortMode === 'most_viewed') {
            return $query->orderByRaw('view_count IS NULL ASC, view_count DESC')
                ->orderByDesc('aired_at')
                ->orderByDesc('id');
        }

        if ($sortMode === 'most_liked') {
            return $query->orderByRaw('like_count IS NULL ASC, like_count DESC')
                ->orderByDesc('aired_at')
                ->orderByDesc('id');
        }

        if ($sortMode === 'most_commented') {
            return $query->orderByRaw('comment_count IS NULL ASC, comment_count DESC')
                ->orderByDesc('aired_at')
                ->orderByDesc('id');
        }

        if ($sortMode === 'manual' && ($this->source_type ?? 'manual') === 'manual') {
            return $query->join('episode_video_collection', 'episodes.id', '=', 'episode_video_collection.episode_id')
                ->where('episode_video_collection.video_collection_id', $this->id)
                ->orderBy('episode_video_collection.sort_order', 'asc')
                ->select('episodes.*');
        }

        return $query->orderByDesc('aired_at')->orderByDesc('id');
    }

    /**
     * Resolves unique public/active episodes for this collection based on source_type & sort_mode.
     */
    public function resolveEpisodes(?int $limit = null): Collection
    {
        if (! $this->is_active) {
            return collect();
        }

        $query = $this->resolveEpisodesQuery();

        return $limit === null ? $query->get() : $query->take($limit)->get();
    }

    public function sortEpisodeCollection(Collection $collection, string $sortMode): Collection
    {
        if ($collection->isEmpty()) {
            return $collection;
        }

        switch ($sortMode) {
            case 'oldest':
                return $collection->sort(function ($a, $b) {
                    $timeA = $a->aired_at ? $a->aired_at->timestamp : 0;
                    $timeB = $b->aired_at ? $b->aired_at->timestamp : 0;
                    if ($timeA === $timeB) {
                        return $a->id <=> $b->id;
                    }
                    return $timeA <=> $timeB;
                })->values();

            case 'most_viewed':
                return $collection->sort(function ($a, $b) {
                    $valA = $a->view_count;
                    $valB = $b->view_count;
                    if ($valA === $valB) {
                        $timeA = $a->aired_at ? $a->aired_at->timestamp : 0;
                        $timeB = $b->aired_at ? $b->aired_at->timestamp : 0;
                        if ($timeA === $timeB) {
                            return $b->id <=> $a->id;
                        }
                        return $timeB <=> $timeA;
                    }
                    if ($valA === null) return 1;
                    if ($valB === null) return -1;
                    return $valB <=> $valA;
                })->values();

            case 'most_liked':
                return $collection->sort(function ($a, $b) {
                    $valA = $a->like_count;
                    $valB = $b->like_count;
                    if ($valA === $valB) {
                        $timeA = $a->aired_at ? $a->aired_at->timestamp : 0;
                        $timeB = $b->aired_at ? $b->aired_at->timestamp : 0;
                        if ($timeA === $timeB) {
                            return $b->id <=> $a->id;
                        }
                        return $timeB <=> $timeA;
                    }
                    if ($valA === null) return 1;
                    if ($valB === null) return -1;
                    return $valB <=> $valA;
                })->values();

            case 'most_commented':
                return $collection->sort(function ($a, $b) {
                    $valA = $a->comment_count;
                    $valB = $b->comment_count;
                    if ($valA === $valB) {
                        $timeA = $a->aired_at ? $a->aired_at->timestamp : 0;
                        $timeB = $b->aired_at ? $b->aired_at->timestamp : 0;
                        if ($timeA === $timeB) {
                            return $b->id <=> $a->id;
                        }
                        return $timeB <=> $timeA;
                    }
                    if ($valA === null) return 1;
                    if ($valB === null) return -1;
                    return $valB <=> $valA;
                })->values();

            case 'manual':
                return $collection;

            case 'newest':
            case 'latest':
            default:
                return $collection->sort(function ($a, $b) {
                    $timeA = $a->aired_at ? $a->aired_at->timestamp : 0;
                    $timeB = $b->aired_at ? $b->aired_at->timestamp : 0;
                    if ($timeA === $timeB) {
                        return $b->id <=> $a->id;
                    }
                    return $timeB <=> $timeA;
                })->values();
        }
    }

    protected function shuffleDeterministically(Collection $collection, int $seed): Collection
    {
        $items = $collection->values()->all();
        mt_srand($seed);
        for ($i = count($items) - 1; $i > 0; $i--) {
            $j = mt_rand(0, $i);
            $tmp = $items[$i];
            $items[$i] = $items[$j];
            $items[$j] = $tmp;
        }
        mt_srand();
        return collect($items);
    }
}
