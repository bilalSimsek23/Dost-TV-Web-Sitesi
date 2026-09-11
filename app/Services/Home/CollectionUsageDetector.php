<?php

namespace App\Services\Home;

use App\Models\HomepageLayout;
use App\Models\ProgramCollection;
use App\Models\VideoCollection;

class CollectionUsageDetector
{
    protected ?array $publishedProgramCollectionIds = null;

    protected ?array $draftProgramCollectionIds = null;

    protected ?array $publishedVideoCollectionIds = null;

    protected ?array $draftVideoCollectionIds = null;

    protected function ensureLoaded(): void
    {
        if ($this->publishedProgramCollectionIds !== null) {
            return;
        }

        $this->publishedProgramCollectionIds = [];
        $this->draftProgramCollectionIds = [];
        $this->publishedVideoCollectionIds = [];
        $this->draftVideoCollectionIds = [];

        $layouts = HomepageLayout::query()->get();

        foreach ($layouts as $layout) {
            $isPublishedLayout = (bool) $layout->is_active;

            // Published sections scan (only from active homepage layout)
            if ($isPublishedLayout && ! empty($layout->published_sections)) {
                foreach ($layout->published_sections as $sec) {
                    $bt = $sec['block_type'] ?? '';
                    if ($bt === 'program_showcase' && ! empty($sec['program_collection_id'])) {
                        $this->publishedProgramCollectionIds[] = (int) $sec['program_collection_id'];
                    } elseif (in_array($bt, ['video_collection', 'video_showcase', 'youtube_video_shelf'], true)) {
                        $colId = $sec['collection_id'] ?? ($sec['video_collection_id'] ?? null);
                        if ($colId) {
                            $this->publishedVideoCollectionIds[] = (int) $colId;
                        }
                    }
                }
            }

            // Draft sections scan (from all layouts)
            if (! empty($layout->draft_sections)) {
                foreach ($layout->draft_sections as $sec) {
                    $bt = $sec['block_type'] ?? '';
                    if ($bt === 'program_showcase' && ! empty($sec['program_collection_id'])) {
                        $this->draftProgramCollectionIds[] = (int) $sec['program_collection_id'];
                    } elseif (in_array($bt, ['video_collection', 'video_showcase', 'youtube_video_shelf'], true)) {
                        $colId = $sec['collection_id'] ?? ($sec['video_collection_id'] ?? null);
                        if ($colId) {
                            $this->draftVideoCollectionIds[] = (int) $colId;
                        }
                    }
                }
            }
        }

        $this->publishedProgramCollectionIds = array_values(array_unique($this->publishedProgramCollectionIds));
        $this->draftProgramCollectionIds = array_values(array_unique($this->draftProgramCollectionIds));
        $this->publishedVideoCollectionIds = array_values(array_unique($this->publishedVideoCollectionIds));
        $this->draftVideoCollectionIds = array_values(array_unique($this->draftVideoCollectionIds));
    }

    public function getProgramCollectionUsage(ProgramCollection $collection): array
    {
        $this->ensureLoaded();

        $isPublished = in_array((int) $collection->id, $this->publishedProgramCollectionIds, true);
        $isDraft = in_array((int) $collection->id, $this->draftProgramCollectionIds, true);

        $labels = [];
        if ($isPublished) {
            $labels[] = 'Ana Sayfa';
        }
        if ($isDraft) {
            $labels[] = 'Taslak';
        }
        if (empty($labels)) {
            $labels[] = 'Kullanılmıyor';
        }

        return [
            'isPublished' => $isPublished,
            'isDraft' => $isDraft,
            'labels' => $labels,
            'summary' => implode(' + ', $labels),
        ];
    }

    public function getVideoCollectionUsage(VideoCollection $collection): array
    {
        $this->ensureLoaded();

        $isPublished = in_array((int) $collection->id, $this->publishedVideoCollectionIds, true);
        $isDraft = in_array((int) $collection->id, $this->draftVideoCollectionIds, true);
        $isVideoCenter = (bool) $collection->is_active;

        $labels = [];
        if ($isPublished) {
            $labels[] = 'Ana Sayfa';
        }
        if ($isVideoCenter) {
            $labels[] = 'Video Merkezi';
        }
        if ($isDraft && ! $isPublished) {
            $labels[] = 'Taslak';
        }
        if (empty($labels)) {
            $labels[] = 'Kullanılmıyor';
        }

        return [
            'isPublished' => $isPublished,
            'isDraft' => $isDraft,
            'isVideoCenter' => $isVideoCenter,
            'labels' => $labels,
            'summary' => implode(' + ', $labels),
        ];
    }

    public function getPublishedProgramCollectionIds(): array
    {
        $this->ensureLoaded();
        return $this->publishedProgramCollectionIds;
    }

    public function getDraftProgramCollectionIds(): array
    {
        $this->ensureLoaded();
        return $this->draftProgramCollectionIds;
    }

    public function getPublishedVideoCollectionIds(): array
    {
        $this->ensureLoaded();
        return $this->publishedVideoCollectionIds;
    }

    public function getDraftVideoCollectionIds(): array
    {
        $this->ensureLoaded();
        return $this->draftVideoCollectionIds;
    }
}
