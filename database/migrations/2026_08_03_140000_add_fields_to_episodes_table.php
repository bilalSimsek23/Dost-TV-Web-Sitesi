<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('episodes', function (Blueprint $table) {
            if (! Schema::hasColumn('episodes', 'episode_number')) {
                $table->integer('episode_number')->nullable()->after('program_id');
            }
            if (! Schema::hasColumn('episodes', 'season_number')) {
                $table->integer('season_number')->nullable()->after('episode_number');
            }
            if (! Schema::hasColumn('episodes', 'slug')) {
                $table->string('slug')->nullable()->unique()->after('title');
            }
            if (! Schema::hasColumn('episodes', 'status')) {
                $table->string('status')->default('published')->after('video_path');
            }
            if (! Schema::hasColumn('episodes', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('status');
            }
            if (! Schema::hasColumn('episodes', 'show_on_public')) {
                $table->boolean('show_on_public')->default(true)->after('is_active');
            }
            if (! Schema::hasColumn('episodes', 'duration')) {
                $table->string('duration')->nullable()->after('show_on_public');
            }
            if (! Schema::hasColumn('episodes', 'horizontal_image')) {
                $table->string('horizontal_image')->nullable()->after('thumbnail');
            }
            if (! Schema::hasColumn('episodes', 'social_image')) {
                $table->string('social_image')->nullable()->after('horizontal_image');
            }
            if (! Schema::hasColumn('episodes', 'meta_title')) {
                $table->string('meta_title')->nullable()->after('duration');
            }
            if (! Schema::hasColumn('episodes', 'meta_description')) {
                $table->text('meta_description')->nullable()->after('meta_title');
            }
        });

        // Backfill unique slugs and default status for existing episodes
        $episodes = DB::table('episodes')->get();
        $usedSlugs = [];

        foreach ($episodes as $episode) {
            $program = DB::table('programs')->where('id', $episode->program_id)->first();
            $progSlug = $program->slug ?? 'program';
            $epTitleSlug = Str::slug($episode->title ?: "bolum-{$episode->id}");
            $baseSlug = Str::slug("{$progSlug}-{$epTitleSlug}");

            if (blank($baseSlug)) {
                $baseSlug = "bolum-{$episode->id}";
            }

            $slug = $baseSlug;
            $counter = 1;

            while (in_array($slug, $usedSlugs, true)) {
                $counter++;
                $slug = "{$baseSlug}-{$counter}";
            }

            $usedSlugs[] = $slug;

            DB::table('episodes')->where('id', $episode->id)->update([
                'slug' => $slug,
                'status' => 'published',
                'is_active' => true,
                'show_on_public' => true,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('episodes', function (Blueprint $table) {
            $table->dropColumn([
                'episode_number',
                'season_number',
                'slug',
                'status',
                'is_active',
                'show_on_public',
                'duration',
                'horizontal_image',
                'social_image',
                'meta_title',
                'meta_description',
            ]);
        });
    }
};
