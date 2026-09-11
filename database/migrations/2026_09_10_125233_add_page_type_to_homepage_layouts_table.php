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
        Schema::table('homepage_layouts', function (Blueprint $table) {
            $table->string('page_type')->default('home')->after('name')->index();
            $table->unsignedBigInteger('target_id')->nullable()->after('page_type')->index();
        });
    }

    public function down(): void
    {
        Schema::table('homepage_layouts', function (Blueprint $table) {
            $table->dropColumn(['page_type', 'target_id']);
        });
    }
};
