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
        Schema::table('pages', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->after('id')->constrained('pages')->nullOnDelete();
            $table->string('page_type')->nullable()->after('slug');
            $table->string('template')->default('default')->after('page_type');
            $table->string('status')->default('published')->after('template');
            $table->timestamp('published_at')->nullable()->after('status');
            $table->boolean('show_in_header')->default(false)->after('show_in_menu');
            $table->boolean('show_in_footer')->default(false)->after('show_in_header');
            $table->string('menu_group')->nullable()->after('show_in_footer');
            $table->string('menu_location')->nullable()->after('menu_group');
            $table->string('seo_title')->nullable()->after('sort_order');
            $table->string('seo_description')->nullable()->after('seo_title');
            $table->string('og_image')->nullable()->after('seo_description');
            $table->text('custom_css')->nullable()->after('og_image');
            $table->json('settings')->nullable()->after('custom_css');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_id');
            $table->dropColumn([
                'page_type',
                'template',
                'status',
                'published_at',
                'show_in_header',
                'show_in_footer',
                'menu_group',
                'menu_location',
                'seo_title',
                'seo_description',
                'og_image',
                'custom_css',
                'settings',
            ]);
        });
    }
};
