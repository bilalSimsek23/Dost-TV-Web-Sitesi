<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('video_collections', function (Blueprint $table) {
            $table->string('source_type')->default('manual')->after('description');
            $table->foreignId('category_id')->nullable()->after('source_type')->constrained('categories')->nullOnDelete();
            $table->string('sort_mode')->default('latest')->after('category_id');
            $table->string('fallback_source_type')->nullable()->after('sort_mode');
            $table->foreignId('fallback_category_id')->nullable()->after('fallback_source_type')->constrained('categories')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('video_collections', function (Blueprint $table) {
            $table->dropForeign(['fallback_category_id']);
            $table->dropForeign(['category_id']);
            $table->dropColumn([
                'source_type',
                'category_id',
                'sort_mode',
                'fallback_source_type',
                'fallback_category_id',
            ]);
        });
    }
};
