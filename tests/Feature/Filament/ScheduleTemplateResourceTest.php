<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\ScheduleCalendarPage;
use App\Filament\Resources\ScheduleTemplates\Pages\ListScheduleTemplates;
use App\Filament\Resources\ScheduleTemplates\ScheduleTemplateResource;
use App\Models\Program;
use App\Models\ScheduleTemplate;
use App\Models\ScheduleTemplateItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ScheduleTemplateResourceTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'administrator']);
    }

    public function test_authorized_user_can_access_schedule_template_resource(): void
    {
        $this->actingAs($this->admin)
            ->get(ScheduleTemplateResource::getUrl())
            ->assertSuccessful();
    }

    public function test_schedule_templates_are_listed(): void
    {
        $template = ScheduleTemplate::create([
            'name' => 'Genel Yayın Akışı 2026',
            'valid_from' => '2026-01-01',
            'valid_until' => '2026-12-31',
            'status' => 'published',
            'is_active' => true,
        ]);

        Livewire::actingAs($this->admin)
            ->test(ListScheduleTemplates::class)
            ->assertCanSeeTableRecords([$template]);
    }

    public function test_can_create_new_draft_schedule_template(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ListScheduleTemplates::class)
            ->callAction('create', [
                'name' => 'Ramazan Yayınları 2026',
                'valid_from' => '2026-03-01',
                'valid_until' => '2026-03-30',
                'status' => 'draft',
                'is_active' => false,
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('schedule_templates', [
            'name' => 'Ramazan Yayınları 2026',
            'status' => 'draft',
            'is_active' => false,
        ]);
    }

    public function test_single_default_period_enforcement(): void
    {
        $template1 = ScheduleTemplate::create([
            'name' => 'Dönem 1',
            'status' => 'published',
            'is_active' => true,
        ]);

        Livewire::actingAs($this->admin)
            ->test(ListScheduleTemplates::class)
            ->callAction('create', [
                'name' => 'Dönem 2',
                'valid_from' => '2026-06-01',
                'valid_until' => '2026-08-31',
                'status' => 'published',
                'is_active' => true,
            ])
            ->assertHasNoActionErrors();

        $this->assertFalse($template1->fresh()->is_active);
    }

    public function test_duplicate_period_copies_items_independently(): void
    {
        $prog = Program::create(['name' => 'Haberler', 'slug' => 'haberler', 'is_active' => true]);
        $source = ScheduleTemplate::create([
            'name' => 'Yaz Dönemi 2026',
            'status' => 'published',
            'is_active' => true,
        ]);

        $item = ScheduleTemplateItem::create([
            'schedule_template_id' => $source->id,
            'program_id' => $prog->id,
            'day_of_week' => 1,
            'start_time' => '19:00',
            'end_time' => '20:00',
        ]);

        Livewire::actingAs($this->admin)
            ->test(ListScheduleTemplates::class)
            ->callTableAction('copy_template', $source, [
                'new_name' => 'Kış Dönemi 2026',
                'valid_from' => '2026-09-01',
                'valid_until' => '2026-12-31',
                'copy_items' => true,
            ])
            ->assertHasNoTableActionErrors();

        $copy = ScheduleTemplate::where('name', 'Kış Dönemi 2026')->first();
        $this->assertNotNull($copy);
        $this->assertEquals('draft', $copy->status);
        $this->assertFalse($copy->is_active);
        $this->assertEquals(1, $copy->items()->count());

        // Source template is unmutated
        $this->assertEquals('Yaz Dönemi 2026', $source->fresh()->name);
        $this->assertEquals(1, $source->fresh()->items()->count());
    }

    public function test_publishing_empty_period_shows_warning(): void
    {
        $emptyTemplate = ScheduleTemplate::create([
            'name' => 'Boş Dönem',
            'status' => 'draft',
            'is_active' => false,
        ]);

        Livewire::actingAs($this->admin)
            ->test(ListScheduleTemplates::class)
            ->callTableAction('publish', $emptyTemplate);

        $this->assertEquals('draft', $emptyTemplate->fresh()->status);
    }

    public function test_publishing_period_with_items_succeeds(): void
    {
        $prog = Program::create(['name' => 'Dost Sohbeti', 'slug' => 'dost-sohbeti', 'is_active' => true]);
        $template = ScheduleTemplate::create([
            'name' => 'Dolu Dönem',
            'status' => 'draft',
            'is_active' => false,
        ]);

        ScheduleTemplateItem::create([
            'schedule_template_id' => $template->id,
            'program_id' => $prog->id,
            'day_of_week' => 0,
            'start_time' => '10:00',
        ]);

        Livewire::actingAs($this->admin)
            ->test(ListScheduleTemplates::class)
            ->callTableAction('publish', $template)
            ->assertHasNoTableActionErrors();

        $this->assertEquals('published', $template->fresh()->status);
        $this->assertTrue($template->fresh()->is_active);
    }

    public function test_period_archiving_preserves_broadcast_items(): void
    {
        $prog = Program::create(['name' => 'Arşiv Programı', 'slug' => 'arsiv-prog', 'is_active' => true]);
        $template = ScheduleTemplate::create([
            'name' => 'Eski Dönem 2025',
            'status' => 'published',
            'is_active' => true,
        ]);

        $item = ScheduleTemplateItem::create([
            'schedule_template_id' => $template->id,
            'program_id' => $prog->id,
            'day_of_week' => 2,
            'start_time' => '15:00',
        ]);

        Livewire::actingAs($this->admin)
            ->test(ListScheduleTemplates::class)
            ->callTableAction('archive', $template)
            ->assertHasNoTableActionErrors();

        $this->assertEquals('archived', $template->fresh()->status);
        $this->assertFalse($template->fresh()->is_active);
        $this->assertDatabaseHas('schedule_template_items', ['id' => $item->id]);
    }

    public function test_schedule_calendar_page_opens_selected_template_from_query_parameter(): void
    {
        $template = ScheduleTemplate::create([
            'name' => 'Özel Seçili Dönem',
            'status' => 'published',
            'is_active' => false,
        ]);

        $this->actingAs($this->admin)
            ->get('/admin/schedule-calendar?template=' . $template->id)
            ->assertSuccessful();

        Livewire::actingAs($this->admin)
            ->withQueryParams(['template' => $template->id])
            ->test(ScheduleCalendarPage::class)
            ->assertSet('selectedTemplateId', $template->id);
    }
}
