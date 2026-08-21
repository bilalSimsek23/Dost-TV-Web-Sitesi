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

        // This corrective migration applies to MySQL/MariaDB to achieve
        // 100% NULL-safe composite unique parity with SQLite's partial unique indexes.
        if ($driver !== 'sqlite') {
            // 1. Drop previous non-NULL-safe unique index
            Schema::table('program_seasons', function (Blueprint $table) {
                $table->dropUnique('prog_season_unique');
            });

            // 2. Add virtual generated column converting NULL to empty string ''
            DB::statement("ALTER TABLE program_seasons ADD COLUMN season_year_key VARCHAR(20) GENERATED ALWAYS AS (COALESCE(season_year, '')) VIRTUAL AFTER season_year");

            // 3. Create composite unique index on (program_id, season_number, season_year_key)
            Schema::table('program_seasons', function (Blueprint $table) {
                $table->unique(['program_id', 'season_number', 'season_year_key'], 'prog_season_null_safe_unique');
            });
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver !== 'sqlite') {
            Schema::table('program_seasons', function (Blueprint $table) {
                $table->dropUnique('prog_season_null_safe_unique');
            });

            DB::statement('ALTER TABLE program_seasons DROP COLUMN season_year_key');

            Schema::table('program_seasons', function (Blueprint $table) {
                $table->unique(['program_id', 'season_number', 'season_year'], 'prog_season_unique');
            });
        }
    }
};
