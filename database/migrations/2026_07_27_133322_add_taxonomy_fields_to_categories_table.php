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
        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->after('id')->constrained('categories')->nullOnDelete();
            $table->text('description')->nullable()->after('slug');
            $table->string('image')->nullable()->after('description');
            $table->string('icon')->nullable()->after('image');
            $table->string('color')->nullable()->after('icon');
            $table->string('background_color')->nullable()->after('color');
            $table->string('text_color')->nullable()->after('background_color');
            $table->boolean('is_active')->default(true)->after('text_color');
            $table->boolean('is_featured')->default(false)->after('is_active');
            $table->boolean('show_in_menu')->default(false)->after('is_featured');
            $table->unsignedInteger('sort_order')->default(0)->after('show_in_menu');
            $table->string('seo_title')->nullable()->after('sort_order');
            $table->string('seo_description')->nullable()->after('seo_title');
            $table->json('metadata')->nullable()->after('seo_description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_id');
            $table->dropColumn([
                'description',
                'image',
                'icon',
                'color',
                'background_color',
                'text_color',
                'is_active',
                'is_featured',
                'show_in_menu',
                'sort_order',
                'seo_title',
                'seo_description',
                'metadata',
            ]);
        });
    }
};
