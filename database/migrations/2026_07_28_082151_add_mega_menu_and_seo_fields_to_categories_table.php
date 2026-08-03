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
            $table->boolean('show_in_mega_menu')->default(true)->after('show_in_menu');
            $table->string('hover_color')->nullable()->after('text_color');
            $table->string('card_variant')->nullable()->after('hover_color');
            $table->string('og_image')->nullable()->after('seo_description');
            $table->string('canonical_url')->nullable()->after('og_image');
            $table->string('index_policy')->default('index')->after('canonical_url');
            $table->string('follow_policy')->default('follow')->after('index_policy');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn([
                'show_in_mega_menu',
                'hover_color',
                'card_variant',
                'og_image',
                'canonical_url',
                'index_policy',
                'follow_policy',
            ]);
        });
    }
};
