<?php

namespace Tests\Feature;

use App\Models\Program;
use App\Models\YoutubeChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProgramYoutubeChannelSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.youtube.key' => 'TEST_API_KEY']);
    }

    public function test_program_without_youtube_channel_url_does_not_create_youtube_channel()
    {
        Program::create([
            'name' => 'Kanal Olmayan Program',
            'youtube_channel_url' => null,
            'status' => 'active',
            'show_on_public' => true,
        ]);

        $this->assertEquals(0, YoutubeChannel::count());
    }

    public function test_program_with_youtube_channel_url_automatically_creates_youtube_channel()
    {
        Http::fake([
            'https://www.googleapis.com/youtube/v3/channels*' => Http::response([
                'items' => [
                    [
                        'id' => 'UC_PROGRAM_CHANNEL_999',
                        'snippet' => [
                            'title' => 'Çocuk ve Biz YouTube',
                            'customUrl' => '@CocukveBiz',
                            'thumbnails' => [
                                'high' => [
                                    'url' => 'https://yt3.ggpht.com/cocukvebiz.jpg',
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $program = Program::create([
            'name' => 'Çocuk ve Biz',
            'youtube_channel_url' => 'https://www.youtube.com/@CocukveBiz',
            'status' => 'active',
            'show_on_public' => true,
        ]);

        $this->assertEquals(1, YoutubeChannel::count());

        $channel = YoutubeChannel::first();
        $this->assertEquals('UC_PROGRAM_CHANNEL_999', $channel->channel_id);
        $this->assertEquals('Çocuk ve Biz YouTube', $channel->name);
        $this->assertEquals('@CocukveBiz', $channel->handle);
        $this->assertEquals('https://www.youtube.com/@CocukveBiz', $channel->url);
        $this->assertEquals('https://yt3.ggpht.com/cocukvebiz.jpg', $channel->logo);
        $this->assertTrue($channel->is_active);
    }

    public function test_multiple_programs_with_same_youtube_channel_url_do_not_create_duplicates()
    {
        Http::fake([
            'https://www.googleapis.com/youtube/v3/channels*' => Http::response([
                'items' => [
                    [
                        'id' => 'UC_SHARED_CHANNEL_111',
                        'snippet' => [
                            'title' => 'Ortak Kanal',
                            'customUrl' => '@OrtakKanal',
                        ],
                    ],
                ],
            ], 200),
        ]);

        Program::create([
            'name' => 'Program 1',
            'youtube_channel_url' => 'https://www.youtube.com/@OrtakKanal',
            'status' => 'active',
        ]);

        Program::create([
            'name' => 'Program 2',
            'youtube_channel_url' => 'https://www.youtube.com/@OrtakKanal',
            'status' => 'active',
        ]);

        Program::create([
            'name' => 'Program 3',
            'youtube_channel_url' => 'https://www.youtube.com/@OrtakKanal',
            'status' => 'active',
        ]);

        $this->assertEquals(1, YoutubeChannel::count());
        $this->assertDatabaseHas('youtube_channels', [
            'channel_id' => 'UC_SHARED_CHANNEL_111',
            'name' => 'Ortak Kanal',
        ]);
    }

    public function test_updating_program_youtube_channel_url_creates_new_channel_and_preserves_old()
    {
        Http::fake([
            'https://www.googleapis.com/youtube/v3/channels*forHandle=%40KanalBir*' => Http::response([
                'items' => [
                    [
                        'id' => 'UC_KANAL_1',
                        'snippet' => ['title' => 'Kanal Bir', 'customUrl' => '@KanalBir'],
                    ],
                ],
            ], 200),
            'https://www.googleapis.com/youtube/v3/channels*forHandle=%40KanalIki*' => Http::response([
                'items' => [
                    [
                        'id' => 'UC_KANAL_2',
                        'snippet' => ['title' => 'Kanal İki', 'customUrl' => '@KanalIki'],
                    ],
                ],
            ], 200),
        ]);

        $program = Program::create([
            'name' => 'Dinamik Program',
            'youtube_channel_url' => 'https://www.youtube.com/@KanalBir',
            'status' => 'active',
        ]);

        $this->assertEquals(1, YoutubeChannel::count());

        // Update program URL to Kanal İki
        $program->update([
            'youtube_channel_url' => 'https://www.youtube.com/@KanalIki',
        ]);

        $this->assertEquals(2, YoutubeChannel::count());
        $this->assertDatabaseHas('youtube_channels', ['channel_id' => 'UC_KANAL_1']);
        $this->assertDatabaseHas('youtube_channels', ['channel_id' => 'UC_KANAL_2']);
    }

    public function test_deleting_program_does_not_delete_youtube_channel()
    {
        Http::fake([
            'https://www.googleapis.com/youtube/v3/channels*' => Http::response([
                'items' => [
                    [
                        'id' => 'UC_PERSISTENT_CHANNEL',
                        'snippet' => ['title' => 'Kalıcı Kanal', 'customUrl' => '@KalıcıKanal'],
                    ],
                ],
            ], 200),
        ]);

        $program = Program::create([
            'name' => 'Silinecek Program',
            'youtube_channel_url' => 'https://www.youtube.com/@KaliciKanal',
            'status' => 'active',
        ]);

        $this->assertEquals(1, YoutubeChannel::count());

        $program->delete();

        // YoutubeChannel kaydı veritabanında kalmalıdır!
        $this->assertEquals(1, YoutubeChannel::count());
        $this->assertDatabaseHas('youtube_channels', ['channel_id' => 'UC_PERSISTENT_CHANNEL']);
    }

    public function test_api_error_does_not_cause_500_or_fail_program_save()
    {
        Http::fake([
            'https://www.googleapis.com/youtube/v3/channels*' => Http::response(['error' => 'API Unavailable'], 500),
        ]);

        $program = Program::create([
            'name' => 'Hata Toleransı Testi',
            'youtube_channel_url' => 'https://www.youtube.com/@HatalıKanal',
            'status' => 'active',
        ]);

        // Program başarıyla kaydedilmeli, 500 çökme yaşanmamalıdır.
        $this->assertDatabaseHas('programs', ['id' => $program->id, 'name' => 'Hata Toleransı Testi']);
        $this->assertEquals(0, YoutubeChannel::count());
    }

    public function test_auto_created_channel_appears_in_public_youtube_channels_page()
    {
        Http::fake([
            'https://www.googleapis.com/youtube/v3/channels*' => Http::response([
                'items' => [
                    [
                        'id' => 'UC_PUBLIC_AUTO_CHANNEL',
                        'snippet' => [
                            'title' => 'Otomatik Public Kanal',
                            'customUrl' => '@OtomatikPublic',
                            'thumbnails' => ['high' => ['url' => 'https://yt3.ggpht.com/auto.jpg']],
                        ],
                    ],
                ],
            ], 200),
        ]);

        Program::create([
            'name' => 'Otomatik Program',
            'youtube_channel_url' => 'https://www.youtube.com/@OtomatikPublic',
            'status' => 'active',
        ]);

        $response = $this->get(route('youtube-channels.index'));
        $response->assertStatus(200);
        $response->assertSee('Otomatik Public Kanal');
        $response->assertSee('@OtomatikPublic');
        $response->assertSee('https://www.youtube.com/@OtomatikPublic');
    }
}
