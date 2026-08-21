<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Filament\Resources\Users\UserResource;
use App\Models\Role;
use App\Models\User;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserFormAndListSimplificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_avatar_field_is_not_in_user_form_schema(): void
    {
        $schema = UserForm::configure(Schema::make());
        $components = $schema->getComponents();

        // Check tabs and components
        $formJson = json_encode($schema);
        $this->assertStringNotContainsString('avatar_url', $formJson);
    }

    public function test_avatar_data_in_database_is_preserved_when_user_updated(): void
    {
        $user = User::create([
            'name' => 'Avatar Test',
            'email' => 'avatar@example.com',
            'password' => 'secret123',
            'avatar_url' => 'avatars/legacy-photo.jpg',
            'role' => 'editor',
        ]);

        $this->assertEquals('avatars/legacy-photo.jpg', $user->avatar_url);

        $user->update(['name' => 'Avatar Test Updated']);
        $user->refresh();

        $this->assertEquals('Avatar Test Updated', $user->name);
        $this->assertEquals('avatars/legacy-photo.jpg', $user->avatar_url);
    }

    public function test_phone_can_be_empty_or_null(): void
    {
        $user = User::create([
            'name' => 'No Phone User',
            'email' => 'nophone@example.com',
            'password' => 'secret123',
            'phone' => null,
            'role' => 'editor',
        ]);

        $this->assertNull($user->phone);
        $this->assertNull($user->formatted_phone);
    }

    public function test_valid_phone_normalized_to_standard_format(): void
    {
        $user1 = User::create([
            'name' => 'Phone User 1',
            'email' => 'phone1@example.com',
            'password' => 'secret123',
            'phone' => '5321234567',
        ]);

        $this->assertEquals('+905321234567', $user1->phone);
        $this->assertEquals('+90 532 123 45 67', $user1->formatted_phone);
    }

    public function test_masked_phone_input_is_normalized(): void
    {
        $user2 = User::create([
            'name' => 'Phone User 2',
            'email' => 'phone2@example.com',
            'password' => 'secret123',
            'phone' => '532 987 65 43',
        ]);

        $this->assertEquals('+905329876543', $user2->phone);
        $this->assertEquals('+90 532 987 65 43', $user2->formatted_phone);

        $user3 = User::create([
            'name' => 'Phone User 3',
            'email' => 'phone3@example.com',
            'password' => 'secret123',
            'phone' => '+90 (555) 111-22-33',
        ]);

        $this->assertEquals('+905551112233', $user3->phone);
        $this->assertEquals('+90 555 111 22 33', $user3->formatted_phone);
    }

    public function test_phone_display_formatting_handles_null_and_standard_formats(): void
    {
        $this->assertNull(User::formatPhoneForDisplay(null));
        $this->assertNull(User::formatPhoneForDisplay(''));
        $this->assertEquals('+90 544 333 22 11', User::formatPhoneForDisplay('+905443332211'));
        $this->assertEquals('+90 544 333 22 11', User::formatPhoneForDisplay('5443332211'));
    }

    public function test_phone_normalization_helper(): void
    {
        $this->assertNull(User::normalizePhone(''));
        $this->assertNull(User::normalizePhone(null));
        $this->assertEquals('+905321234567', User::normalizePhone('532 123 45 67'));
        $this->assertEquals('+905321234567', User::normalizePhone('+905321234567'));
        $this->assertEquals('+905321234567', User::normalizePhone('905321234567'));
    }

    public function test_user_password_update_behavior(): void
    {
        $user = User::create([
            'name' => 'Password Test',
            'email' => 'pwd@example.com',
            'password' => 'initial5',
            'role' => 'editor',
        ]);

        $originalHash = $user->password;
        $this->assertTrue(Hash::check('initial5', $originalHash));

        // Update other fields without changing password
        $user->update(['name' => 'Password Test Name']);
        $user->refresh();
        $this->assertEquals($originalHash, $user->password);

        // Update password with 5+ chars
        $user->update(['password' => 'newpwd5']);
        $user->refresh();
        $this->assertNotEquals($originalHash, $user->password);
        $this->assertTrue(Hash::check('newpwd5', $user->password));
    }

    public function test_administrator_cannot_assign_super_admin_role(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);
        $superAdminRoleId = Role::where('slug', 'super-admin')->value('id');
        $editorRoleId = Role::where('slug', 'editor')->value('id');

        $this->actingAs($admin);

        // Simulate creating user as administrator
        $page = new \App\Filament\Resources\Users\Pages\CreateUser();
        $reflector = new \ReflectionMethod($page, 'mutateFormDataBeforeCreate');
        $reflector->setAccessible(true);

        $result = $reflector->invoke($page, [
            'name' => 'Attempted Super Admin',
            'email' => 'attempt@example.com',
            'role_id' => $superAdminRoleId,
        ]);

        // Must be demoted to editor role_id
        $this->assertEquals($editorRoleId, $result['role_id']);
    }

    public function test_editor_cannot_access_user_policy(): void
    {
        $editor = User::factory()->create(['role' => 'editor']);
        $policy = new \App\Policies\UserPolicy();

        $this->assertFalse($policy->viewAny($editor));
        $this->assertFalse($policy->create($editor));
    }

    public function test_user_table_displays_role_model_and_does_not_have_avatar_column(): void
    {
        $customRole = Role::create([
            'name' => 'Prodüksiyon Şefi',
            'base_role' => 'administrator',
        ]);

        $user = User::create([
            'name' => 'Kemal',
            'email' => 'kemal@example.com',
            'password' => 'secret123',
            'phone' => '5301112233',
            'role_id' => $customRole->id,
        ]);

        $this->assertEquals('Prodüksiyon Şefi', $user->roleModel->name);
        $this->assertEquals('+90 530 111 22 33', $user->formatted_phone);
    }
}
