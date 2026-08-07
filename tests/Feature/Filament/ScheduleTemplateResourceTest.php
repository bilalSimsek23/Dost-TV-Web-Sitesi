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

    public function test_row_actions_visibility_for_draft_ready_and_active_states(): void
    {
        $draft = ScheduleTemplate::create([
            'name' => 'Taslak Dönem',
            'status' => 'draft',
            'is_active' => false,
        ]);

        $ready = ScheduleTemplate::create([
            'name' => 'Hazır Dönem',
            'status' => 'published',
            'is_active' => false,
        ]);

        $active = ScheduleTemplate::create([
            'name' => 'Gösterimde Dönem',
            'status' => 'published',
            'is_active' => true,
        ]);

        // Taslak: open_schedule, edit, make_ready, delete visible; set_active hidden
        Livewire::actingAs($this->admin)
            ->test(ListScheduleTemplates::class)
            ->assertTableActionExists('open_schedule', record: $draft)
            ->assertTableActionExists('edit', record: $draft)
            ->assertTableActionExists('make_ready', record: $draft)
            ->assertTableActionExists('delete', record: $draft)
            ->assertTableActionHidden('set_active', record: $draft)
            ->assertTableActionDoesNotExist('copy_template', record: $draft)
            ->assertTableActionDoesNotExist('archive', record: $draft)
            ->assertTableActionDoesNotExist('publish', record: $draft);

        // Hazır: open_schedule, edit, set_active, delete visible; make_ready hidden
        Livewire::actingAs($this->admin)
            ->test(ListScheduleTemplates::class)
            ->assertTableActionExists('open_schedule', record: $ready)
            ->assertTableActionExists('edit', record: $ready)
            ->assertTableActionExists('set_active', record: $ready)
            ->assertTableActionExists('delete', record: $ready)
            ->assertTableActionHidden('make_ready', record: $ready);

        // Gösterimde: open_schedule, edit visible; make_ready, set_active, delete hidden
        Livewire::actingAs($this->admin)
            ->test(ListScheduleTemplates::class)
            ->assertTableActionExists('open_schedule', record: $active)
            ->assertTableActionExists('edit', record: $active)
            ->assertTableActionHidden('make_ready', record: $active)
            ->assertTableActionHidden('set_active', record: $active)
            ->assertTableActionHidden('delete', record: $active);
    }

    public function test_make_ready_transitions_draft_to_ready(): void
    {
        $template = ScheduleTemplate::create([
            'name' => 'Taslak Dönem',
            'status' => 'draft',
            'is_active' => false,
        ]);

        Livewire::actingAs($this->admin)
            ->test(ListScheduleTemplates::class)
            ->callTableAction('make_ready', $template)
            ->assertHasNoTableActionErrors();

        $this->assertEquals('published', $template->fresh()->status);
        $this->assertFalse($template->fresh()->is_active);
        $this->assertEquals('Hazır', $template->fresh()->display_status);
    }

    public function test_set_active_transitions_ready_to_active(): void
    {
        $template = ScheduleTemplate::create([
            'name' => 'Hazır Dönem',
            'status' => 'published',
            'is_active' => false,
        ]);

        Livewire::actingAs($this->admin)
            ->test(ListScheduleTemplates::class)
            ->callTableAction('set_active', $template)
            ->assertHasNoTableActionErrors();

        $this->assertEquals('published', $template->fresh()->status);
        $this->assertTrue($template->fresh()->is_active);
        $this->assertEquals('Gösterimde', $template->fresh()->display_status);
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
