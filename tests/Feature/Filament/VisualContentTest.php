<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Banners\BannerResource;
use App\Filament\Resources\Banners\Pages\ListBanners;
use App\Models\Banner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VisualContentTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'administrator']);
    }

    public function test_authorized_user_can_access_visual_content_page(): void
    {
        $this->actingAs($this->admin)
            ->get(BannerResource::getUrl())
            ->assertSuccessful();
    }

    public function test_can_create_hero_banner_and_promotion_visual_content(): void
    {
        $hero = Banner::create([
            'title' => 'Anasayfa Hero Slayt',
            'content_type' => 'hero',
            'image' => 'banners/hero.jpg',
            'is_active' => true,
        ]);

        $banner = Banner::create([
            'title' => 'Sol Yan Banner',
            'content_type' => 'banner',
            'image' => 'banners/side.jpg',
            'is_active' => true,
        ]);

        $promotion = Banner::create([
            'title' => 'Ramazan Özel Tanıtım',
            'content_type' => 'promotion',
            'image' => 'banners/promo.jpg',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('banners', ['id' => $hero->id, 'content_type' => 'hero']);
        $this->assertDatabaseHas('banners', ['id' => $banner->id, 'content_type' => 'banner']);
        $this->assertDatabaseHas('banners', ['id' => $promotion->id, 'content_type' => 'promotion']);
    }

    public function test_expired_or_inactive_content_is_excluded_from_public_homepage(): void
    {
        $activeHero = Banner::create([
            'title' => 'Aktif Slayt',
            'content_type' => 'hero',
            'image' => 'banners/active.jpg',
            'is_active' => true,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(5),
        ]);

        $expiredHero = Banner::create([
            'title' => 'Süresi Dolan Slayt',
            'content_type' => 'hero',
            'image' => 'banners/expired.jpg',
            'is_active' => true,
            'starts_at' => now()->subDays(10),
            'ends_at' => now()->subDay(),
        ]);

        $inactiveHero = Banner::create([
            'title' => 'Pasif Slayt',
            'content_type' => 'hero',
            'image' => 'banners/inactive.jpg',
            'is_active' => false,
        ]);

        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Aktif Slayt');
        $response->assertDontSee('Süresi Dolan Slayt');
        $response->assertDontSee('Pasif Slayt');
    }

    public function test_replication_creates_duplicate_with_kopya_suffix_and_inactive_status(): void
    {
        $banner = Banner::create([
            'title' => 'Ana Slayt',
            'subtitle' => 'Alt Bilgi',
            'content_type' => 'hero',
            'image' => 'banners/main.jpg',
            'is_active' => true,
        ]);

        Livewire::actingAs($this->admin)
            ->test(ListBanners::class)
            ->callTableAction('replicate', $banner);

        $this->assertDatabaseHas('banners', [
            'title' => 'Ana Slayt (Kopya)',
            'is_active' => false,
        ]);

        $this->assertDatabaseHas('banners', [
            'id' => $banner->id,
            'title' => 'Ana Slayt',
            'is_active' => true,
        ]);
    }
}
