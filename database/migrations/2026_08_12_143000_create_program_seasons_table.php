<?php

use App\Models\Episode;
use App\Models\Program;
use App\Models\ProgramSeason;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_seasons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained('programs')->cascadeOnDelete();
            $table->unsignedSmallInteger('season_number')->nullable();
            $table->string('season_year', 20)->nullable();
            $table->string('youtube_playlist_url', 500)->nullable();
            $table->string('youtube_playlist_title')->nullable();
            $table->timestamp('last_youtube_sync_at')->nullable();
            $table->timestamps();

            $table->index(['program_id', 'season_number', 'season_year'], 'prog_season_idx');
        });

        // Migrate existing program-level playlist URLs to program_seasons
        $programs = DB::table('programs')
            ->whereNotNull('youtube_playlist_url')
            ->where('youtube_playlist_url', '!=', '')
            ->get();

        foreach ($programs as $prog) {
            // Find distinct seasons for this program in episodes table
            $seasons = DB::table('episodes')
                ->where('program_id', $prog->id)
                ->whereNotNull('season_number')
                ->select('season_number', 'season_year')
                ->distinct()
                ->get();

            if ($seasons->count() === 1) {
                // Program has only one season -> assign playlist to that season
                $s = $seasons->first();
                DB::table('program_seasons')->insert([
                    'program_id' => $prog->id,
                    'season_number' => $s->season_number,
                    'season_year' => $s->season_year,
                    'youtube_playlist_url' => $prog->youtube_playlist_url,
                    'last_youtube_sync_at' => $prog->last_youtube_sync_at,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } elseif ($seasons->count() > 1) {
                // Program has multiple seasons (e.g. Hikmet Arayışları with S1 2017)
                // If the playlist URL matches Season 1 (or lowest season), assign it to Season 1
                $firstSeason = $seasons->sortBy('season_number')->first();
                DB::table('program_seasons')->insert([
                    'program_id' => $prog->id,
                    'season_number' => $firstSeason->season_number,
                    'season_year' => $firstSeason->season_year,
                    'youtube_playlist_url' => $prog->youtube_playlist_url,
                    'last_youtube_sync_at' => $prog->last_youtube_sync_at,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                // Seasonless program
                DB::table('program_seasons')->insert([
                    'program_id' => $prog->id,
                    'season_number' => 1,
                    'season_year' => null,
                    'youtube_playlist_url' => $prog->youtube_playlist_url,
                    'last_youtube_sync_at' => $prog->last_youtube_sync_at,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('program_seasons');
    }
};
