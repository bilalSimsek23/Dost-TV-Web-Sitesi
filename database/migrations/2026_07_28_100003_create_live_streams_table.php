<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('live_streams', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('stream_type')->default('hls'); // hls, youtube, iframe, custom
            $table->string('stream_url')->nullable();
            $table->text('embed_code')->nullable();
            $table->string('backup_url')->nullable();
            $table->string('poster_image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_currently_live')->default(true);
            $table->boolean('is_primary')->default(false);
            $table->dateTime('start_time')->nullable();
            $table->dateTime('end_time')->nullable();
            $table->string('button_text')->default('Canlı İzle');
            $table->boolean('show_watch_button')->default(true);
            $table->boolean('open_in_new_tab')->default(false);
            $table->integer('sort_order')->default(1);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_streams');
    }
};
