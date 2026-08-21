<?php

namespace Tests\Feature;

use App\Filament\Resources\Roles\RoleResource;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_three_system_roles_exist_after_migration(): void
    {
        $this->assertDatabaseHas('roles', [
            'slug' => 'super-admin',
            'base_role' => 'super_admin',
            'is_system' => true,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('roles', [
            'slug' => 'yonetici',
            'base_role' => 'administrator',
            'is_system' => true,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('roles', [
            'slug' => 'editor',
            'base_role' => 'editor',
            'is_system' => true,
            'is_active' => true,
        ]);
    }

    public function test_system_role_cannot_be_deleted(): void
    {
        $superAdminRole = Role::where('slug', 'super-admin')->first();
        $this->assertNotNull($superAdminRole);

        $result = $superAdminRole->delete();
        $this->assertFalse($result);
        $this->assertDatabaseHas('roles', ['id' => $superAdminRole->id]);
    }

    public function test_system_role_cannot_be_renamed_or_demoted_or_deactivated(): void
    {
        $editorRole = Role::where('slug', 'editor')->first();
        $this->assertNotNull($editorRole);

        $editorRole->update([
            'base_role' => 'super_admin',
            'slug' => 'hacked-slug',
            'is_active' => false,
            'description' => 'Güncellenmiş açıklama',
        ]);

        $editorRole->refresh();

        // Protected fields must not change
        $this->assertEquals('editor', $editorRole->base_role);
        $this->assertEquals('editor', $editorRole->slug);
        $this->assertTrue($editorRole->is_active);

        // Allowed field (description) updates successfully
        $this->assertEquals('Güncellenmiş açıklama', $editorRole->description);
    }

    public function test_custom_editor_role_can_be_created(): void
    {
        $role = Role::create([
            'name' => 'Yayın Editörü',
            'base_role' => 'editor',
            'description' => 'Yayın akışından sorumlu editör.',
        ]);

        $this->assertEquals('yayin-editoru', $role->slug);
        $this->assertEquals('editor', $role->base_role);
        $this->assertFalse($role->is_system);
        $this->assertTrue($role->is_active);
    }

    public function test_custom_administrator_role_can_be_created(): void
    {
        $role = Role::create([
            'name' => 'Genel Yayın Yönetmeni',
            'base_role' => 'administrator',
            'description' => 'Yayın yönetimi yöneticisi.',
        ]);

        $this->assertEquals('genel-yayin-yonetmeni', $role->slug);
        $this->assertEquals('administrator', $role->base_role);
    }

    public function test_super_admin_can_access_roles_resource(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
        ]);

        $this->actingAs($superAdmin);
        $this->assertTrue(RoleResource::canAccess());
    }

    public function test_administrator_cannot_access_roles_resource(): void
    {
        $admin = User::factory()->create([
            'role' => 'administrator',
        ]);

        $this->actingAs($admin);
        $this->assertFalse(RoleResource::canAccess());
    }

    public function test_editor_cannot_access_roles_resource(): void
    {
        $editor = User::factory()->create([
            'role' => 'editor',
        ]);

        $this->actingAs($editor);
        $this->assertFalse(RoleResource::canAccess());
    }

    public function test_user_role_and_role_id_bidirectional_sync(): void
    {
        // 1. User created with role string gets correct role_id automatically
        $user1 = User::create([
            'name' => 'Test Admin',
            'email' => 'testadmin@example.com',
            'password' => 'secret123',
            'role' => 'super_admin',
        ]);

        $superAdminRoleId = Role::where('slug', 'super-admin')->value('id');
        $this->assertEquals($superAdminRoleId, $user1->role_id);
        $this->assertTrue($user1->isSuperAdmin());
        $this->assertTrue($user1->hasRole('super_admin'));

        // 2. User created with custom role_id gets correct base_role
        $customRole = Role::create([
            'name' => 'Haber Editörü',
            'base_role' => 'editor',
        ]);

        $user2 = User::create([
            'name' => 'Test Editör',
            'email' => 'testeditor@example.com',
            'password' => 'secret123',
            'role_id' => $customRole->id,
        ]);

        $this->assertEquals('editor', $user2->role);
        $this->assertEquals('editor', $user2->baseRole());
        $this->assertTrue($user2->isEditor());
        $this->assertTrue($user2->hasRole('editor'));
        $this->assertTrue($user2->hasRole('haber-editoru'));
    }

    public function test_has_any_role_works_with_both_base_role_and_slug(): void
    {
        $customRole = Role::create([
            'name' => 'Metin Yazarı',
            'base_role' => 'editor',
        ]);

        $user = User::create([
            'name' => 'Yazar',
            'email' => 'yazar@example.com',
            'password' => 'secret123',
            'role_id' => $customRole->id,
        ]);

        $this->assertTrue($user->hasAnyRole(['editor', 'administrator']));
        $this->assertTrue($user->hasAnyRole(['metin-yazari', 'super_admin']));
        $this->assertFalse($user->hasAnyRole(['super_admin', 'administrator']));
    }

    public function test_role_with_assigned_users_cannot_be_deleted(): void
    {
        $customRole = Role::create([
            'name' => 'Kıdemli Editör',
            'base_role' => 'editor',
        ]);

        $user = User::create([
            'name' => 'Ahmet',
            'email' => 'ahmet@example.com',
            'password' => 'secret123',
            'role_id' => $customRole->id,
        ]);

        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $policy = new \App\Policies\RolePolicy();

        $this->assertFalse($policy->delete($superAdmin, $customRole));

        // When user is deleted, custom role can be deleted
        $user->forceDelete();
        $this->assertTrue($policy->delete($superAdmin, $customRole));
    }
}
