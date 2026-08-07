<?php

namespace App\Services\Menu;

use App\Models\Category;
use App\Models\Program;
use App\Support\SiteCache;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ProgramMegaMenuService
{
    public const CACHE_KEY = 'program_mega_menu_data_v4';

    public function getMenuData(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addHours(24), function () {
            // 1. Query real root categories with active children & active programs eager-loaded
            $realCategories = Category::query()
                ->whereNull('parent_id')
                ->where('slug', '!=', Category::ALL_CATEGORIES_SLUG)
                ->where('is_active', true)
                ->where('show_in_menu', true)
                ->where('show_in_mega_menu', true)
                ->orderBy('sort_order')
                ->with([
                    'activeChildren' => fn ($q) => $q->where('show_in_menu', true)
                        ->where('show_in_mega_menu', true)
                        ->with([
                            'activeChildren' => fn ($q) => $q->where('show_in_menu', true)->where('show_in_mega_menu', true),
                            'programs' => fn ($q) => $q->where('is_active', true)->where('show_on_public', true),
                        ]),
                    'programs' => fn ($q) => $q->where('is_active', true)->where('show_on_public', true),
                ])
                ->get();

            // 2. Query all active and public programs
            $allActivePrograms = Program::query()
                ->where('is_active', true)
                ->where('show_on_public', true)
                ->get();

            // 3. Virtual "Tüm Programlar" Category Object
            $virtualAllCategory = new Category([
                'name' => 'Tüm Programlar',
                'slug' => Category::ALL_CATEGORIES_SLUG,
                'is_active' => true,
                'show_in_menu' => true,
                'show_in_mega_menu' => true,
                'sort_order' => 0,
            ]);
            $virtualAllCategory->id = 0;
            $virtualAllCategory->setRelation('activeChildren', collect());
            $virtualAllCategory->setRelation('programs', $allActivePrograms);

            // Left menu categories collection starting with "Tüm Programlar"
            $categoriesTree = collect([$virtualAllCategory])->concat($realCategories);

            // 4. Flatten all categories (virtual + parents + children + subchildren)
            $flatCategories = collect();
            foreach ($categoriesTree as $cat) {
                $flatCategories->push($cat);
                if ($cat->activeChildren) {
                    foreach ($cat->activeChildren as $child) {
                        $flatCategories->push($child);
                        if ($child->activeChildren) {
                            foreach ($child->activeChildren as $subChild) {
                                $flatCategories->push($subChild);
                            }
                        }
                    }
                }
            }

            // 5. Build category details map with Turkish alphabetical sorting & balanced column distribution
            $categoryDetails = [];

            foreach ($flatCategories as $cat) {
                $isAllCategory = ($cat->slug === Category::ALL_CATEGORIES_SLUG);
                
                // Get raw program collection for this category
                $rawPrograms = $isAllCategory ? $allActivePrograms : $cat->programs;

                // Sort programs in Turkish alphabetical order & ensure uniqueness
                $sortedPrograms = self::sortProgramsAlphabetically($rawPrograms);

                // Map programs to array with route URLs
                $mappedPrograms = $sortedPrograms->map(fn (Program $p) => [
                    'id' => $p->id,
                    'title' => $p->name ?? $p->title,
                    'slug' => $p->slug,
                    'cover_image' => $p->cover_image ? asset('storage/' . $p->cover_image) : null,
                    'url' => route('programs.show', $p),
                ])->all();

                // Calculate column count & balanced distribution
                $distribution = self::distributeIntoBalancedColumns($mappedPrograms);

                $categoryDetails['cat-' . $cat->id] = [
                    'id' => $cat->id,
                    'name' => $cat->name,
                    'slug' => $cat->slug,
                    'right_title' => $isAllCategory ? 'TÜM PROGRAMLAR' : mb_strtoupper($cat->name, 'UTF-8'),
                    'all_url' => route('programs.index', $isAllCategory ? [] : ['kategori' => $cat->slug]),
                    'total_programs' => count($mappedPrograms),
                    'column_count' => $distribution['column_count'],
                    'columns' => $distribution['columns'],
                ];
            }

            return [
                'categories' => $categoriesTree,
                'category_details' => $categoryDetails,
            ];
        });
    }

    /**
     * Sort program collection in Turkish alphabetical order (case-insensitive) & remove duplicates.
     */
    public static function sortProgramsAlphabetically(Collection $programs): Collection
    {
        return $programs->unique('id')->sort(function (Program $a, Program $b) {
            $nameA = $a->name ?? $a->title ?? '';
            $nameB = $b->name ?? $b->title ?? '';

            return self::compareTurkishStrings($nameA, $nameB);
        })->values();
    }

    /**
     * Compare two strings using Turkish alphabetical rules.
     */
    public static function compareTurkishStrings(string $str1, string $str2): int
    {
        if (class_exists(\Collator::class)) {
            try {
                $collator = new \Collator('tr_TR');
                return $collator->compare($str1, $str2);
            } catch (\Throwable $e) {
                // Fallback to custom map below
            }
        }

        $trMap = [
            'a' => 'a0', 'A' => 'a0',
            'b' => 'b0', 'B' => 'b0',
            'c' => 'c0', 'C' => 'c0',
            'ç' => 'c1', 'Ç' => 'c1',
            'd' => 'd0', 'D' => 'd0',
            'e' => 'e0', 'E' => 'e0',
            'f' => 'f0', 'F' => 'f0',
            'g' => 'g0', 'G' => 'g0',
            'ğ' => 'g1', 'Ğ' => 'g1',
            'h' => 'h0', 'H' => 'h0',
            'ı' => 'i0', 'I' => 'i0',
            'i' => 'i1', 'İ' => 'i1',
            'j' => 'j0', 'J' => 'j0',
            'k' => 'k0', 'K' => 'k0',
            'l' => 'l0', 'L' => 'l0',
            'm' => 'm0', 'M' => 'm0',
            'n' => 'n0', 'N' => 'n0',
            'o' => 'o0', 'O' => 'o0',
            'ö' => 'o1', 'Ö' => 'o1',
            'p' => 'p0', 'P' => 'p0',
            'r' => 'r0', 'R' => 'r0',
            's' => 's0', 'S' => 's0',
            'ş' => 's1', 'Ş' => 's1',
            't' => 't0', 'T' => 't0',
            'u' => 'u0', 'U' => 'u0',
            'ü' => 'u1', 'Ü' => 'u1',
            'v' => 'v0', 'V' => 'v0',
            'y' => 'y0', 'Y' => 'y0',
            'z' => 'z0', 'Z' => 'z0',
        ];

        $key1 = strtr(mb_strtolower($str1, 'UTF-8'), $trMap);
        $key2 = strtr(mb_strtolower($str2, 'UTF-8'), $trMap);

        return strcmp($key1, $key2);
    }

    /**
     * Determine column count based on item count:
     * - 1–8 items: 1 column
     * - 9–20 items: 2 columns
     * - 21–40 items: 3 columns
     * - 41+ items: 4 columns
     */
    public static function determineColumnCount(int $totalItems): int
    {
        if ($totalItems <= 8) {
            return 1;
        }
        if ($totalItems <= 20) {
            return 2;
        }
        if ($totalItems <= 40) {
            return 3;
        }

        return 4;
    }

    /**
     * Distribute items across N columns so reading order flows top-to-bottom per column.
     */
    public static function distributeIntoBalancedColumns(array $items): array
    {
        $total = count($items);
        if ($total === 0) {
            return [
                'column_count' => 1,
                'columns' => [[]],
            ];
        }

        $colCount = self::determineColumnCount($total);
        $baseCount = intdiv($total, $colCount);
        $remainder = $total % $colCount;

        $columns = [];
        $offset = 0;

        for ($i = 0; $i < $colCount; $i++) {
            $count = $baseCount + ($i < $remainder ? 1 : 0);
            $columns[] = array_slice($items, $offset, $count);
            $offset += $count;
        }

        return [
            'column_count' => $colCount,
            'columns' => $columns,
        ];
    }

    public static function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        SiteCache::forgetCategoryTree();
    }
}
