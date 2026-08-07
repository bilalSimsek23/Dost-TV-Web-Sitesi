<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->timestamp('last_youtube_sync_at')->nullable()->after('youtube_playlist_url');
        });

        Schema::create('youtube_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->nullable()->constrained('programs')->onDelete('set null');
            $table->string('playlist_url')->nullable();
            $table->string('status')->default('success');
            $table->integer('checked_videos')->default(0);
            $table->integer('new_videos')->default(0);
            $table->integer('created_episodes')->default(0);
            $table->integer('skipped_videos')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('youtube_sync_logs');

        Schema::table('programs', function (Blueprint $table) {
            $table->dropColumn('last_youtube_sync_at');
        });
    }
};
