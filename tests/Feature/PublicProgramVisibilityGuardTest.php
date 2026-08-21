<?php

namespace Tests\Feature;

use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicProgramVisibilityGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_active_program_returns_200_ok(): void
    {
        $program = Program::create([
            'name' => 'Canlı Yayın Sohbeti',
            'slug' => 'canli-yayin-sohbeti',
            'status' => 'active',
            'is_active' => true,
            'show_on_public' => true,
        ]);

        $response = $this->get("/programlar/{$program->slug}");

        $response->assertOk();
        $response->assertSee('Canlı Yayın Sohbeti');
    }

    public function test_hidden_program_returns_404_for_public_visitor(): void
    {
        $program = Program::create([
            'name' => 'Gizli Taslak Program',
            'slug' => 'gizli-taslak-program',
            'status' => 'active',
            'is_active' => false,
            'show_on_public' => false,
        ]);

        $response = $this->get("/programlar/{$program->slug}");

        $response->assertNotFound();
    }

    public function test_archived_program_returns_404_for_public_visitor(): void
    {
        $program = Program::create([
            'name' => 'Yönetim Arşivindeki Program',
            'slug' => 'yonetim-arsivindeki-program',
            'status' => 'archived',
            'is_active' => false,
            'show_on_public' => false,
        ]);

        $response = $this->get("/programlar/{$program->slug}");

        $response->assertNotFound();
    }

    public function test_unarchived_and_public_program_returns_200_again(): void
    {
        $program = Program::create([
            'name' => 'Geri Dönen Program',
            'slug' => 'geri-donen-program',
            'status' => 'archived',
            'is_active' => false,
            'show_on_public' => false,
        ]);

        // When archived -> 404
        $this->get("/programlar/{$program->slug}")->assertNotFound();

        // Unarchive program
        $program->update([
            'status' => 'active',
            'is_active' => true,
            'show_on_public' => true,
        ]);

        // When restored -> 200
        $this->get("/programlar/{$program->slug}")->assertOk();
    }

    public function test_logged_in_admin_can_preview_hidden_and_archived_programs(): void
    {
        $admin = User::factory()->create([
            'role' => 'administrator',
            'is_active' => true,
        ]);

        $hiddenProgram = Program::create([
            'name' => 'Önizlenen Gizli Program',
            'slug' => 'onizlenen-gizli-program',
            'status' => 'active',
            'is_active' => false,
            'show_on_public' => false,
        ]);

        $archivedProgram = Program::create([
            'name' => 'Önizlenen Arşivli Program',
            'slug' => 'onizlenen-arsivli-program',
            'status' => 'archived',
            'is_active' => false,
            'show_on_public' => false,
        ]);

        // Unauthenticated -> 404
        $this->get("/programlar/{$hiddenProgram->slug}")->assertNotFound();
        $this->get("/programlar/{$archivedProgram->slug}")->assertNotFound();

        // Authenticated admin -> 200 OK preview
        $this->actingAs($admin)->get("/programlar/{$hiddenProgram->slug}")->assertOk();
        $this->actingAs($admin)->get("/programlar/{$archivedProgram->slug}")->assertOk();
    }

    public function test_deactivated_admin_cannot_bypass_404_guard(): void
    {
        $deactivatedAdmin = User::factory()->create([
            'role' => 'administrator',
            'is_active' => false,
        ]);

        $hiddenProgram = Program::create([
            'name' => 'Gizli Program',
            'slug' => 'gizli-program-deactivated-test',
            'status' => 'active',
            'is_active' => false,
            'show_on_public' => false,
        ]);

        $this->actingAs($deactivatedAdmin)
            ->get("/programlar/{$hiddenProgram->slug}")
            ->assertNotFound();
    }
}
