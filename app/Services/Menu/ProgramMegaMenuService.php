<?php

namespace App\Services\Menu;

use App\Models\Category;
use App\Models\Program;
use App\Support\SiteCache;
use Illuminate\Support\Facades\Cache;

class ProgramMegaMenuService
{
    public const CACHE_KEY = 'program_mega_menu_data_v2';

    public function getMenuData(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addHours(24), function () {
            $categories = Category::query()
                ->whereNull('parent_id')
                ->where('is_active', true)
                ->where('show_in_menu', true)
                ->where('show_in_mega_menu', true)
                ->orderBy('sort_order')
                ->with([
                    'activeChildren' => fn ($q) => $q->where('show_in_menu', true)
                        ->where('show_in_mega_menu', true)
                        ->with([
                            'activeChildren' => fn ($q) => $q->where('show_in_menu', true)->where('show_in_mega_menu', true),
                            'programs' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order'),
                        ]),
                    'programs' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order'),
                ])
                ->get();

            $allCategory = $categories->firstWhere('slug', Category::ALL_CATEGORIES_SLUG);
            if (! $allCategory) {
                $allCategory = Category::where('slug', Category::ALL_CATEGORIES_SLUG)->first();
            }

            $otherCategories = $categories->reject(fn ($c) => $c->slug === Category::ALL_CATEGORIES_SLUG);

            $sortedCategories = collect();
            if ($allCategory) {
                $sortedCategories->push($allCategory);
            }
            foreach ($otherCategories as $cat) {
                $sortedCategories->push($cat);
            }

            $allActivePrograms = Program::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get()
                ->map(fn ($p) => [
                    'id' => $p->id,
                    'title' => $p->title,
                    'slug' => $p->slug,
                    'cover_image' => $p->cover_image ? asset('storage/' . $p->cover_image) : null,
                    'url' => route('programs.show', $p),
                    'category_ids' => $p->categories()->pluck('categories.id')->toArray(),
                ]);

            return [
                'categories' => $sortedCategories,
                'all_programs' => $allActivePrograms,
            ];
        });
    }

    public static function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        SiteCache::forgetCategoryTree();
    }
}
