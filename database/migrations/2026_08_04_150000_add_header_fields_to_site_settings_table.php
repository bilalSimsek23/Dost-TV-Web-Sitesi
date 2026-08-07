<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('favicon')->nullable()->after('logo');
            $table->string('logo_alt_text')->nullable()->after('favicon');
            $table->string('live_button_text')->default('Canlı İzle')->nullable()->after('logo_alt_text');
            $table->boolean('live_button_is_visible')->default(true)->after('live_button_text');
            $table->boolean('search_is_visible')->default(true)->after('live_button_is_visible');
            $table->boolean('header_is_sticky')->default(true)->after('search_is_visible');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'favicon',
                'logo_alt_text',
                'live_button_text',
                'live_button_is_visible',
                'search_is_visible',
                'header_is_sticky',
            ]);
        });
    }
};
