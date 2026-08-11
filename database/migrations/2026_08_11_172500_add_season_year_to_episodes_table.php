<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('episodes', function (Blueprint $table) {
            if (! Schema::hasColumn('episodes', 'season_year')) {
                $table->unsignedSmallInteger('season_year')->nullable()->after('season_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('episodes', function (Blueprint $table) {
            if (Schema::hasColumn('episodes', 'season_year')) {
                $table->dropColumn('season_year');
            }
        });
    }
};
