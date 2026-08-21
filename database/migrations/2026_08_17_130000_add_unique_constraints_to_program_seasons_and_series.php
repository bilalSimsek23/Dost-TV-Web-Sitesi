<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        // 1. Program Series Unique Constraints: (program_id, name) and (program_id, slug)
        Schema::table('program_series', function (Blueprint $table) {
            $table->unique(['program_id', 'name'], 'prog_series_name_unique');
            $table->unique(['program_id', 'slug'], 'prog_series_slug_unique');
        });

        // 2. Program Seasons Unique Constraints (NULL-Safe)
        if ($driver === 'sqlite') {
            // In SQLite, partial unique indexes provide strict NULL-safe uniqueness
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS prog_season_with_year_unique ON program_seasons (program_id, season_number, season_year) WHERE season_year IS NOT NULL');
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS prog_season_null_year_unique ON program_seasons (program_id, season_number) WHERE season_year IS NULL');
        } else {
            // In MySQL/MariaDB/PostgreSQL
            Schema::table('program_seasons', function (Blueprint $table) {
                $table->unique(['program_id', 'season_number', 'season_year'], 'prog_season_unique');
            });
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        Schema::table('program_series', function (Blueprint $table) {
            $table->dropUnique('prog_series_name_unique');
            $table->dropUnique('prog_series_slug_unique');
        });

        if ($driver === 'sqlite') {
            DB::statement('DROP INDEX IF EXISTS prog_season_with_year_unique');
            DB::statement('DROP INDEX IF EXISTS prog_season_null_year_unique');
        } else {
            Schema::table('program_seasons', function (Blueprint $table) {
                $table->dropUnique('prog_season_unique');
            });
        }
    }
};
