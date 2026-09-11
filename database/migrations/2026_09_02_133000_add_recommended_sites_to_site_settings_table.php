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
        if (! Schema::hasColumn('site_settings', 'recommended_sites')) {
            Schema::table('site_settings', function (Blueprint $table) {
                $table->json('recommended_sites')->nullable()->after('copyright_text');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('site_settings', 'recommended_sites')) {
            Schema::table('site_settings', function (Blueprint $table) {
                $table->dropColumn('recommended_sites');
            });
        }
    }
};
