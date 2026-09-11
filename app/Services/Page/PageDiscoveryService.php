<?php

namespace App\Services\Page;

use App\Models\HomepageLayout;
use App\Models\ProgramCollection;
use App\Models\VideoCollection;
use Illuminate\Support\Collection;

class PageDiscoveryService
{
    /**
     * Get all discoverable system and dynamic collection pages.
     */
    public static function getDiscoverablePages(): Collection
    {
        $pages = collect();

        // 1. Core System Pages
        $pages->push([
            'key' => 'home_null',
            'name' => 'Ana Sayfa',
            'page_type' => 'home',
            'target_id' => null,
            'url' => '/',
            'is_system' => true,
        ]);

        $pages->push([
            'key' => 'program_detail_null',
            'name' => 'Program Detay Şablonu',
            'page_type' => 'program_detail',
            'target_id' => null,
            'url' => '/programlar/{slug}',
            'is_system' => true,
        ]);

        $pages->push([
            'key' => 'program_index_null',
            'name' => 'Programlar Sayfası',
            'page_type' => 'program_index',
            'target_id' => null,
            'url' => '/programlar',
            'is_system' => true,
        ]);

        $pages->push([
            'key' => 'schedule_null',
            'name' => 'Yayın Akışı Sayfası',
            'page_type' => 'schedule',
            'target_id' => null,
            'url' => '/yayin-akisi',
            'is_system' => true,
        ]);

        $pages->push([
            'key' => 'live_tv_null',
            'name' => 'Canlı TV Sayfası',
            'page_type' => 'live_tv',
            'target_id' => null,
            'url' => '/canli-tv',
            'is_system' => true,
        ]);

        // 2. Program Collections (Dynamic detail pages)
        $programCollections = ProgramCollection::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        foreach ($programCollections as $pCol) {
            $pages->push([
                'key' => "program_collection_{$pCol->id}",
                'name' => $pCol->name,
                'page_type' => 'program_collection',
                'target_id' => $pCol->id,
                'url' => "/program-koleksiyonlari/{$pCol->slug}",
                'is_system' => false,
            ]);
        }

        // 3. Video Collections (Dynamic detail pages)
        $videoCollections = VideoCollection::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        foreach ($videoCollections as $vCol) {
            $pages->push([
                'key' => "video_collection_{$vCol->id}",
                'name' => $vCol->name,
                'page_type' => 'video_collection',
                'target_id' => $vCol->id,
                'url' => "/koleksiyonlar/{$vCol->slug}",
                'is_system' => false,
            ]);
        }

        // Map layout records to discovered pages
        $existingLayouts = HomepageLayout::all()->groupBy(fn ($l) => $l->page_type . '_' . ($l->target_id ?? 'null'));

        return $pages->map(function ($page) use ($existingLayouts) {
            $groupKey = $page['page_type'] . '_' . ($page['target_id'] ?? 'null');
            $matchingLayouts = $existingLayouts->get($groupKey, collect());

            $activeLayout = $matchingLayouts->firstWhere('is_active', true);
            $latestLayout = $activeLayout ?? $matchingLayouts->first();

            $page['layout'] = $latestLayout;
            $page['has_layout'] = $matchingLayouts->isNotEmpty();

            if (! $page['has_layout']) {
                $page['status_label'] = 'Henüz Düzenlenmedi';
                $page['status_color'] = 'gray';
            } elseif ($latestLayout->is_active) {
                $page['status_label'] = 'Canlı';
                $page['status_color'] = 'success';
            } else {
                $page['status_label'] = 'Taslak / Pasif';
                $page['status_color'] = 'warning';
            }

            return $page;
        });
    }

    /**
     * Finds existing layout or creates the initial layout record for a page_type + target_id.
     */
    public static function findOrCreateLayoutForTarget(string $pageType, ?int $targetId = null, ?string $customName = null): HomepageLayout
    {
        $query = HomepageLayout::query()->where('page_type', $pageType);

        if ($targetId !== null) {
            $query->where('target_id', $targetId);
        } else {
            $query->whereNull('target_id');
        }

        // Return active layout if present, or existing draft layout
        $existing = (clone $query)->where('is_active', true)->first() ?? (clone $query)->first();

        if ($existing) {
            return $existing;
        }

        // Default name resolution
        $name = $customName;
        if (! $name) {
            if ($pageType === 'program_collection' && $targetId) {
                $col = ProgramCollection::find($targetId);
                $name = $col ? $col->name : "Program Koleksiyonu {$targetId}";
            } elseif ($pageType === 'video_collection' && $targetId) {
                $col = VideoCollection::find($targetId);
                $name = $col ? $col->name : "Video Koleksiyonu {$targetId}";
            } else {
                $name = match ($pageType) {
                    'home' => 'Ana Sayfa Düzeni',
                    'program_detail' => 'Program Detay Şablonu',
                    'program_index' => 'Programlar Sayfası Düzeni',
                    'schedule' => 'Yayın Akışı Düzeni',
                    'live_tv' => 'Canlı TV Düzeni',
                    default => ucfirst($pageType) . ' Düzeni',
                };
            }
        }

        return HomepageLayout::create([
            'name' => $name,
            'page_type' => $pageType,
            'target_id' => $targetId,
            'is_active' => false,
            'draft_sections' => [],
            'published_sections' => [],
        ]);
    }
}
