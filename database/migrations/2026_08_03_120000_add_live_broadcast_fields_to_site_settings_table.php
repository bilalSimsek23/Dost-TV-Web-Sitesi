<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('site_settings', 'live_tv_title')) {
                $table->string('live_tv_title')->nullable()->after('live_tv_url');
            }
            if (! Schema::hasColumn('site_settings', 'live_tv_description')) {
                $table->text('live_tv_description')->nullable()->after('live_tv_title');
            }
            if (! Schema::hasColumn('site_settings', 'live_tv_backup_url')) {
                $table->string('live_tv_backup_url')->nullable()->after('live_tv_description');
            }
            if (! Schema::hasColumn('site_settings', 'live_tv_poster')) {
                $table->string('live_tv_poster')->nullable()->after('live_tv_backup_url');
            }
            if (! Schema::hasColumn('site_settings', 'live_tv_maintenance_message')) {
                $table->text('live_tv_maintenance_message')->nullable()->after('live_tv_poster');
            }
            if (! Schema::hasColumn('site_settings', 'live_tv_error_message')) {
                $table->text('live_tv_error_message')->nullable()->after('live_tv_maintenance_message');
            }
            if (! Schema::hasColumn('site_settings', 'live_tv_is_active')) {
                $table->boolean('live_tv_is_active')->default(true)->after('live_tv_error_message');
            }
            if (! Schema::hasColumn('site_settings', 'live_tv_is_public')) {
                $table->boolean('live_tv_is_public')->default(true)->after('live_tv_is_active');
            }

            if (! Schema::hasColumn('site_settings', 'radio_description')) {
                $table->text('radio_description')->nullable()->after('radio_name');
            }
            if (! Schema::hasColumn('site_settings', 'radio_backup_url')) {
                $table->string('radio_backup_url')->nullable()->after('radio_description');
            }
            if (! Schema::hasColumn('site_settings', 'radio_image')) {
                $table->string('radio_image')->nullable()->after('radio_backup_url');
            }
            if (! Schema::hasColumn('site_settings', 'radio_maintenance_message')) {
                $table->text('radio_maintenance_message')->nullable()->after('radio_image');
            }
            if (! Schema::hasColumn('site_settings', 'radio_error_message')) {
                $table->text('radio_error_message')->nullable()->after('radio_maintenance_message');
            }
            if (! Schema::hasColumn('site_settings', 'radio_is_active')) {
                $table->boolean('radio_is_active')->default(true)->after('radio_error_message');
            }
            if (! Schema::hasColumn('site_settings', 'radio_is_public')) {
                $table->boolean('radio_is_public')->default(true)->after('radio_is_active');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'live_tv_title',
                'live_tv_description',
                'live_tv_backup_url',
                'live_tv_poster',
                'live_tv_maintenance_message',
                'live_tv_error_message',
                'live_tv_is_active',
                'live_tv_is_public',
                'radio_description',
                'radio_backup_url',
                'radio_image',
                'radio_maintenance_message',
                'radio_error_message',
                'radio_is_active',
                'radio_is_public',
            ]);
        });
    }
};
