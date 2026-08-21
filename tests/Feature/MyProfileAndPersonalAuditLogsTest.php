<?php

namespace Tests\Feature;

use App\Filament\Pages\MyProfilePage;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class MyProfileAndPersonalAuditLogsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_access_hesabim_page(): void
    {
        $user = User::factory()->create([
            'name' => 'Yasemin Çelik',
            'email' => 'yasemin@dosttv.com',
            'role' => 'editor',
        ]);

        $this->actingAs($user);

        $response = $this->get(MyProfilePage::getUrl());
        $response->assertSuccessful();
        $response->assertSee('Hesap Bilgileri');
        $response->assertSee('Şifre Değiştir');
        $response->assertSee('Benim İşlemlerim');
    }

    public function test_user_can_update_name_and_phone_with_normalization(): void
    {
        $user = User::factory()->create([
            'name' => 'Yasemin Çelik',
            'email' => 'yasemin@dosttv.com',
            'phone' => null,
            'role' => 'editor',
        ]);

        $this->actingAs($user);

        Livewire::test(MyProfilePage::class)
            ->set('accountData.name', 'Yasemin Yılmaz')
            ->set('accountData.phone', '5321234567')
            ->call('updateProfile')
            ->assertHasNoErrors();

        $user->refresh();
        $this->assertEquals('Yasemin Yılmaz', $user->name);
        $this->assertEquals('+905321234567', $user->phone);

        // Audit log created
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'updated',
            'message' => 'Yasemin Yılmaz, hesap bilgilerini güncelledi.',
        ]);
    }

    public function test_email_role_and_is_active_cannot_be_modified_via_profile(): void
    {
        $editorRole = Role::where('slug', 'editor')->first();
        $superAdminRole = Role::where('slug', 'super-admin')->first();

        $user = User::factory()->create([
            'name' => 'Yasemin Çelik',
            'email' => 'yasemin@dosttv.com',
            'role' => 'editor',
            'role_id' => $editorRole?->id,
            'is_active' => true,
        ]);

        $this->actingAs($user);

        // Attempting to pass manipulated data in Livewire
        Livewire::test(MyProfilePage::class)
            ->set('accountData.name', 'Yasemin Çelik')
            ->set('accountData.email', 'hacker@dosttv.com')
            ->set('accountData.role', 'super_admin')
            ->set('accountData.role_id', $superAdminRole?->id)
            ->set('accountData.is_active', false)
            ->call('updateProfile')
            ->assertHasNoErrors();

        $user->refresh();
        // Critical security asserts: values remain unchanged
        $this->assertEquals('yasemin@dosttv.com', $user->email);
        $this->assertEquals('editor', $user->role);
        $this->assertEquals($editorRole?->id, $user->role_id);
        $this->assertTrue($user->is_active);
    }

    public function test_password_update_fails_when_current_password_incorrect(): void
    {
        $user = User::factory()->create([
            'password' => 'old_secret_123',
        ]);

        $this->actingAs($user);

        Livewire::test(MyProfilePage::class)
            ->set('passwordData.current_password', 'wrong_password')
            ->set('passwordData.new_password', 'new_secret_123')
            ->set('passwordData.new_password_confirmation', 'new_secret_123')
            ->call('updatePassword')
            ->assertHasErrors(['passwordData.current_password']);

        $user->refresh();
        $this->assertTrue(Hash::check('old_secret_123', $user->password));
    }

    public function test_password_update_requires_minimum_5_chars(): void
    {
        $user = User::factory()->create([
            'password' => 'old_secret_123',
        ]);

        $this->actingAs($user);

        Livewire::test(MyProfilePage::class)
            ->set('passwordData.current_password', 'old_secret_123')
            ->set('passwordData.new_password', '1234')
            ->set('passwordData.new_password_confirmation', '1234')
            ->call('updatePassword')
            ->assertHasErrors(['passwordData.new_password']);
    }

    public function test_password_successfully_updated_and_logs_audit_without_sensitive_data(): void
    {
        $user = User::factory()->create([
            'name' => 'Yasemin Çelik',
            'password' => 'old_secret_123',
        ]);

        $this->actingAs($user);

        Livewire::test(MyProfilePage::class)
            ->set('passwordData.current_password', 'old_secret_123')
            ->set('passwordData.new_password', 'brand_new_pass_456')
            ->set('passwordData.new_password_confirmation', 'brand_new_pass_456')
            ->call('updatePassword')
            ->assertHasNoErrors();

        $user->refresh();
        $this->assertTrue(Hash::check('brand_new_pass_456', $user->password));

        // Audit log created without password details
        $audit = AuditLog::where('user_id', $user->id)
            ->where('action', 'updated')
            ->where('message', 'Yasemin Çelik, şifresini değiştirdi.')
            ->first();

        $this->assertNotNull($audit);
        $this->assertNull($audit->metadata);
        $this->assertStringNotContainsString('brand_new_pass_456', json_encode($audit->toArray()));
        $this->assertStringNotContainsString('old_secret_123', json_encode($audit->toArray()));
    }

    public function test_benim_islemlerim_strictly_scopes_to_authenticated_user(): void
    {
        $user1 = User::factory()->create(['name' => 'Yasemin Çelik', 'role' => 'editor']);
        $user2 = User::factory()->create(['name' => 'Mehmet Kaya', 'role' => 'administrator']);

        AuditLogger::log(
            action: 'created',
            message: "Yasemin Çelik, Akla Kapı 1. Bölüm'ü ekledi.",
            user: $user1,
            subjectLabel: 'Akla Kapı 1. Bölüm'
        );

        AuditLogger::log(
            action: 'created',
            message: "Mehmet Kaya, 2026 Yaz Yayın Akışı dönemini oluşturdu.",
            user: $user2,
            subjectLabel: '2026 Yaz Yayın Akışı'
        );

        $this->actingAs($user1);

        $component = Livewire::test(MyProfilePage::class)
            ->set('activeTab', 'audit_logs');

        // Can see own log
        $component->assertSee("Yasemin Çelik, Akla Kapı 1. Bölüm'ü ekledi.");
        // Cannot see other user's log
        $component->assertDontSee("Mehmet Kaya, 2026 Yaz Yayın Akışı dönemini oluşturdu.");
    }

    public function test_editor_can_access_my_profile_and_my_logs_but_not_global_audit_logs(): void
    {
        $editor = User::factory()->create(['name' => 'Yasemin Çelik', 'role' => 'editor']);
        $this->actingAs($editor);

        // 1. Can access MyProfilePage (Hesabım / Benim İşlemlerim)
        $this->get(MyProfilePage::getUrl())->assertSuccessful();

        // 2. Cannot access global Audit Logs resource
        $this->get('/admin/audit-logs')->assertForbidden();
    }

    public function test_super_admin_and_administrator_can_access_both(): void
    {
        $admin = User::factory()->create(['name' => 'Admin', 'role' => 'administrator']);
        $this->actingAs($admin);

        $this->get(MyProfilePage::getUrl())->assertSuccessful();
        $this->get('/admin/audit-logs')->assertSuccessful();

        $superAdmin = User::factory()->create(['name' => 'Super Admin', 'role' => 'super_admin']);
        $this->actingAs($superAdmin);

        $this->get(MyProfilePage::getUrl())->assertSuccessful();
        $this->get('/admin/audit-logs')->assertSuccessful();
    }

    public function test_historical_audit_log_user_name_snapshot_is_preserved_when_name_changes(): void
    {
        $user = User::factory()->create(['name' => 'Yasemin Çelik', 'role' => 'editor']);
        $this->actingAs($user);

        AuditLogger::log(
            action: 'created',
            message: "Yasemin Çelik, Akla Kapı 1. Bölüm'ü ekledi.",
            user: $user,
            subjectLabel: 'Akla Kapı 1. Bölüm'
        );

        $historicalLog = AuditLog::where('user_id', $user->id)->first();
        $this->assertEquals('Yasemin Çelik', $historicalLog->user_name_snapshot);

        // Update user name
        Livewire::test(MyProfilePage::class)
            ->set('accountData.name', 'Yasemin Yılmaz')
            ->call('updateProfile')
            ->assertHasNoErrors();

        // Historical log's user_name_snapshot must remain untouched
        $historicalLog->refresh();
        $this->assertEquals('Yasemin Çelik', $historicalLog->user_name_snapshot);

        // New audit log has the new name
        $newLog = AuditLog::where('user_id', $user->id)->latest('id')->first();
        $this->assertEquals('Yasemin Yılmaz', $newLog->user_name_snapshot);
    }
}
