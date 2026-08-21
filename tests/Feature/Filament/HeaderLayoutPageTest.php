<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\SiteLayout\HeaderLayoutPage;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class HeaderLayoutPageTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Menu $menu;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'administrator']);
        Storage::fake('public');

        $this->menu = Menu::firstOrCreate(
            ['location' => 'header_primary'],
            ['name' => 'Ana Üst Menü', 'is_active' => true]
        );

        MenuItem::create([
            'menu_id' => $this->menu->id,
            'title' => 'Programlar',
            'item_type' => 'program_mega_menu',
            'route_name' => 'programs.index',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        MenuItem::create([
            'menu_id' => $this->menu->id,
            'title' => 'Yayın Akışı',
            'item_type' => 'route',
            'route_name' => 'schedule.index',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        MenuItem::create([
            'menu_id' => $this->menu->id,
            'title' => 'Hatim / Cüz Al',
            'item_type' => 'url',
            'url' => 'https://dosttv.com/hatim',
            'sort_order' => 3,
            'is_active' => true,
        ]);
    }

    public function test_authorized_user_can_access_header_layout_page_and_see_three_tabs(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/site-layout/header')
            ->assertSuccessful()
            ->assertSee('Logo ve Marka')
            ->assertSee('Navigasyon')
            ->assertSee('Header Davranışı')
            ->assertDontSee('Canlı Yayın Butonu')
            ->assertDontSee('Public Header Önizlemesi')
            ->assertSee('Programlar')
            ->assertSee('Yayın Akışı')
            ->assertSee('Hatim / Cüz Al')
            ->assertSee('Yeni Menü Öğesi');
    }

    public function test_header_settings_can_be_saved(): void
    {
        $logo = UploadedFile::fake()->image('custom_logo.png');
        $favicon = UploadedFile::fake()->image('favicon.png');

        Livewire::actingAs($this->admin)
            ->test(HeaderLayoutPage::class)
            ->fillForm([
                'site_name' => 'Dost Medya TV',
                'logo' => $logo,
                'favicon' => $favicon,
                'logo_alt_text' => 'Dost Medya Kurumsal Logo',
                'header_is_sticky' => true,
                'search_is_visible' => false,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $settings = SiteSetting::current();
        $this->assertEquals('Dost Medya TV', $settings->site_name);
        $this->assertEquals('Dost Medya Kurumsal Logo', $settings->logo_alt_text);
        $this->assertTrue($settings->header_is_sticky);
        $this->assertFalse($settings->search_is_visible);
        $this->assertNotNull($settings->logo);
        $this->assertNotNull($settings->favicon);
    }

    public function test_new_menu_item_xxxx_can_be_created_and_renders_on_public_header(): void
    {
        Livewire::actingAs($this->admin)
            ->test(HeaderLayoutPage::class)
            ->callAction('createMenuItem', [
                'title' => 'XXXX',
                'item_type' => 'url',
                'url' => 'https://dosttv.com/xxxx',
                'is_active' => true,
                'open_in_new_tab' => false,
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('menu_items', [
            'menu_id' => $this->menu->id,
            'title' => 'XXXX',
            'url' => 'https://dosttv.com/xxxx',
        ]);

        // Verify public header renders XXXX
        $this->get(route('home'))
            ->assertSuccessful()
            ->assertSee('Programlar')
            ->assertSee('Yayın Akışı')
            ->assertSee('Hatim / Cüz Al')
            ->assertSee('XXXX');
    }

    public function test_menu_items_can_be_reordered(): void
    {
        $items = MenuItem::where('menu_id', $this->menu->id)->orderBy('sort_order')->pluck('id')->toArray();
        $reversed = array_reverse($items);

        Livewire::actingAs($this->admin)
            ->test(HeaderLayoutPage::class)
            ->call('reorderMenuItems', $reversed);

        $firstItem = MenuItem::find($reversed[0]);
        $this->assertEquals(1, $firstItem->sort_order);
    }

    public function test_menu_item_can_be_edited_and_deleted(): void
    {
        $hatim = MenuItem::where('title', 'Hatim / Cüz Al')->first();

        Livewire::actingAs($this->admin)
            ->test(HeaderLayoutPage::class)
            ->callAction('editMenuItem', [
                'title' => 'Hatim ve Cüz Dağıtımı',
                'item_type' => 'url',
                'url' => 'https://dosttv.com/hatim-dagitim',
                'is_active' => true,
            ], ['item' => $hatim->id])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('menu_items', [
            'id' => $hatim->id,
            'title' => 'Hatim ve Cüz Dağıtımı',
        ]);

        Livewire::actingAs($this->admin)
            ->test(HeaderLayoutPage::class)
            ->callAction('deleteMenuItem', [], ['item' => $hatim->id])
            ->assertHasNoActionErrors();

        $this->assertDatabaseMissing('menu_items', [
            'id' => $hatim->id,
        ]);
    }

    public function test_public_header_retains_live_cta_and_logo(): void
    {
        $this->get(route('home'))
            ->assertSuccessful()
            ->assertSee('Dost', false)
            ->assertSee('Canlı')
            ->assertSee(route('live.tv'));
    }
}
