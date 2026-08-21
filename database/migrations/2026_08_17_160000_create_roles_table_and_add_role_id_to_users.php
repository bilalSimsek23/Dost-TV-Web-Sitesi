<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('base_role'); // super_admin, administrator, editor
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        // Seed the 3 Immutable System Roles
        $now = now();
        $systemRoles = [
            [
                'name' => 'Süper Admin',
                'slug' => 'super-admin',
                'base_role' => 'super_admin',
                'description' => 'Tüm sistem ve kullanıcı yönetimi yetkisine sahip çekirdek yönetici.',
                'is_active' => true,
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Yönetici',
                'slug' => 'yonetici',
                'base_role' => 'administrator',
                'description' => 'İçerik, yayın akışı ve editör kullanıcı yönetimi yetkisine sahip yönetici.',
                'is_active' => true,
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Editör',
                'slug' => 'editor',
                'base_role' => 'editor',
                'description' => 'Program, bölüm ve yayın akışı operasyonlarını yürüten içerik editörü.',
                'is_active' => true,
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('roles')->insert($systemRoles);

        $superAdminRoleId = DB::table('roles')->where('slug', 'super-admin')->value('id');
        $adminRoleId = DB::table('roles')->where('slug', 'yonetici')->value('id');
        $editorRoleId = DB::table('roles')->where('slug', 'editor')->value('id');

        // Add role_id to users table (keeping existing `role` string column for backward compatibility)
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('role')->constrained('roles')->nullOnDelete();
        });

        // Map existing users to corresponding role_id
        if ($superAdminRoleId) {
            DB::table('users')->where('role', 'super_admin')->update(['role_id' => $superAdminRoleId]);
        }

        if ($adminRoleId) {
            DB::table('users')->where('role', 'administrator')->update(['role_id' => $adminRoleId]);
        }

        if ($editorRoleId) {
            DB::table('users')->whereNotIn('role', ['super_admin', 'administrator'])->update(['role_id' => $editorRoleId]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn('role_id');
        });

        Schema::dropIfExists('roles');
    }
};
