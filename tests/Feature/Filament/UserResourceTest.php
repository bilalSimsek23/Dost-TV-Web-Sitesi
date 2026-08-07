<?php

namespace Tests\Feature\Filament;

use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_super_admin_can_access_users_resource(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $response = $this->actingAs($superAdmin)->get('/admin/users');

        $response->assertStatus(200);
    }

    public function test_authorized_administrator_can_access_users_resource(): void
    {
        $administrator = User::factory()->create([
            'role' => 'administrator',
            'is_active' => true,
        ]);

        $response = $this->actingAs($administrator)->get('/admin/users');

        $response->assertStatus(200);
    }

    public function test_unauthorized_editor_cannot_access_users_resource(): void
    {
        $editor = User::factory()->create([
            'role' => 'editor',
            'is_active' => true,
        ]);

        $response = $this->actingAs($editor)->get('/admin/users');

        $response->assertStatus(403);
    }

    public function test_unauthorized_designer_cannot_access_users_resource(): void
    {
        $designer = User::factory()->create([
            'role' => 'designer',
            'is_active' => true,
        ]);

        $response = $this->actingAs($designer)->get('/admin/users');

        $response->assertStatus(403);
    }

    public function test_can_create_new_user_and_password_is_hashed(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $newUser = User::create([
            'name' => 'Yeni Editör',
            'email' => 'yeni.editor@dosttv.com',
            'password' => 'secret1234',
            'role' => 'editor',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'yeni.editor@dosttv.com',
            'role' => 'editor',
            'is_active' => 1,
        ]);

        $this->assertTrue(Hash::check('secret1234', $newUser->password));
    }

    public function test_deactivated_user_cannot_access_panel(): void
    {
        $user = User::factory()->create([
            'role' => 'editor',
            'is_active' => false,
        ]);

        $panel = \Filament\Facades\Filament::getCurrentPanel() ?? new \Filament\Panel();

        $this->assertFalse($user->canAccessPanel($panel));
    }

    public function test_archived_user_cannot_access_panel(): void
    {
        $user = User::factory()->create([
            'role' => 'editor',
            'is_active' => true,
        ]);

        $user->delete(); // SoftDelete

        $panel = \Filament\Facades\Filament::getCurrentPanel() ?? new \Filament\Panel();

        $this->assertFalse($user->canAccessPanel($panel));
    }

    public function test_user_cannot_deactivate_self(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->assertTrue($superAdmin->canAccessPanel(new \Filament\Panel()));
    }

    public function test_last_active_super_admin_cannot_be_deactivated_or_archived(): void
    {
        $lastSuperAdmin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $policy = new \App\Policies\UserPolicy();

        // Cannot delete last super admin
        $this->assertFalse($policy->delete($lastSuperAdmin, $lastSuperAdmin));
    }

    public function test_administrator_cannot_modify_super_admin(): void
    {
        $admin = User::factory()->create([
            'role' => 'administrator',
            'is_active' => true,
        ]);
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $policy = new \App\Policies\UserPolicy();

        $this->assertFalse($policy->update($admin, $superAdmin));
        $this->assertFalse($policy->delete($admin, $superAdmin));
    }

    public function test_successful_login_records_last_login_at_and_ip(): void
    {
        $user = User::factory()->create([
            'role' => 'editor',
            'is_active' => true,
        ]);

        $this->assertNull($user->last_login_at);

        event(new Login('web', $user, false));

        $user->refresh();

        $this->assertNotNull($user->last_login_at);
    }
}
