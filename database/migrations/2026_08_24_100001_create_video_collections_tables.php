<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_collections', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('episode_video_collection', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_collection_id')->constrained('video_collections')->cascadeOnDelete();
            $table->foreignId('episode_id')->constrained('episodes')->cascadeOnDelete();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['video_collection_id', 'episode_id'], 'vcoll_ep_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('episode_video_collection');
        Schema::dropIfExists('video_collections');
    }
};
