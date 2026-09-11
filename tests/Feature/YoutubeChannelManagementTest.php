<?php

namespace Tests\Feature;

use App\Filament\Resources\YoutubeChannels\YoutubeChannelResource;
use App\Models\HomepageLayout;
use App\Models\User;
use App\Models\YoutubeChannel;
use App\Services\Home\HomepageBlockRegistry;
use App\Services\Home\HomepageDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class YoutubeChannelManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'email' => 'admin@dosttv.com',
            'is_active' => true,
        ]);
    }

    public function test_can_render_create_and_index_youtube_channel_pages()
    {
        $responseIndex = $this->actingAs($this->admin)->get(YoutubeChannelResource::getUrl('index'));
        $responseIndex->assertStatus(200);

        $responseCreate = $this->actingAs($this->admin)->get(YoutubeChannelResource::getUrl('create'));
        $responseCreate->assertStatus(200);
    }

    public function test_can_create_and_manage_youtube_channels()
    {
        $channel = YoutubeChannel::create([
            'name' => 'Çocuk ve Biz',
            'handle' => '@CocukveBiz',
            'url' => 'https://www.youtube.com/@CocukveBiz',
            'logo' => 'youtube-channels/cocuk-ve-biz.jpg',
            'description' => 'Çocuk ve aile odaklı programlar kanalı.',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->assertDatabaseHas('youtube_channels', [
            'id' => $channel->id,
            'name' => 'Çocuk ve Biz',
            'handle' => '@CocukveBiz',
            'url' => 'https://www.youtube.com/@CocukveBiz',
            'is_active' => true,
        ]);

        $channel->update([
            'name' => 'Çocuk ve Biz Güncellendi',
            'is_active' => false,
        ]);

        $this->assertDatabaseHas('youtube_channels', [
            'id' => $channel->id,
            'name' => 'Çocuk ve Biz Güncellendi',
            'is_active' => false,
        ]);

        $this->assertCount(0, YoutubeChannel::active()->get());
    }

    public function test_homepage_data_service_resolves_only_active_channels_in_correct_order()
    {
        $channel1 = YoutubeChannel::create(['name' => 'Kanal 1', 'is_active' => true, 'sort_order' => 10]);
        $channel2 = YoutubeChannel::create(['name' => 'Kanal 2', 'is_active' => true, 'sort_order' => 5]);
        $channel3 = YoutubeChannel::create(['name' => 'Kanal 3 Pasif', 'is_active' => false, 'sort_order' => 1]);

        $service = app(HomepageDataService::class);

        // Default order test (by sort_order)
        $resolvedDefault = $service->resolveYoutubeChannelShelfData([
            'block_type' => 'youtube_channel_shelf',
        ]);

        $this->assertCount(2, $resolvedDefault);
        $this->assertEquals($channel2->id, $resolvedDefault->first()->id);
        $this->assertEquals($channel1->id, $resolvedDefault->last()->id);

        // Manual channel selection and ordering test
        $resolvedManual = $service->resolveYoutubeChannelShelfData([
            'block_type' => 'youtube_channel_shelf',
            'channel_ids' => [$channel1->id, $channel2->id, $channel3->id],
        ]);

        // Inactive channel3 should be omitted
        $this->assertCount(2, $resolvedManual);
        $this->assertEquals($channel1->id, $resolvedManual->first()->id);
        $this->assertEquals($channel2->id, $resolvedManual->last()->id);
    }

    public function test_public_homepage_renders_youtube_channel_shelf_with_direct_links()
    {
        $channelWithUrl = YoutubeChannel::create([
            'name' => 'Hikmet Arayışları',
            'handle' => '@HikmetArayislari',
            'url' => 'https://www.youtube.com/@HikmetArayislari',
            'is_active' => true,
        ]);

        $channelNoUrl = YoutubeChannel::create([
            'name' => 'URL Olmayan Kanal',
            'handle' => '@NoUrl',
            'url' => null,
            'is_active' => true,
        ]);

        $layout = HomepageLayout::create([
            'name' => 'Test Düzeni',
            'is_active' => true,
            'published_sections' => [
                [
                    'uuid' => 'yt-section-123',
                    'block_type' => HomepageBlockRegistry::BLOCK_YOUTUBE_CHANNEL_SHELF,
                    'visible' => true,
                    'title' => 'Özel YouTube Kanallarımız',
                    'show_title' => true,
                    'channel_ids' => [$channelWithUrl->id, $channelNoUrl->id],
                ],
            ],
        ]);

        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Özel YouTube Kanallarımız');
        $response->assertSee('Hikmet Arayışları');
        $response->assertSee('@HikmetArayislari');
        $response->assertSee('https://www.youtube.com/@HikmetArayislari');
        $response->assertSee('target="_blank"', false);
        $response->assertSee('rel="noopener noreferrer"', false);
        $response->assertSee('URL Olmayan Kanal');
    }

    public function test_draft_sections_are_isolated_until_published()
    {
        $channel = YoutubeChannel::create([
            'name' => 'Yeni Sezon Kanalı',
            'url' => 'https://youtube.com/@yenisezon',
            'is_active' => true,
        ]);

        $layout = HomepageLayout::create([
            'name' => 'Test Layout',
            'is_active' => true,
            'draft_sections' => [
                [
                    'uuid' => 'yt-draft-1',
                    'block_type' => HomepageBlockRegistry::BLOCK_YOUTUBE_CHANNEL_SHELF,
                    'visible' => true,
                    'title' => 'Taslak YouTube Rafı',
                    'channel_ids' => [$channel->id],
                ],
            ],
            'published_sections' => [],
        ]);

        // Public site should NOT render draft sections before publish
        $response = $this->get('/');
        $response->assertDontSee('Taslak YouTube Rafı');
        $response->assertDontSee('Yeni Sezon Kanalı');

        // Publish layout
        $layout->publish();

        // Public site SHOULD now render the published shelf
        $responseAfter = $this->get('/');
        $responseAfter->assertSee('Taslak YouTube Rafı');
        $responseAfter->assertSee('Yeni Sezon Kanalı');
    }

    public function test_deleting_channel_from_shelf_does_not_delete_channel_record()
    {
        $channel = YoutubeChannel::create([
            'name' => 'Kalıcı Kanal',
            'is_active' => true,
        ]);

        $layout = HomepageLayout::create([
            'name' => 'Test Layout',
            'is_active' => true,
            'draft_sections' => [
                [
                    'uuid' => 'yt-draft-1',
                    'block_type' => HomepageBlockRegistry::BLOCK_YOUTUBE_CHANNEL_SHELF,
                    'visible' => true,
                    'channel_ids' => [$channel->id],
                ],
            ],
        ]);

        // Simulate removing channel from shelf block in draft
        $drafts = $layout->draft_sections;
        $drafts[0]['channel_ids'] = [];
        $layout->update(['draft_sections' => $drafts]);

        // The channel record must remain in database!
        $this->assertDatabaseHas('youtube_channels', [
            'id' => $channel->id,
            'name' => 'Kalıcı Kanal',
        ]);
    }

    public function test_youtube_channel_fetch_service_fetches_and_normalizes_channel_info()
    {
        config(['services.youtube.key' => 'TEST_API_KEY']);

        \Illuminate\Support\Facades\Http::fake([
            'https://www.googleapis.com/youtube/v3/channels*' => \Illuminate\Support\Facades\Http::response([
                'items' => [
                    [
                        'id' => 'UC_TEST_CHANNEL_123',
                        'snippet' => [
                            'title' => 'Çocuk ve Biz Official',
                            'customUrl' => '@CocukveBiz',
                            'thumbnails' => [
                                'high' => [
                                    'url' => 'https://yt3.ggpht.com/avatar_high.jpg',
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = app(\App\Services\YouTube\YouTubeChannelFetchService::class);
        $info = $service->fetchChannelInfo('https://www.youtube.com/@CocukveBiz');

        $this->assertEquals('UC_TEST_CHANNEL_123', $info['channel_id']);
        $this->assertEquals('Çocuk ve Biz Official', $info['name']);
        $this->assertEquals('@CocukveBiz', $info['handle']);
        $this->assertEquals('https://www.youtube.com/@CocukveBiz', $info['canonical_url']);
        $this->assertEquals('https://yt3.ggpht.com/avatar_high.jpg', $info['avatar_url']);

        // Verify description or statistics are not included in response array
        $this->assertArrayNotHasKey('statistics', $info);
        $this->assertArrayNotHasKey('description', $info);
    }

    public function test_duplicate_channel_id_throws_friendly_exception()
    {
        config(['services.youtube.key' => 'TEST_API_KEY']);

        YoutubeChannel::create([
            'name' => 'Mevcut Kanal',
            'handle' => '@MevcutKanal',
            'channel_id' => 'UC_DUPLICATE_123',
            'is_active' => true,
        ]);

        \Illuminate\Support\Facades\Http::fake([
            'https://www.googleapis.com/youtube/v3/channels*' => \Illuminate\Support\Facades\Http::response([
                'items' => [
                    [
                        'id' => 'UC_DUPLICATE_123',
                        'snippet' => [
                            'title' => 'Mevcut Kanal',
                            'customUrl' => '@MevcutKanal',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('zaten sisteme kayıtlı');

        $service = app(\App\Services\YouTube\YouTubeChannelFetchService::class);
        $service->fetchChannelInfo('https://www.youtube.com/@MevcutKanal');
    }

    public function test_missing_api_key_throws_user_friendly_runtime_exception()
    {
        config([
            'services.youtube.key' => null,
            'services.youtube.api_key' => null,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('YOUTUBE_API_KEY .env dosyasında bulunamadı');

        $service = app(\App\Services\YouTube\YouTubeChannelFetchService::class);
        $service->fetchChannelInfo('https://www.youtube.com/@CocukveBiz');
    }

    public function test_extract_channel_identifier_parses_various_url_formats()
    {
        $h1 = \App\Support\Youtube::extractChannelIdentifier('https://www.youtube.com/@CocukveBiz');
        $this->assertEquals(['type' => 'handle', 'value' => '@CocukveBiz'], $h1);

        $h2 = \App\Support\Youtube::extractChannelIdentifier('youtube.com/channel/UC1234567890123456789012');
        $this->assertEquals(['type' => 'id', 'value' => 'UC1234567890123456789012'], $h2);

        $h3 = \App\Support\Youtube::extractChannelIdentifier('https://www.youtube.com/c/DostTVOfficial');
        $this->assertEquals(['type' => 'custom', 'value' => 'DostTVOfficial'], $h3);

        $h4 = \App\Support\Youtube::extractChannelIdentifier('https://www.youtube.com/user/DostTVUser');
        $this->assertEquals(['type' => 'username', 'value' => 'DostTVUser'], $h4);
    }
}
