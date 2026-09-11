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
        Schema::create('instagram_videos', function (Blueprint $table) {
            $table->id();
            $table->string('instagram_media_id', 100)->nullable()->unique();
            $table->string('shortcode', 50)->nullable()->index();
            $table->string('username', 100)->nullable();
            $table->string('permalink', 255)->unique();
            $table->text('caption')->nullable();
            $table->text('thumbnail_url')->nullable();
            $table->string('media_type', 20)->default('reel');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('instagram_videos');
    }
};
