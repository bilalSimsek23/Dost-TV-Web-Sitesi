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
        Schema::table('schedule_template_items', function (Blueprint $table) {
            if (! Schema::hasColumn('schedule_template_items', 'link_type')) {
                $table->string('link_type')->default('program')->after('note');
            }
            if (! Schema::hasColumn('schedule_template_items', 'episode_id')) {
                $table->foreignId('episode_id')->nullable()->after('link_type')->constrained('episodes')->nullOnDelete();
            }
            if (! Schema::hasColumn('schedule_template_items', 'external_url')) {
                $table->string('external_url')->nullable()->after('episode_id');
            }
            if (! Schema::hasColumn('schedule_template_items', 'stream_url')) {
                $table->string('stream_url')->nullable()->after('external_url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('schedule_template_items', function (Blueprint $table) {
            $table->dropForeign(['episode_id']);
            $table->dropColumn(['link_type', 'episode_id', 'external_url', 'stream_url']);
        });
    }
};
