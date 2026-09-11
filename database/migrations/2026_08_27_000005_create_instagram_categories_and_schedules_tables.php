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
        // 1. Instagram Reel Categories Table
        Schema::create('instagram_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // 2. Instagram Category <-> Video Pivot Table
        Schema::create('instagram_category_video', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instagram_category_id')->constrained('instagram_categories')->cascadeOnDelete();
            $table->foreignId('instagram_video_id')->constrained('instagram_videos')->cascadeOnDelete();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['instagram_category_id', 'instagram_video_id'], 'ig_cat_vid_unique');
        });

        // 3. Weekly Schedule & Sort Settings Table
        Schema::create('instagram_reels_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monday_category_id')->nullable()->constrained('instagram_categories')->nullOnDelete();
            $table->foreignId('tuesday_category_id')->nullable()->constrained('instagram_categories')->nullOnDelete();
            $table->foreignId('wednesday_category_id')->nullable()->constrained('instagram_categories')->nullOnDelete();
            $table->foreignId('thursday_category_id')->nullable()->constrained('instagram_categories')->nullOnDelete();
            $table->foreignId('friday_category_id')->nullable()->constrained('instagram_categories')->nullOnDelete();
            $table->foreignId('saturday_category_id')->nullable()->constrained('instagram_categories')->nullOnDelete();
            $table->foreignId('sunday_category_id')->nullable()->constrained('instagram_categories')->nullOnDelete();
            $table->string('sort_mode', 30)->default('latest');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('instagram_reels_schedules');
        Schema::dropIfExists('instagram_category_video');
        Schema::dropIfExists('instagram_categories');
    }
};
