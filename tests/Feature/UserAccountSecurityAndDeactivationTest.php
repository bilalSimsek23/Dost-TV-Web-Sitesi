<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserAccountSecurityAndDeactivationTest extends TestCase
{
    use RefreshDatabase;

    public function test_deactivated_user_cannot_access_filament_panel(): void
    {
        $user = User::factory()->create([
            'email' => 'passive@dosttv.com',
            'password' => Hash::make('password123'),
            'role' => 'editor',
            'is_active' => false,
        ]);

        $panel = \Filament\Facades\Filament::getPanel('admin');
        $this->assertFalse($user->canAccessPanel($panel));
    }

    public function test_deactivating_user_terminates_database_sessions(): void
    {
        $user = User::factory()->create([
            'role' => 'editor',
            'is_active' => true,
        ]);

        // Insert fake session records in sessions table
        DB::table('sessions')->insert([
            'id' => 'session_123',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => 'payload_data',
            'last_activity' => time(),
        ]);

        $this->assertDatabaseHas('sessions', ['id' => 'session_123', 'user_id' => $user->id]);

        // Deactivate user
        $user->update(['is_active' => false]);

        // Session must be deleted
        $this->assertDatabaseMissing('sessions', ['user_id' => $user->id]);
    }

    public function test_reactivated_user_can_access_filament_panel(): void
    {
        $user = User::factory()->create([
            'role' => 'editor',
            'is_active' => false,
        ]);

        $panel = \Filament\Facades\Filament::getPanel('admin');
        $this->assertFalse($user->canAccessPanel($panel));

        $user->update(['is_active' => true]);

        $this->assertTrue($user->canAccessPanel($panel));
    }

    public function test_administrator_can_deactivate_and_activate_regular_users(): void
    {
        $admin = User::factory()->create(['role' => 'administrator', 'is_active' => true]);
        $editor = User::factory()->create(['role' => 'editor', 'is_active' => true]);

        $policy = new UserPolicy();
        $this->assertTrue($policy->update($admin, $editor));
        $this->assertTrue($policy->delete($admin, $editor));

        $editor->update(['is_active' => false]);
        $this->assertFalse($editor->fresh()->is_active);

        $editor->update(['is_active' => true]);
        $this->assertTrue($editor->fresh()->is_active);
    }

    public function test_administrator_cannot_force_delete_any_user(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);
        $editor = User::factory()->create(['role' => 'editor']);

        $policy = new UserPolicy();
        $this->assertFalse($policy->forceDelete($admin, $editor));
    }

    public function test_editor_cannot_access_user_management(): void
    {
        $editor = User::factory()->create(['role' => 'editor']);
        $policy = new UserPolicy();

        $this->assertFalse($policy->viewAny($editor));
        $this->assertFalse($policy->create($editor));
    }

    public function test_administrator_cannot_modify_or_deactivate_super_admin(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $policy = new UserPolicy();
        $this->assertFalse($policy->update($admin, $superAdmin));
        $this->assertFalse($policy->delete($admin, $superAdmin));
        $this->assertFalse($policy->forceDelete($admin, $superAdmin));
    }

    public function test_super_admin_can_force_delete_regular_user(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $editor = User::factory()->create(['role' => 'editor']);

        // Attach dummy session
        DB::table('sessions')->insert([
            'id' => 'session_editor',
            'user_id' => $editor->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => 'payload',
            'last_activity' => time(),
        ]);

        $policy = new UserPolicy();
        $this->assertTrue($policy->forceDelete($superAdmin, $editor));

        $editor->forceDelete();

        $this->assertDatabaseMissing('users', ['id' => $editor->id]);
        $this->assertDatabaseMissing('sessions', ['user_id' => $editor->id]);
    }

    public function test_last_active_super_admin_cannot_be_deactivated(): void
    {
        // Keep only 1 active super admin
        User::where('role', 'super_admin')->delete();
        $soleSuperAdmin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $this->assertTrue(User::isLastActiveSuperAdmin($soleSuperAdmin));

        // Attempt deactivation
        $soleSuperAdmin->update(['is_active' => false]);
        $soleSuperAdmin->refresh();

        $this->assertTrue($soleSuperAdmin->is_active);
    }

    public function test_last_active_super_admin_cannot_be_deleted_or_force_deleted(): void
    {
        User::where('role', 'super_admin')->delete();
        $soleSuperAdmin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $policy = new UserPolicy();
        $this->assertFalse($policy->delete($soleSuperAdmin, $soleSuperAdmin));
        $this->assertFalse($policy->forceDelete($soleSuperAdmin, $soleSuperAdmin));

        $result = $soleSuperAdmin->delete();
        $this->assertFalse($result);
        $this->assertDatabaseHas('users', ['id' => $soleSuperAdmin->id]);
    }

    public function test_last_active_super_admin_cannot_be_demoted(): void
    {
        User::where('role', 'super_admin')->delete();
        $soleSuperAdmin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $editorRoleId = Role::where('slug', 'editor')->value('id');

        // Attempt role change
        $soleSuperAdmin->update(['role_id' => $editorRoleId, 'role' => 'editor']);
        $soleSuperAdmin->refresh();

        $this->assertEquals('super_admin', $soleSuperAdmin->baseRole());
        $this->assertTrue($soleSuperAdmin->isSuperAdmin());
    }

    public function test_multiple_super_admins_allows_one_to_deactivate_themselves(): void
    {
        $superAdmin1 = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $superAdmin2 = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $this->assertFalse(User::isLastActiveSuperAdmin($superAdmin1));

        $superAdmin1->update(['is_active' => false]);
        $superAdmin1->refresh();

        $this->assertFalse($superAdmin1->is_active);
    }

    public function test_administrator_can_deactivate_self_and_terminates_session(): void
    {
        $admin = User::factory()->create(['role' => 'administrator', 'is_active' => true]);

        DB::table('sessions')->insert([
            'id' => 'session_admin_self',
            'user_id' => $admin->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => 'payload',
            'last_activity' => time(),
        ]);

        $admin->update(['is_active' => false]);
        $admin->refresh();

        $this->assertFalse($admin->is_active);
        $this->assertDatabaseMissing('sessions', ['user_id' => $admin->id]);
    }

    public function test_role_change_applies_immediately_without_terminating_session(): void
    {
        $user = User::factory()->create(['role' => 'editor', 'is_active' => true]);
        $adminRoleId = Role::where('slug', 'yonetici')->value('id');

        DB::table('sessions')->insert([
            'id' => 'session_role_change',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => 'payload',
            'last_activity' => time(),
        ]);

        // Change role to administrator
        $user->update(['role_id' => $adminRoleId]);
        $user->refresh();

        $this->assertTrue($user->isAdministrator());
        // Session must still be present!
        $this->assertDatabaseHas('sessions', ['id' => 'session_role_change']);
    }
}
