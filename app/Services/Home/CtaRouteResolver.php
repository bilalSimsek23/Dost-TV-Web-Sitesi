<?php

namespace App\Services\Home;

use App\Models\Category;
use App\Models\Program;
use App\Models\ProgramCollection;
use App\Models\VideoCollection;
use Illuminate\Support\Facades\Route;

class CtaRouteResolver
{
    /**
     * Resolves a public URL for a block's "Daha Fazla İzle" CTA.
     * Returns null if no valid, real public route exists.
     */
    public static function resolveForVideoBlock(array $block): ?string
    {
        $sourceMode = $block['source_mode'] ?? 'latest_videos';

        if ($sourceMode === 'manual_collection' || ! empty($block['collection_id'])) {
            $collectionId = $block['collection_id'] ?? null;
            if ($collectionId) {
                $collection = VideoCollection::query()->where('is_active', true)->find($collectionId);
                if ($collection && Route::has('collections.show')) {
                    return route('collections.show', $collection->slug);
                }
            }
        }

        if ($sourceMode === 'program_videos' || ! empty($block['program_id'])) {
            $programId = $block['program_id'] ?? null;
            if ($programId) {
                $program = Program::query()->find($programId);
                if ($program && Route::has('programs.show')) {
                    return route('programs.show', $program->slug);
                }
            }
        }

        if ($sourceMode === 'category_videos' || ! empty($block['category_id'])) {
            $categoryId = $block['category_id'] ?? null;
            if ($categoryId) {
                $category = Category::query()->find($categoryId);
                if ($category) {
                    if (Route::has('categories.show')) {
                        return route('categories.show', $category->slug);
                    }
                    if (Route::has('programs.index')) {
                        return route('programs.index', ['kategori' => $category->slug]);
                    }
                }
            }
        }

        if (Route::has('youtube-channels.index')) {
            return route('youtube-channels.index');
        }

        if (Route::has('programs.index')) {
            return route('programs.index');
        }

        return null;
    }

    /**
     * Resolves a public URL for a program showcase block's CTA.
     */
    public static function resolveForProgramBlock(array $block): ?string
    {
        // 1. Direct collection ID matching
        $collectionId = $block['program_collection_id'] ?? null;

        if ($collectionId) {
            $collection = ProgramCollection::query()->where('is_active', true)->find($collectionId);
            if ($collection && Route::has('program.collections.show')) {
                return route('program.collections.show', $collection->slug);
            }
        }

        // 2. Dynamic matching by source_mode -> ProgramCollection.source_type
        $sourceMode = $block['source_mode'] ?? null;
        if ($sourceMode) {
            $collectionSourceTypes = match ($sourceMode) {
                'active_period_schedule' => ['active_period_schedule'],
                'featured_programs' => ['active_period_live_programs', 'featured'],
                'category_programs' => ['category'],
                'archive_programs' => ['archive_programs'],
                default => [],
            };

            foreach ($collectionSourceTypes as $collectionSourceType) {
                $query = ProgramCollection::query()
                    ->where('is_active', true)
                    ->where('source_type', $collectionSourceType);

                if ($collectionSourceType === 'category' && ! empty($block['category_id'])) {
                    $query->where('category_id', $block['category_id']);
                }

                $matchedCollection = $query->first();
                if ($matchedCollection && Route::has('program.collections.show')) {
                    return route('program.collections.show', $matchedCollection->slug);
                }
            }
        }

        // 3. Fallback to category show/index or general programs index
        if ($sourceMode === 'category_programs' || ! empty($block['category_id'])) {
            $categoryId = $block['category_id'] ?? null;
            if ($categoryId) {
                $category = Category::query()->find($categoryId);
                if ($category) {
                    if (Route::has('categories.show')) {
                        return route('categories.show', $category->slug);
                    }
                    if (Route::has('programs.index')) {
                        return route('programs.index', ['kategori' => $category->slug]);
                    }
                }
            }
        }

        if (Route::has('programs.index')) {
            return route('programs.index');
        }

        return null;
    }
}
