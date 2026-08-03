<?php

namespace App\Models;

use App\Support\SiteCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class Category extends Model
{
    public const MAX_DEPTH = 3;

    public const ALL_CATEGORIES_SLUG = 'tum-kategoriler';

    protected $fillable = [
        'name',
        'slug',
        'parent_id',
        'description',
        'image',
        'icon',
        'color',
        'background_color',
        'text_color',
        'hover_color',
        'card_variant',
        'is_active',
        'is_featured',
        'show_in_menu',
        'show_in_mega_menu',
        'sort_order',
        'seo_title',
        'seo_description',
        'og_image',
        'canonical_url',
        'index_policy',
        'follow_policy',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'show_in_menu' => 'boolean',
        'show_in_mega_menu' => 'boolean',
        'sort_order' => 'integer',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (Category $category) {
            if (blank($category->slug)) {
                $category->slug = Str::slug($category->name);
            }

            // "Tüm Kategoriler" kısıtları
            if ($category->slug === self::ALL_CATEGORIES_SLUG && $category->parent_id !== null) {
                throw ValidationException::withMessages([
                    'parent_id' => '"Tüm Kategoriler" bir alt kategori yapılamaz.',
                ]);
            }

            if ($category->parent_id) {
                $parent = static::find($category->parent_id);

                if ($parent && $parent->slug === self::ALL_CATEGORIES_SLUG) {
                    throw ValidationException::withMessages([
                        'parent_id' => '"Tüm Kategoriler" altına alt kategori eklenemez.',
                    ]);
                }

                if (static::depthOf($category->parent_id) >= self::MAX_DEPTH) {
                    throw ValidationException::withMessages([
                        'parent_id' => 'Kategoriler en fazla '.self::MAX_DEPTH.' seviye derinliğinde olabilir.',
                    ]);
                }
            }

            if ($category->exists && $category->parent_id && static::wouldCreateCycle($category->getKey(), $category->parent_id)) {
                throw ValidationException::withMessages([
                    'parent_id' => 'Bir kategori kendisini veya kendi alt ağacındaki bir kategoriyi üst kategori olarak seçemez.',
                ]);
            }
        });

        static::deleting(function (Category $category) {
            if ($category->slug === self::ALL_CATEGORIES_SLUG) {
                throw ValidationException::withMessages([
                    'slug' => '"Tüm Kategoriler" özel sistem kaydı olup silinemez.',
                ]);
            }
        });

        static::saved(function () {
            \App\Services\Menu\ProgramMegaMenuService::forgetCache();
            SiteCache::forgetCategoryTree();
        });
        static::deleted(function () {
            \App\Services\Menu\ProgramMegaMenuService::forgetCache();
            SiteCache::forgetCategoryTree();
        });
    }

    public static function depthOf(?int $categoryId): int
    {
        if ($categoryId === null) {
            return 0;
        }

        $depth = 0;
        $current = static::find($categoryId);

        while ($current) {
            $depth++;
            $current = $current->parent_id ? static::find($current->parent_id) : null;
        }

        return $depth;
    }

    public static function wouldCreateCycle(int $categoryId, ?int $parentId): bool
    {
        while ($parentId !== null) {
            if ($parentId === $categoryId) {
                return true;
            }

            $parentId = static::find($parentId)?->parent_id;
        }

        return false;
    }

    public function descendantIds(): array
    {
        $ids = [];

        foreach ($this->children as $child) {
            $ids[] = $child->id;
            $ids = [...$ids, ...$child->descendantIds()];
        }

        return $ids;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function programs(): BelongsToMany
    {
        return $this->belongsToMany(Program::class);
    }

    public function episodesCount(): int
    {
        return Episode::query()
            ->whereHas('program.categories', fn ($query) => $query->whereKey($this->getKey()))
            ->count();
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('sort_order');
    }

    public function activeChildren(): HasMany
    {
        return $this->children()->where('is_active', true)->orderBy('sort_order');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeInMenu($query)
    {
        return $query->where('show_in_menu', true);
    }

    public function scopeInMegaMenu($query)
    {
        return $query->where('show_in_mega_menu', true);
    }
}
