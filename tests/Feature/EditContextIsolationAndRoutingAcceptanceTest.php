<?php

namespace Tests\Feature;

use App\Filament\Resources\ProgramCollections\Pages\EditProgramCollection;
use App\Filament\Resources\VideoCollections\Pages\EditVideoCollection;
use App\Models\Category;
use App\Models\Episode;
use App\Models\Page;
use App\Models\Program;
use App\Models\ProgramCollection;
use App\Models\User;
use App\Models\VideoCollection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EditContextIsolationAndRoutingAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected VideoCollection $videoCollection;

    protected ProgramCollection $programCollection;

    protected Page $page;

    protected Program $program;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'email' => 'admin@dosttv.com',
            'role' => 'administrator',
        ]);

        $category = Category::create(['name' => 'Din ve Hayat', 'slug' => 'din-ve-hayat']);
        $this->program = Program::create(['name' => 'Akıştan Program', 'slug' => 'akistan-program', 'category_id' => $category->id, 'status' => 'active']);

        $episode = Episode::create([
            'program_id' => $this->program->id,
            'title' => 'Bölüm 1',
            'slug' => 'bolum-1',
            'youtube_video_id' => 'vid1',
            'published_at' => now(),
            'is_published' => true,
        ]);

        $this->videoCollection = VideoCollection::create([
            'name' => 'Akıştan Videolar',
            'slug' => 'akistan-videolar',
            'is_active' => true,
            'source_type' => 'manual',
            'public_settings' => [
                'desktop_columns' => 4,
                'tablet_columns' => 3,
                'mobile_columns' => 1,
            ],
        ]);
        $this->videoCollection->episodes()->attach($episode->id);

        $this->programCollection = ProgramCollection::create([
            'name' => 'Öne Çıkan Programlar',
            'slug' => 'one-cikan-programlar',
            'is_active' => true,
            'source_type' => 'manual',
            'public_settings' => [
                'desktop_columns' => 5,
                'tablet_columns' => 3,
                'mobile_columns' => 2,
            ],
        ]);
        $this->programCollection->programs()->attach($this->program->id);

        $this->page = Page::create([
            'title' => 'Dost TV Yayın İlkeleri',
            'slug' => 'dost-tv-yayin-ilkeleri',
            'content' => '<p>İlkeler</p>',
            'status' => 'published',
        ]);
    }

    public function test_public_pages_do_not_render_permanent_admin_access_bar(): void
    {
        $response = $this->actingAs($this->admin)->get('/');
        $response->assertSuccessful();

        $response->assertDontSee('DOST TV Yönetim Erişimi');
        $response->assertDontSee('Ana Sayfa Düzenleyici');
        $response->assertDontSee('Yönetim Paneli ›');

        $corporateResponse = $this->actingAs($this->admin)->get('/dost-tv-yayin-ilkeleri');
        $corporateResponse->assertSuccessful();
        $corporateResponse->assertDontSee('DOST TV Yönetim Erişimi');
        $corporateResponse->assertDontSee('Yönetim Paneli ›');
    }

    public function test_video_collection_live_preview_opens_collection_detail_page_with_temporary_test_mode_bar(): void
    {
        Livewire::actingAs($this->admin)
            ->test(EditVideoCollection::class, ['record' => $this->videoCollection->getRouteKey()])
            ->fillForm([
                'public_settings.display_variant' => 'grid',
                'public_settings.desktop_columns' => 6,
                'public_settings.tablet_columns' => 3,
                'public_settings.mobile_columns' => 1,
                'public_settings.gap_size' => 'medium',
                'public_settings.page_size' => 'all',
            ])
            ->mountAction('livePreview')
            ->assertHasNoFormErrors()
            ->assertNotified('Canlı Koleksiyon Test Modu Başlatıldı');

        $token = session('collection_preview_token');
        $this->assertNotEmpty($token);

        $response = $this->actingAs($this->admin)->get('/koleksiyonlar/akistan-videolar?collection_preview_token=' . $token);
        $response->assertSuccessful()
            ->assertSee('KOLEKSİYON TEST MODU')
            ->assertSee('Düzenlemeye Dön');

        $closeResponse = $this->actingAs($this->admin)->get(route('video.collection.preview.close', ['slug' => 'akistan-videolar']));
        $closeResponse->assertRedirect('/koleksiyonlar/akistan-videolar');
    }

    public function test_program_collection_live_preview_opens_collection_detail_page_with_temporary_test_mode_bar(): void
    {
        Livewire::actingAs($this->admin)
            ->test(EditProgramCollection::class, ['record' => $this->programCollection->getRouteKey()])
            ->fillForm([
                'public_settings.display_variant' => 'grid',
                'public_settings.desktop_columns' => 3,
                'public_settings.tablet_columns' => 2,
                'public_settings.mobile_columns' => 1,
                'public_settings.gap_size' => 'medium',
                'public_settings.page_size' => 'all',
            ])
            ->mountAction('livePreview')
            ->assertHasNoFormErrors()
            ->assertNotified('Canlı Koleksiyon Test Modu Başlatıldı');

        $token = session('collection_preview_token');
        $this->assertNotEmpty($token);

        $response = $this->actingAs($this->admin)->get('/program-koleksiyonlari/one-cikan-programlar?collection_preview_token=' . $token);
        $response->assertSuccessful()
            ->assertSee('KOLEKSİYON TEST MODU')
            ->assertSee('Düzenlemeye Dön');

        $closeResponse = $this->actingAs($this->admin)->get(route('program.collection.preview.close', ['slug' => 'one-cikan-programlar']));
        $closeResponse->assertRedirect('/program-koleksiyonlari/one-cikan-programlar');
    }
}
