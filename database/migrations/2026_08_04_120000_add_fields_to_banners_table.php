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
        Schema::table('banners', function (Blueprint $table) {
            $table->string('content_type')->default('hero')->nullable()->after('subtitle');
            $table->text('description')->nullable()->after('content_type');
            $table->string('mobile_image')->nullable()->after('image');
            $table->string('alt_text')->nullable()->after('mobile_image');
            $table->string('button_text')->nullable()->after('link_url');
            $table->boolean('open_in_new_tab')->default(false)->after('button_text');
            $table->timestamp('starts_at')->nullable()->after('is_active');
            $table->timestamp('ends_at')->nullable()->after('starts_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->dropColumn([
                'content_type',
                'description',
                'mobile_image',
                'alt_text',
                'button_text',
                'open_in_new_tab',
                'starts_at',
                'ends_at',
            ]);
        });
    }
};
