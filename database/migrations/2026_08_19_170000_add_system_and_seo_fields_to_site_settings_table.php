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
            if (! Schema::hasColumn('site_settings', 'title_suffix')) {
                $table->string('title_suffix')->nullable()->default('| DOST TV')->after('site_name');
            }
            if (! Schema::hasColumn('site_settings', 'system_email')) {
                $table->string('system_email')->nullable()->after('email');
            }
            if (! Schema::hasColumn('site_settings', 'default_meta_description')) {
                $table->text('default_meta_description')->nullable()->after('logo_alt_text');
            }
            if (! Schema::hasColumn('site_settings', 'default_og_image')) {
                $table->string('default_og_image')->nullable()->after('default_meta_description');
            }
            if (! Schema::hasColumn('site_settings', 'search_engine_indexing')) {
                $table->boolean('search_engine_indexing')->default(true)->after('default_og_image');
            }
            if (! Schema::hasColumn('site_settings', 'canonical_url_mode')) {
                $table->string('canonical_url_mode')->default('current_url')->after('search_engine_indexing');
            }
            if (! Schema::hasColumn('site_settings', 'google_analytics_id')) {
                $table->string('google_analytics_id')->nullable()->after('homepage_sections');
            }
            if (! Schema::hasColumn('site_settings', 'google_tag_manager_id')) {
                $table->string('google_tag_manager_id')->nullable()->after('google_analytics_id');
            }
            if (! Schema::hasColumn('site_settings', 'google_site_verification')) {
                $table->string('google_site_verification')->nullable()->after('google_tag_manager_id');
            }
            if (! Schema::hasColumn('site_settings', 'custom_head_code')) {
                $table->text('custom_head_code')->nullable()->after('google_site_verification');
            }
            if (! Schema::hasColumn('site_settings', 'custom_body_code')) {
                $table->text('custom_body_code')->nullable()->after('custom_head_code');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $columnsToDrop = [
                'title_suffix',
                'system_email',
                'default_meta_description',
                'default_og_image',
                'search_engine_indexing',
                'canonical_url_mode',
                'google_analytics_id',
                'google_tag_manager_id',
                'google_site_verification',
                'custom_head_code',
                'custom_body_code',
            ];

            $existing = array_filter($columnsToDrop, fn ($col) => Schema::hasColumn('site_settings', $col));
            if (! empty($existing)) {
                $table->dropColumn($existing);
            }
        });
    }
};
