<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\TopHeader;
use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Khatms\KhatmResource;
use App\Filament\Resources\LiveStreams\LiveStreamResource;
use App\Filament\Resources\MenuItems\MenuItemResource;
use App\Filament\Resources\Schedules\ScheduleResource;
use App\Models\Category;
use App\Models\Khatm;
use App\Models\LiveStream;
use App\Models\MenuItem;
use App\Models\Program;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationHierarchyTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_resource_registers_navigation(): void
    {
        $this->assertTrue(CategoryResource::shouldRegisterNavigation());
    }

    public function test_admin_categories_list_create_and_edit_pages_are_accessible(): void
    {
        $user = User::factory()->create(['role' => 'administrator']);
        $cat = Category::create(['name' => 'Test Kat', 'slug' => 'test-kat', 'is_active' => true]);

        $this->actingAs($user)->get(CategoryResource::getUrl('index'))->assertOk();
        $this->actingAs($user)->get(CategoryResource::getUrl('create'))->assertOk();
        $this->actingAs($user)->get(CategoryResource::getUrl('edit', ['record' => $cat]))->assertOk();
    }

    public function test_schedule_management_module_is_fully_accessible(): void
    {
        $user = User::factory()->create(['role' => 'administrator']);
        $program = Program::create(['name' => 'Test Program', 'slug' => 'test-program', 'is_active' => true]);
        $schedule = Schedule::create([
            'program_id' => $program->id,
            'day_of_week' => 1,
            'start_time' => '14:00',
            'end_time' => '15:00',
            'is_live' => true,
            'is_repeat' => false,
            'is_active' => true,
        ]);

        $this->actingAs($user)->get(ScheduleResource::getUrl('index'))->assertOk();
        $this->actingAs($user)->get(ScheduleResource::getUrl('create'))->assertOk();
        $this->actingAs($user)->get(ScheduleResource::getUrl('edit', ['record' => $schedule]))->assertOk();
    }

    public function test_hatim_and_cuz_management_module_creates_30_juz_claims_automatically(): void
    {
        $user = User::factory()->create(['role' => 'administrator']);
        $khatm = Khatm::create([
            'title' => 'Test Hatm-i Şerif',
            'slug' => 'test-hatm-i-serif',
            'status' => 'active',
            'total_juz' => 30,
        ]);

        $this->assertEquals(30, $khatm->juzClaims()->count());

        $this->actingAs($user)->get(KhatmResource::getUrl('index'))->assertOk();
        $this->actingAs($user)->get(KhatmResource::getUrl('create'))->assertOk();
        $this->actingAs($user)->get(KhatmResource::getUrl('edit', ['record' => $khatm]))->assertOk();
    }

    public function test_live_stream_management_module_syncs_primary_stream(): void
    {
        $user = User::factory()->create(['role' => 'administrator']);
        $stream = LiveStream::create([
            'title' => 'Dost TV Ana Akış',
            'stream_type' => 'hls',
            'stream_url' => 'https://dost.stream.emsal.im/tv/live.m3u8',
            'is_active' => true,
            'is_currently_live' => true,
            'is_primary' => true,
        ]);

        $this->assertTrue($stream->is_primary);

        $this->actingAs($user)->get(LiveStreamResource::getUrl('index'))->assertOk();
        $this->actingAs($user)->get(LiveStreamResource::getUrl('create'))->assertOk();
        $this->actingAs($user)->get(LiveStreamResource::getUrl('edit', ['record' => $stream]))->assertOk();
    }

    public function test_admin_top_header_items_resolve_correct_admin_module_urls(): void
    {
        $user = User::factory()->create(['role' => 'administrator']);
        $topHeaderPage = new TopHeader();
        $topHeaderPage->ensureHeaderPrimaryMenu();

        $items = MenuItem::where('menu_id', $topHeaderPage->menu->id)->whereNull('parent_id')->get();

        $this->assertGreaterThan(0, $items->count());

        foreach ($items as $item) {
            $adminTargetUrl = $item->admin_target_url;
            $editMenuItemUrl = '/admin/menu-items/' . $item->id . '/edit';

            if ($adminTargetUrl) {
                $response = $this->actingAs($user)->get($adminTargetUrl);
                $response->assertOk();
            }

            $editResponse = $this->actingAs($user)->get($editMenuItemUrl);
            $editResponse->assertOk();
        }
    }
}
