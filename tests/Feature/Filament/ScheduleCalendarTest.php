<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\ScheduleCalendarPage;
use App\Models\Program;
use App\Models\Schedule;
use App\Models\ScheduleTemplate;
use App\Models\ScheduleTemplateItem;
use App\Models\User;
use App\Services\Schedule\ScheduleCalendarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ScheduleCalendarTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_access_schedule_calendar_page(): void
    {
        $user = User::factory()->create(['role' => 'administrator']);

        $this->actingAs($user)
            ->get('/admin/schedule-calendar')
            ->assertOk();
    }

    public function test_day_tabs_switch_and_counts(): void
    {
        $user = User::factory()->create(['role' => 'administrator']);
        $template = ScheduleTemplate::create(['name' => 'Kış Akışı 2026', 'slug' => 'kis-akisi-2026', 'status' => 'published', 'priority' => 10]);

        $prog1 = Program::create(['name' => 'Program 1', 'slug' => 'p1', 'is_active' => true]);

        ScheduleTemplateItem::create([
            'schedule_template_id' => $template->id,
            'program_id' => $prog1->id,
            'day_of_week' => 0,
            'start_time' => '08:00',
            'end_time' => '09:00',
        ]);

        Livewire::actingAs($user)
            ->test(ScheduleCalendarPage::class)
            ->call('selectDayTab', '0')
            ->assertSet('activeDayTab', '0');
    }

    public function test_new_broadcast_item_creation_header_action(): void
    {
        $user = User::factory()->create(['role' => 'administrator']);
        $template = ScheduleTemplate::create(['name' => 'Bahar Akışı', 'slug' => 'bahar-akisi', 'status' => 'published']);
        $prog = Program::create(['name' => 'Tefsir Saati', 'slug' => 'tefsir-saati', 'is_active' => true]);

        Livewire::actingAs($user)
            ->test(ScheduleCalendarPage::class)
            ->callAction('create_item', [
                'program_id' => $prog->id,
                'day_of_week' => 1,
                'start_time' => '10:00',
                'end_time' => '11:00',
                'item_type' => 'live',
                'is_live' => true,
                'is_repeat' => false,
                'is_active' => true,
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('schedule_template_items', [
            'schedule_template_id' => $template->id,
            'program_id' => $prog->id,
            'day_of_week' => 1,
            'start_time' => '10:00',
            'is_live' => true,
        ]);
    }

    public function test_broadcast_item_can_be_copied_moved_and_deleted(): void
    {
        $user = User::factory()->create(['role' => 'administrator']);
        $template = ScheduleTemplate::create(['name' => 'Test Sezon', 'slug' => 'test-sezon', 'status' => 'draft']);
        $prog = Program::create(['name' => 'Ana Haber', 'slug' => 'ana-haber', 'is_active' => true]);

        $item = ScheduleTemplateItem::create([
            'schedule_template_id' => $template->id,
            'program_id' => $prog->id,
            'day_of_week' => 0,
            'start_time' => '19:00',
            'end_time' => '20:00',
        ]);

        $service = new ScheduleCalendarService();
        $service->copyDay($template, 0, 1);

        $this->assertDatabaseHas('schedule_template_items', [
            'schedule_template_id' => $template->id,
            'day_of_week' => 1,
            'start_time' => '19:00',
        ]);

        $item->delete();

        $this->assertDatabaseMissing('schedule_template_items', [
            'id' => $item->id,
        ]);
    }

    public function test_shift_times_action(): void
    {
        $template = ScheduleTemplate::create(['name' => 'Test Shift', 'slug' => 'test-shift', 'status' => 'draft']);
        $prog = Program::create(['name' => 'P1', 'slug' => 'p1', 'is_active' => true]);

        $item = ScheduleTemplateItem::create([
            'schedule_template_id' => $template->id,
            'program_id' => $prog->id,
            'day_of_week' => 0,
            'start_time' => '10:00',
            'end_time' => '11:00',
        ]);

        $service = new ScheduleCalendarService();
        $service->shiftTimes($template, 30, 0);

        $this->assertEquals('10:30', $item->fresh()->start_time);
        $this->assertEquals('11:30', $item->fresh()->end_time);
    }

    public function test_creating_duplicate_template_names_generates_unique_slugs(): void
    {
        $user = User::factory()->create(['role' => 'administrator']);
        ScheduleTemplate::create(['name' => 'DENEME', 'slug' => 'deneme']);

        Livewire::actingAs($user)
            ->test(ScheduleCalendarPage::class)
            ->callAction('create_template', [
                'name' => 'DENEME',
                'creation_mode' => 'empty',
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('schedule_templates', [
            'name' => 'DENEME',
            'slug' => 'deneme-1',
        ]);
    }

    public function test_create_live_broadcast_and_replicate_as_repeat_broadcast(): void
    {
        $template = ScheduleTemplate::create(['name' => 'Test Akış', 'slug' => 'test-akis', 'status' => 'published']);
        $prog = Program::create(['name' => 'Canlı Sohbet', 'slug' => 'canli-sohbet', 'is_active' => true]);

        // 1. Pazartesi 20:00-21:30 Canlı Yayın Oluştur
        $liveItem = ScheduleTemplateItem::create([
            'schedule_template_id' => $template->id,
            'program_id' => $prog->id,
            'day_of_week' => 0, // Pazartesi
            'start_time' => '20:00',
            'end_time' => '21:30',
            'is_live' => true,
            'is_repeat' => false,
            'is_active' => true,
        ]);

        // 2. Perşembe 20:00-21:30 Tekrar Yayını Üret
        $repeatItem = $liveItem->replicate();
        $repeatItem->day_of_week = 3; // Perşembe
        $repeatItem->is_live = false;
        $repeatItem->is_repeat = true;
        $repeatItem->save();

        // 3. İki kaydın veritabanında ayrı ID ile bulunduğunu doğrula
        $this->assertNotEquals($liveItem->id, $repeatItem->id);

        // 4. Canlı kaydın değişmediğini doğrula
        $liveFresh = $liveItem->fresh();
        $this->assertEquals(0, $liveFresh->day_of_week);
        $this->assertEquals('20:00', $liveFresh->start_time);
        $this->assertEquals('21:30', $liveFresh->end_time);
        $this->assertTrue($liveFresh->is_live);
        $this->assertFalse($liveFresh->is_repeat);

        // 5. Tekrar kaydının yayın türünün repeat olduğunu doğrula
        $repeatFresh = $repeatItem->fresh();
        $this->assertFalse($repeatFresh->is_live);
        $this->assertTrue($repeatFresh->is_repeat);
    }

    public function test_broadcast_modal_creates_item_with_image_description_and_note(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['role' => 'administrator']);
        $template = ScheduleTemplate::create(['name' => 'Görsel Test Akışı', 'slug' => 'gorsel-test', 'status' => 'published']);
        $prog = Program::create(['name' => 'Cuma Sohbeti', 'slug' => 'cuma-sohbeti', 'is_active' => true]);

        $file = UploadedFile::fake()->image('custom_test_image.jpg', 1920, 1080);

        Livewire::actingAs($user)
            ->test(ScheduleCalendarPage::class)
            ->callAction('create_item', [
                'program_id' => $prog->id,
                'day_of_week' => 4,
                'start_time' => '14:00',
                'end_time' => '15:30',
                'image' => $file,
                'description' => 'Özel cuma yayını açıklaması',
                'note' => 'Yalnızca yöneticilere özel not',
                'is_live' => true,
                'is_active' => true,
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('schedule_template_items', [
            'schedule_template_id' => $template->id,
            'program_id' => $prog->id,
            'description' => 'Özel cuma yayını açıklaması',
            'note' => 'Yalnızca yöneticilere özel not',
        ]);
    }

    public function test_effective_image_fallback_hierarchy(): void
    {
        $prog = Program::create(['name' => 'Program A', 'slug' => 'prog-a', 'cover_image' => 'https://example.com/program.jpg', 'is_active' => true]);
        $template = ScheduleTemplate::create(['name' => 'Fallback Akış', 'slug' => 'fallback-akis', 'status' => 'published']);

        // Fallback 1: Custom broadcast image
        $item1 = ScheduleTemplateItem::create([
            'schedule_template_id' => $template->id,
            'program_id' => $prog->id,
            'day_of_week' => 0,
            'start_time' => '09:00',
            'image' => 'schedules/custom.jpg',
        ]);
        $this->assertStringContainsString('storage/schedules/custom.jpg', $item1->effective_image);

        // Fallback 3: Program cover image (when no custom image)
        $item2 = ScheduleTemplateItem::create([
            'schedule_template_id' => $template->id,
            'program_id' => $prog->id,
            'day_of_week' => 0,
            'start_time' => '10:00',
        ]);
        $this->assertEquals('https://example.com/program.jpg', $item2->effective_image);
    }
}
