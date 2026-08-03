<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            if (! Schema::hasColumn('programs', 'status')) {
                $table->string('status')->default('active')->after('is_active');
            }
            if (! Schema::hasColumn('programs', 'short_description')) {
                $table->text('short_description')->nullable()->after('description');
            }
            if (! Schema::hasColumn('programs', 'horizontal_image')) {
                $table->string('horizontal_image')->nullable()->after('cover_image');
            }
            if (! Schema::hasColumn('programs', 'program_logo')) {
                $table->string('program_logo')->nullable()->after('horizontal_image');
            }
            if (! Schema::hasColumn('programs', 'default_episode_image')) {
                $table->string('default_episode_image')->nullable()->after('program_logo');
            }
            if (! Schema::hasColumn('programs', 'is_featured')) {
                $table->boolean('is_featured')->default(false)->after('sort_order');
            }
            if (! Schema::hasColumn('programs', 'show_on_public')) {
                $table->boolean('show_on_public')->default(true)->after('is_featured');
            }
            if (! Schema::hasColumn('programs', 'meta_title')) {
                $table->string('meta_title')->nullable()->after('show_on_public');
            }
            if (! Schema::hasColumn('programs', 'meta_description')) {
                $table->text('meta_description')->nullable()->after('meta_title');
            }
        });

        // Migrate existing status & show_on_public data safely
        DB::table('programs')->where('is_active', true)->update([
            'status' => 'active',
            'show_on_public' => true,
        ]);
        DB::table('programs')->where('is_active', false)->update([
            'status' => 'completed',
            'show_on_public' => false,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->dropColumn([
                'status',
                'short_description',
                'horizontal_image',
                'program_logo',
                'default_episode_image',
                'is_featured',
                'show_on_public',
                'meta_title',
                'meta_description',
            ]);
        });
    }
};
