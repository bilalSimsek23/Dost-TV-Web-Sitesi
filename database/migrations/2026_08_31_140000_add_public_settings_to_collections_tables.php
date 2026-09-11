<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('program_collections', function (Blueprint $table) {
            $table->json('public_settings')->nullable()->after('sort_order');
        });

        Schema::table('video_collections', function (Blueprint $table) {
            $table->json('public_settings')->nullable()->after('sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('program_collections', function (Blueprint $table) {
            $table->dropColumn('public_settings');
        });

        Schema::table('video_collections', function (Blueprint $table) {
            $table->dropColumn('public_settings');
        });
    }
};
