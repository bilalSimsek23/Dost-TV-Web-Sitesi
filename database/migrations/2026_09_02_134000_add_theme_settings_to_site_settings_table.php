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
        if (! Schema::hasColumn('site_settings', 'theme_settings')) {
            Schema::table('site_settings', function (Blueprint $table) {
                $table->json('theme_settings')->nullable()->after('header_responsive_settings');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('site_settings', 'theme_settings')) {
            Schema::table('site_settings', function (Blueprint $table) {
                $table->dropColumn('theme_settings');
            });
        }
    }
};
