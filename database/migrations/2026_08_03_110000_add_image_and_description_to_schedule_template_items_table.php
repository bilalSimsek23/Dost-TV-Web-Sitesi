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
        Schema::table('schedule_template_items', function (Blueprint $table) {
            if (! Schema::hasColumn('schedule_template_items', 'image')) {
                $table->string('image')->nullable()->after('note');
            }
            if (! Schema::hasColumn('schedule_template_items', 'description')) {
                $table->text('description')->nullable()->after('image');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedule_template_items', function (Blueprint $table) {
            $table->dropColumn(['image', 'description']);
        });
    }
};
