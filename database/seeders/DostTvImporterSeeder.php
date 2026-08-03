<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Episode;
use App\Models\Page;
use App\Models\Program;
use App\Models\SiteSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DostTvImporterSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->info('Updating Site Settings with Dost TV & Dost FM live streams...');

        // 1. Site Settings
        SiteSetting::query()->updateOrCreate(
            ['id' => 1],
            [
                'site_name' => 'Dost TV',
                'logo' => 'https://dosttv.com/wp-content/uploads/2022/02/dost_logo.png',
                'live_tv_type' => 'hls',
                'live_tv_url' => 'https://dost.stream.emsal.im/tv/live.m3u8',
                'radio_stream_url' => 'https://dost.stream.emsal.im/fm/5',
                'radio_name' => 'Dost FM',
            ]
        );

        // 2. Import Corporate Pages
        $this->command?->info('Importing Pages from dosttv.com...');
        $this->importPages();

        // 3. Import TV Programs
        $this->command?->info('Importing TV Shows (Programlar) from dosttv.com...');
        $programMap = $this->importPrograms();

        // 4. Import Episodes
        $this->command?->info('Importing Episodes (Bölümler) from dosttv.com...');
        $this->importEpisodes($programMap);

        $this->command?->info('Dost TV content import completed successfully!');
    }

    private function importPages(): void
    {
        $pageUrls = [
            'https://dosttv.com/wp-json/wp/v2/pages?per_page=20&page=1',
            'https://dosttv.com/wp-json/wp/v2/pages?per_page=20&page=2',
        ];

        $order = 1;
        $ignoredSlugs = ['sample-page', 'test', 'landing-2', 'register', 'login', 'account', 'password-reset'];

        foreach ($pageUrls as $url) {
            $json = @file_get_contents($url);
            if (!$json) continue;
            $items = json_decode($json, true);
            if (!is_array($items)) continue;

            foreach ($items as $item) {
                $slug = $item['slug'] ?? '';
                $title = html_entity_decode(strip_tags($item['title']['rendered'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $content = $item['content']['rendered'] ?? '';

                if (empty($title) || in_array($slug, $ignoredSlugs) || str_contains($slug, '-2')) {
                    continue;
                }

                Page::query()->updateOrCreate(
                    ['slug' => $slug],
                    [
                        'title' => $title,
                        'content' => $content,
                        'show_in_menu' => true,
                        'sort_order' => $order++,
                    ]
                );
            }
        }
    }

    private function importPrograms(): array
    {
        $programMap = [];
        $generalCat = Category::query()->firstOrCreate(['slug' => 'genel'], ['name' => 'Genel Programlar']);
        $diniCat = Category::query()->firstOrCreate(['slug' => 'dini-sohbetler'], ['name' => 'Dini Sohbetler']);

        for ($page = 1; $page <= 5; $page++) {
            $url = "https://dosttv.com/wp-json/wp/v2/tv_show?per_page=20&page={$page}";
            $json = @file_get_contents($url);
            if (!$json) break;
            $items = json_decode($json, true);
            if (!is_array($items) || empty($items)) break;

            foreach ($items as $index => $item) {
                $wpId = $item['id'];
                $title = html_entity_decode(strip_tags($item['title']['rendered'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $slug = $item['slug'] ?? Str::slug($title);
                $content = strip_tags($item['content']['rendered'] ?? $item['excerpt']['rendered'] ?? '');
                $description = Str::limit(trim(html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8')), 500);

                // Featured image or default fallback
                $coverImage = 'https://dosttv.com/wp-content/uploads/2022/02/dost_logo.png';
                if (!empty($item['featured_media'])) {
                    $mediaJson = @file_get_contents("https://dosttv.com/wp-json/wp/v2/media/{$item['featured_media']}");
                    if ($mediaJson) {
                        $mediaData = json_decode($mediaJson, true);
                        if (!empty($mediaData['source_url'])) {
                            $coverImage = $mediaData['source_url'];
                        }
                    }
                }

                $program = Program::query()->updateOrCreate(
                    ['slug' => $slug],
                    [
                        'name' => $title,
                        'description' => $description ?: 'Dost TV program içeriği.',
                        'cover_image' => $coverImage,
                        'trailer_url' => 'https://www.youtube.com/@DostRadyoTV',
                        'is_active' => true,
                        'sort_order' => $index + 1,
                    ]
                );

                $program->categories()->syncWithoutDetaching([$generalCat->id, $diniCat->id]);
                $programMap[$wpId] = $program->id;
            }
        }

        return $programMap;
    }

    private function importEpisodes(array $programMap): void
    {
        $programs = Program::query()->pluck('id')->toArray();
        if (empty($programs)) return;

        for ($page = 1; $page <= 5; $page++) {
            $url = "https://dosttv.com/wp-json/wp/v2/episode?per_page=20&page={$page}";
            $json = @file_get_contents($url);
            if (!$json) break;
            $items = json_decode($json, true);
            if (!is_array($items) || empty($items)) break;

            foreach ($items as $index => $item) {
                $title = html_entity_decode(strip_tags($item['title']['rendered'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $rawContent = $item['content']['rendered'] ?? '';
                $description = Str::limit(trim(strip_tags(html_entity_decode($rawContent, ENT_QUOTES | ENT_HTML5, 'UTF-8'))), 300);

                // Extract YouTube URL (Playlist or Single Video) from content
                $youtubeUrl = null;
                if (preg_match('/[?&]list=([A-Za-z0-9_-]+)/', $rawContent, $matches)) {
                    $youtubeUrl = 'https://www.youtube.com/playlist?list=' . $matches[1];
                } elseif (preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]+)/', $rawContent, $matches)) {
                    $youtubeUrl = 'https://www.youtube.com/watch?v=' . $matches[1];
                }

                // Match to a program
                $programId = reset($programs);
                if (!empty($item['tv_show'])) {
                    $wpTvShowId = is_array($item['tv_show']) ? $item['tv_show'][0] : $item['tv_show'];
                    if (isset($programMap[$wpTvShowId])) {
                        $programId = $programMap[$wpTvShowId];
                    }
                }

                Episode::query()->updateOrCreate(
                    [
                        'program_id' => $programId,
                        'title' => $title,
                    ],
                    [
                        'description' => $description ?: 'Dost TV video bölüm içeriği.',
                        'thumbnail' => 'https://dosttv.com/wp-content/uploads/2022/02/dost_logo.png',
                        'video_source' => 'youtube',
                        'youtube_url' => $youtubeUrl,
                        'aired_at' => substr($item['date'] ?? now()->toDateString(), 0, 10),
                        'sort_order' => $index + 1,
                    ]
                );
            }
        }
    }
}
