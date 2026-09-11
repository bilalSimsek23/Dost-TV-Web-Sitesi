<?php

namespace Tests\Feature;

use App\Filament\Resources\Pages\PageResource;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class IletisimGoogleMapsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Page $contactPage;
    protected Page $corporatePage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'email' => 'admin@dosttv.com',
            'role' => 'administrator',
        ]);

        $this->contactPage = Page::updateOrCreate(
            ['slug' => 'iletisim'],
            [
                'title' => 'İletişim',
                'content' => '<p>İletişim içeriği</p>',
                'page_type' => 'corporate',
                'show_in_footer' => true,
                'status' => 'published',
                'settings' => [
                    'map_enabled' => true,
                    'map_title' => 'DOST TV Genel Merkezi',
                    'map_embed_url' => 'https://www.google.com/maps/embed?pb=123',
                    'map_height' => 320,
                ],
            ]
        );

        $this->corporatePage = Page::updateOrCreate(
            ['slug' => 'yayinci-kunye-bilgisi'],
            [
                'title' => 'Yayıncı Künye Bilgisi',
                'content' => '<p>Künye içeriği</p>',
                'page_type' => 'corporate',
                'show_in_footer' => true,
                'status' => 'published',
            ]
        );
    }

    public function test_google_maps_tab_is_only_visible_on_iletisim_page_form(): void
    {
        // On İletişim edit page, Konum / Google Maps tab is visible
        $this->actingAs($this->admin)
            ->get(PageResource::getUrl('edit', ['record' => $this->contactPage]))
            ->assertSuccessful()
            ->assertSee('Konum / Google Maps')
            ->assertSee('Haritayı Göster')
            ->assertSee('Harita Başlığı')
            ->assertSee('Google Maps Embed URL veya Embed Kodu')
            ->assertSee('Harita Yüksekliği (px)');

        // On other corporate pages, Konum / Google Maps tab is NOT visible
        $this->actingAs($this->admin)
            ->get(PageResource::getUrl('edit', ['record' => $this->corporatePage]))
            ->assertSuccessful()
            ->assertDontSee('Konum / Google Maps')
            ->assertDontSee('Harita Yüksekliği (px)');
    }

    public function test_google_maps_embed_url_extractor_and_security_sanitizer(): void
    {
        // Direct Google Maps embed URL
        $this->contactPage->update([
            'settings' => [
                'map_enabled' => true,
                'map_embed_url' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3059',
            ],
        ]);
        $this->assertEquals('https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3059', $this->contactPage->google_maps_embed_url);

        // Full iframe HTML snippet input
        $this->contactPage->update([
            'settings' => [
                'map_enabled' => true,
                'map_embed_url' => '<iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3059" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy"></iframe>',
            ],
        ]);
        $this->assertEquals('https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3059', $this->contactPage->google_maps_embed_url);

        // Untrusted/malicious input
        $this->contactPage->update([
            'settings' => [
                'map_enabled' => true,
                'map_embed_url' => '<script>alert("xss")</script>https://evil.com/map',
            ],
        ]);
        $this->assertNull($this->contactPage->google_maps_embed_url);
    }

    public function test_public_iletisim_page_renders_google_maps_iframe_when_enabled(): void
    {
        $response = $this->get('/iletisim');

        $response->assertSuccessful()
            ->assertSee('İletişim içeriği')
            ->assertSee('DOST TV Genel Merkezi')
            ->assertSee('https://www.google.com/maps/embed?pb=123')
            ->assertSee('loading="lazy"', false)
            ->assertSee('height: 320px;', false);
    }

    public function test_public_iletisim_page_hides_map_when_map_disabled(): void
    {
        $this->contactPage->update([
            'settings' => [
                'map_enabled' => false,
                'map_embed_url' => 'https://www.google.com/maps/embed?pb=123',
            ],
        ]);

        $response = $this->get('/iletisim');

        $response->assertSuccessful()
            ->assertSee('İletişim içeriği')
            ->assertDontSee('https://www.google.com/maps/embed?pb=123');
    }

    public function test_other_pages_never_render_map_even_if_settings_exist(): void
    {
        $this->corporatePage->update([
            'settings' => [
                'map_enabled' => true,
                'map_embed_url' => 'https://www.google.com/maps/embed?pb=123',
            ],
        ]);

        $response = $this->get('/yayinci-kunye-bilgisi');

        $response->assertSuccessful()
            ->assertSee('Künye içeriği')
            ->assertDontSee('https://www.google.com/maps/embed?pb=123');
    }
}
