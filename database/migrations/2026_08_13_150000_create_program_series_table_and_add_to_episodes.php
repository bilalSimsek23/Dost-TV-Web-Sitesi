<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_series', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained('programs')->cascadeOnDelete();
            $table->foreignId('program_season_id')->nullable()->constrained('program_seasons')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->string('youtube_playlist_url', 500)->nullable();
            $table->string('youtube_playlist_title')->nullable();
            $table->timestamp('last_youtube_sync_at')->nullable();
            $table->integer('sort_order')->default(0)->nullable();
            $table->timestamps();

            $table->index(['program_id', 'program_season_id', 'name'], 'prog_series_lookup_idx');
            $table->index(['program_id', 'sort_order'], 'prog_series_sort_idx');
        });

        Schema::table('episodes', function (Blueprint $table) {
            $table->foreignId('program_series_id')->nullable()->after('season_year')->constrained('program_series')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('episodes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('program_series_id');
        });

        Schema::dropIfExists('program_series');
    }
};
