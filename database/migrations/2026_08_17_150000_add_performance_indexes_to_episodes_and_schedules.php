<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('episodes', function (Blueprint $table) {
            $table->index(
                ['program_id', 'is_active', 'show_on_public', 'season_number', 'episode_number'],
                'idx_episodes_program_public_season'
            );

            $table->index(
                ['program_series_id', 'is_active', 'show_on_public', 'episode_number'],
                'idx_episodes_series_public_order'
            );
        });

        Schema::table('schedule_template_items', function (Blueprint $table) {
            $table->index(
                ['schedule_template_id', 'day_of_week', 'is_active', 'start_time'],
                'idx_schedule_items_lookup'
            );
        });
    }

    public function down(): void
    {
        Schema::table('episodes', function (Blueprint $table) {
            $table->dropIndex('idx_episodes_program_public_season');
            $table->dropIndex('idx_episodes_series_public_order');
        });

        Schema::table('schedule_template_items', function (Blueprint $table) {
            $table->dropIndex('idx_schedule_items_lookup');
        });
    }
};
