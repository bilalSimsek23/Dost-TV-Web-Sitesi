<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Program;
use App\Services\Home\HomepageDataService;
use App\Services\Menu\ProgramMegaMenuService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgramStateSyncAndVisibilityMatrixTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_and_public_program_is_active_and_visible_everywhere(): void
    {
        $program = Program::create([
            'name' => 'Aktif Program',
            'slug' => 'aktif-program',
            'status' => 'active',
            'show_on_public' => true,
        ]);

        $program->refresh();
        $this->assertTrue($program->is_active);

        // 1. Public list
        $resList = $this->get('/programlar');
        $resList->assertOk();
        $resList->assertSee('Aktif Program');

        // 2. Public detail
        $resDetail = $this->get('/programlar/aktif-program');
        $resDetail->assertOk();

        // 3. Mega menu
        $menuData = app(ProgramMegaMenuService::class)->getMenuData();
        $this->assertArrayHasKey('categories', $menuData);
        $this->assertArrayHasKey('category_details', $menuData);
    }

    public function test_active_and_hidden_program_is_inactive_and_hidden_everywhere(): void
    {
        $program = Program::create([
            'name' => 'Gizli Aktif Program',
            'slug' => 'gizli-aktif-program',
            'status' => 'active',
            'show_on_public' => false,
        ]);

        $program->refresh();
        $this->assertFalse($program->is_active);

        // 1. Public list
        $resList = $this->get('/programlar');
        $resList->assertDontSee('Gizli Aktif Program');

        // 2. Public detail
        $resDetail = $this->get('/programlar/gizli-aktif-program');
        $resDetail->assertNotFound();
    }

    public function test_season_break_and_public_program_is_active_and_visible_everywhere(): void
    {
        $program = Program::create([
            'name' => 'Sezon Arasındaki Program',
            'slug' => 'sezon-arasindaki-program',
            'status' => 'season_break',
            'show_on_public' => true,
        ]);

        $program->refresh();
        $this->assertTrue($program->is_active);

        // 1. Public list
        $resList = $this->get('/programlar');
        $resList->assertOk();
        $resList->assertSee('Sezon Arasındaki Program');

        // 2. Public detail
        $resDetail = $this->get('/programlar/sezon-arasindaki-program');
        $resDetail->assertOk();
    }

    public function test_season_break_and_hidden_program_is_inactive_and_hidden_everywhere(): void
    {
        $program = Program::create([
            'name' => 'Gizli Sezon Arası Program',
            'slug' => 'gizli-sezon-arasi-program',
            'status' => 'season_break',
            'show_on_public' => false,
        ]);

        $program->refresh();
        $this->assertFalse($program->is_active);

        // 1. Public list
        $resList = $this->get('/programlar');
        $resList->assertDontSee('Gizli Sezon Arası Program');

        // 2. Public detail
        $resDetail = $this->get('/programlar/gizli-sezon-arasi-program');
        $resDetail->assertNotFound();
    }

    public function test_archived_program_is_always_inactive_and_hidden(): void
    {
        $program = Program::create([
            'name' => 'Arşivlenmiş Program',
            'slug' => 'arsivlenmis-program',
            'status' => 'archived',
            'show_on_public' => false,
        ]);

        $program->refresh();
        $this->assertFalse($program->is_active);

        $this->get('/programlar')->assertDontSee('Arşivlenmiş Program');
        $this->get('/programlar/arsivlenmis-program')->assertNotFound();
    }

    public function test_completed_program_derives_is_active_false(): void
    {
        $program = Program::create([
            'name' => 'Sona Ermiş Program',
            'slug' => 'sona-ermis-program',
            'status' => 'completed',
            'show_on_public' => true,
        ]);

        $program->refresh();
        $this->assertFalse($program->is_active);
    }

    public function test_switching_status_to_season_break_recalculates_is_active(): void
    {
        $program = Program::create([
            'name' => 'Dinamik Program',
            'slug' => 'dinamik-program',
            'status' => 'active',
            'show_on_public' => true,
        ]);

        $this->assertTrue($program->fresh()->is_active);

        // Transition to season_break
        $program->update(['status' => 'season_break']);
        $this->assertTrue($program->fresh()->is_active);

        // Toggle public to false
        $program->update(['show_on_public' => false]);
        $this->assertFalse($program->fresh()->is_active);

        // Toggle public back to true
        $program->update(['show_on_public' => true]);
        $this->assertTrue($program->fresh()->is_active);
    }
}
