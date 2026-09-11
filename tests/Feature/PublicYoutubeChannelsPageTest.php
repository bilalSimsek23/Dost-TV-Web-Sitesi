<?php

namespace Tests\Feature;

use App\Models\HomepageLayout;
use App\Models\YoutubeChannel;
use App\Services\Home\CtaRouteResolver;
use App\Services\Home\HomepageBlockRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicYoutubeChannelsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_youtube_channels_page_renders_active_channels_in_correct_order()
    {
        $channel1 = YoutubeChannel::create([
            'name' => 'Kanal Bir',
            'handle' => '@KanalBir',
            'url' => 'https://www.youtube.com/@KanalBir',
            'logo' => 'channels/c1.jpg',
            'is_active' => true,
            'sort_order' => 10,
        ]);

        $channel2 = YoutubeChannel::create([
            'name' => 'Kanal İki',
            'handle' => '@KanalIki',
            'url' => 'https://www.youtube.com/@KanalIki',
            'logo' => 'channels/c2.jpg',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $inactiveChannel = YoutubeChannel::create([
            'name' => 'Pasif Kanal',
            'handle' => '@PasifKanal',
            'url' => 'https://www.youtube.com/@PasifKanal',
            'is_active' => false,
            'sort_order' => 1,
        ]);

        $response = $this->get(route('youtube-channels.index'));

        $response->assertStatus(200);
        $response->assertSee('DOST TV YouTube Kanalları');
        $response->assertSee('Kanal Bir');
        $response->assertSee('@KanalBir');
        $response->assertSee('https://www.youtube.com/@KanalBir');
        $response->assertSee('target="_blank"', false);
        $response->assertSee('rel="noopener noreferrer"', false);

        $response->assertSee('Kanal İki');
        $response->assertSee('@KanalIki');

        // Pasif kanal görünmemeli
        $response->assertDontSee('Pasif Kanal');
        $response->assertDontSee('@PasifKanal');

        // Kanal İki (sort_order = 2) Kanal Bir (sort_order = 10)'den önce görünmeli
        $content = $response->getContent();
        $pos1 = strpos($content, 'Kanal Bir');
        $pos2 = strpos($content, 'Kanal İki');
        $this->assertTrue($pos2 < $pos1, 'Kanal İki sort_order gereği Kanal Bir\'den önce gösterilmelidir.');
    }

    public function test_video_collection_block_cta_points_to_youtube_channels_index()
    {
        $resolvedUrl = CtaRouteResolver::resolveForVideoBlock([
            'block_type' => HomepageBlockRegistry::BLOCK_VIDEO_COLLECTION,
        ]);

        $this->assertEquals(route('youtube-channels.index'), $resolvedUrl);
        $this->assertNotEquals(route('programs.index'), $resolvedUrl);
    }

    public function test_homepage_video_collection_renders_daha_fazla_izle_pointing_to_youtube_channels()
    {
        $layout = HomepageLayout::create([
            'name' => 'Aktif Ana Sayfa',
            'is_active' => true,
            'published_sections' => [
                [
                    'uuid' => 'block-video-col-1',
                    'block_type' => HomepageBlockRegistry::BLOCK_VIDEO_COLLECTION,
                    'visible' => true,
                    'title' => 'Video Vitrini',
                    'show_title' => true,
                ],
            ],
        ]);

        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Video Vitrini');
        $response->assertSee('Daha Fazla İzle');
        $response->assertSee(route('youtube-channels.index'));
    }

    public function test_remote_https_youtube_avatar_url_renders_directly_without_storage_prefix()
    {
        $remoteAvatarUrl = 'https://yt3.ggpht.com/ytc/AIdro_test_avatar_123=s800-c-k-c0x00ffffff-no-rj';

        $channel = YoutubeChannel::create([
            'name' => 'Uzaktan Avatar Kanalı',
            'handle' => '@UzaktanAvatar',
            'url' => 'https://www.youtube.com/@UzaktanAvatar',
            'logo' => $remoteAvatarUrl,
            'is_active' => true,
        ]);

        $this->assertEquals($remoteAvatarUrl, $channel->avatar_url);

        $response = $this->get(route('youtube-channels.index'));
        $response->assertStatus(200);
        $response->assertSee('Uzaktan Avatar Kanalı');
        $response->assertSee($remoteAvatarUrl, false);
        $response->assertDontSee('/storage/https://', false);
    }
}
