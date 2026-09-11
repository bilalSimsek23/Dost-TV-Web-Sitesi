<?php

namespace App\Services\YouTube;

use App\Models\VideoCollection;
use App\Models\YoutubeChannel;
use Illuminate\Support\Collection;

class YoutubeCenterDataService
{
    /**
     * Resolves all structured data for the public /youtube-kanallari (YouTube Merkezi) page directly from active Video Collections.
     *
     * @return array{settings: array, channels: Collection, category_shelves: array}
     */
    public function getCenterData(): array
    {
        // 1. Active YouTube Channels
        $channels = YoutubeChannel::query()
            ->active()
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        // 2. Fixed Public Page Title Settings
        $settings = [
            'page_title' => 'DOST TV YouTube Kanalları',
            'show_page_title' => true,
            'page_subtitle' => 'Tüm programlarımızı, sohbetlerimizi ve özel videolarımızı YouTube kanallarımız üzerinden takip edebilirsiniz.',
            'show_page_subtitle' => true,
            'video_section_title' => 'YouTube Videolarımız',
            'show_video_section_title' => true,
        ];

        // 3. Directly Resolve Active Video Collections as Public Shelves
        $collections = VideoCollection::query()
            ->where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        $resolvedCategoryShelves = [];

        foreach ($collections as $collection) {
            $episodes = $collection->resolveEpisodes();

            if ($episodes->isEmpty()) {
                continue;
            }

            $resolvedCategoryShelves[] = [
                'video_collection_id' => $collection->id,
                'collection' => $collection,
                'title' => $collection->name,
                'show_title' => true,
                'view_mode' => 'shelf',
                'rows' => 1,
                'columns' => 4,
                'gap_size' => 'md',
                'show_arrows' => true,
                'episodes' => $episodes,
            ];
        }

        return [
            'settings' => $settings,
            'channels' => $channels,
            'category_shelves' => $resolvedCategoryShelves,
        ];
    }
}
