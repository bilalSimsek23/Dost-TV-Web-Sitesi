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
        Schema::table('instagram_videos', function (Blueprint $table) {
            if (! Schema::hasColumn('instagram_videos', 'media_url')) {
                $table->text('media_url')->nullable()->after('thumbnail_url');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('instagram_videos', function (Blueprint $table) {
            if (Schema::hasColumn('instagram_videos', 'media_url')) {
                $table->dropColumn('media_url');
            }
        });
    }
};
