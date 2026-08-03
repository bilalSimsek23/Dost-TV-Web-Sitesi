<?php

namespace App\Models;

use App\Services\Menu\MenuResolver;
use App\Support\SiteCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MenuItem extends Model
{
    /** @use HasFactory<\Database\Factories\MenuItemFactory> */
    use HasFactory;

    public const MAX_DEPTH = 3;

    /**
     * Whitelist of safe, parameter-less named routes selectable for the
     * "route" item_type — deliberately excludes routes needing a model
     * parameter (programs.show, pages.show) since this form has no way to
     * pick that parameter.
     */
    public const ROUTE_NAME_OPTIONS = [
        'home' => 'Ana Sayfa',
        'programs.index' => 'Programlar',
        'schedule.index' => 'Yayın Akışı',
        'live.tv' => 'Canlı TV',
        'live.radio' => 'Canlı Radyo',
    ];

    public const ITEM_TYPES = [
        'program_mega_menu' => 'Programlar Mega Menüsü',
        'route' => 'İç Rota',
        'page' => 'Sayfa',
        'url' => 'Dış Bağlantı',
        'dropdown' => 'Açılır Menü',
        'live_tv' => 'Canlı TV',
        'live_radio' => 'Canlı Radyo',
        'schedule' => 'Yayın Akışı',
        'program_listing' => 'Programlar Sayfası',
        'program' => 'Program Detayı',
        'category' => 'Kategori',
        'custom' => 'Özel',
    ];

    protected $fillable = [
        'menu_id',
        'parent_id',
        'title',
        'slug',
        'item_type',
        'linked_model_type',
        'linked_model_id',
        'url',
        'route_name',
        'icon',
        'badge_text',
        'badge_color',
        'css_class',
        'text_color',
        'background_color',
        'hover_text_color',
        'hover_background_color',
        'open_in_new_tab',
        'is_active',
        'show_on_desktop',
        'show_on_mobile',
        'requires_auth',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'open_in_new_tab' => 'boolean',
        'is_active' => 'boolean',
        'show_on_desktop' => 'boolean',
        'show_on_mobile' => 'boolean',
        'requires_auth' => 'boolean',
        'sort_order' => 'integer',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (MenuItem $item) {
            if (blank($item->slug) && filled($item->title)) {
                $item->slug = Str::slug($item->title);
            }

            if ($item->parent_id) {
                $parent = static::find($item->parent_id);

                if ($parent && $parent->menu_id !== $item->menu_id) {
                    throw ValidationException::withMessages([
                        'parent_id' => 'Üst öğe, aynı menüye ait olmayan bir öğe olamaz.',
                    ]);
                }
            }

            if ($item->parent_id && static::depthOf($item->parent_id) >= self::MAX_DEPTH) {
                throw ValidationException::withMessages([
                    'parent_id' => 'Menü öğeleri en fazla '.self::MAX_DEPTH.' seviye derinliğinde olabilir.',
                ]);
            }

            if ($item->exists && $item->parent_id && static::wouldCreateCycle($item->getKey(), $item->parent_id)) {
                throw ValidationException::withMessages([
                    'parent_id' => 'Bir menü öğesi kendisini veya kendi alt ağacındaki bir öğeyi üst öğe olarak seçemez.',
                ]);
            }
        });

        static::saved(function (MenuItem $item) {
            SiteCache::forgetMenu($item->menu->location);
        });

        static::deleted(function (MenuItem $item) {
            SiteCache::forgetMenu($item->menu->location);
        });
    }

    /**
     * Depth (1-based, root = 1) of the given menu item id within its tree.
     */
    public static function depthOf(?int $menuItemId): int
    {
        if ($menuItemId === null) {
            return 0;
        }

        $depth = 0;
        $current = static::find($menuItemId);

        while ($current) {
            $depth++;
            $current = $current->parent_id ? static::find($current->parent_id) : null;
        }

        return $depth;
    }

    /**
     * True if setting $parentId as the parent of $itemId would make $itemId
     * its own ancestor (directly or transitively) — i.e. a cycle.
     */
    public static function wouldCreateCycle(int $itemId, ?int $parentId): bool
    {
        while ($parentId !== null) {
            if ($parentId === $itemId) {
                return true;
            }

            $parentId = static::find($parentId)?->parent_id;
        }

        return false;
    }

    /**
     * All descendant ids of this item, at any depth — used to exclude a
     * subtree from "choose a parent" option lists.
     */
    public function descendantIds(): array
    {
        $ids = [];

        foreach ($this->children as $child) {
            $ids[] = $child->id;
            $ids = [...$ids, ...$child->descendantIds()];
        }

        return $ids;
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'parent_id')->orderBy('sort_order');
    }

    public function linkedModel(): MorphTo
    {
        return $this->morphTo('linkedModel', 'linked_model_type', 'linked_model_id');
    }

    /**
     * Convenience accessor so Blade components never re-implement the
     * item_type -> URL switch themselves; it always defers to MenuResolver.
     */
    public function getResolvedUrlAttribute(): ?string
    {
        return app(MenuResolver::class)->resolve($this);
    }

    /**
     * Resolves the primary admin module management URL for this menu item.
     */
    public function getAdminTargetUrlAttribute(): ?string
    {
        if (! empty($this->metadata['admin_url'])) {
            return $this->metadata['admin_url'];
        }

        $slug = Str::slug($this->title);

        if ($this->item_type === 'program_mega_menu' || $this->item_type === 'program_listing' || $slug === 'programlar') {
            return '/admin/programs';
        }

        if ($this->item_type === 'schedule' || $this->route_name === 'schedule.index' || $slug === 'yayin-akisi') {
            return '/admin/schedule-calendar';
        }

        if ($slug === 'hatim-cuz-al' || $slug === 'hatim' || str_contains($slug, 'hatim')) {
            return '/admin/khatms';
        }

        if ($this->item_type === 'live_tv' || $this->item_type === 'live_radio' || in_array($this->route_name, ['live.tv', 'live.radio']) || $slug === 'canli') {
            return '/admin/live-streams';
        }

        if ($this->item_type === 'page' || $this->linked_model_type === 'page') {
            if ($this->linked_model_id) {
                return '/admin/pages/' . $this->linked_model_id . '/edit';
            }
            return '/admin/pages';
        }

        if ($this->item_type === 'category' || $this->linked_model_type === 'category') {
            return '/admin/categories';
        }

        if ($this->item_type === 'program' && $this->linked_model_id) {
            return '/admin/programs/' . $this->linked_model_id . '/edit';
        }

        return null;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDesktop($query)
    {
        return $query->where('show_on_desktop', true);
    }

    public function scopeMobile($query)
    {
        return $query->where('show_on_mobile', true);
    }
}
