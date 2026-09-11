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
            if (! Schema::hasColumn('instagram_videos', 'speaker_name')) {
                $table->string('speaker_name', 255)->nullable()->after('username');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('instagram_videos', function (Blueprint $table) {
            if (Schema::hasColumn('instagram_videos', 'speaker_name')) {
                $table->dropColumn('speaker_name');
            }
        });
    }
};
