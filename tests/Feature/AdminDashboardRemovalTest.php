<?php

namespace Tests\Feature;

use App\Models\User;
use Filament\Pages\Dashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardRemovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_redirects_to_login(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect('/admin/login');
    }

    public function test_admin_redirects_authenticated_user_to_programs_index(): void
    {
        $user = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get('/admin');

        $response->assertRedirect(route('filament.admin.resources.programs.index'));
    }

    public function test_dashboard_page_is_not_registered_in_panel(): void
    {
        $user = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        // Attempting to visit dashboard route directly should return 404 or redirect away
        $response = $this->actingAs($user)->get('/admin/dashboard');

        $response->assertNotFound();
    }

    public function test_programs_admin_resource_is_accessible(): void
    {
        $user = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('filament.admin.resources.programs.index'));

        $response->assertStatus(200);
        $response->assertSee('Programlar');
    }
}
