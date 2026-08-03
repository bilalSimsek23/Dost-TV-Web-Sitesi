<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            if (! Schema::hasColumn('schedules', 'is_live')) {
                $table->boolean('is_live')->default(false)->after('note');
            }
            if (! Schema::hasColumn('schedules', 'is_repeat')) {
                $table->boolean('is_repeat')->default(false)->after('is_live');
            }
            if (! Schema::hasColumn('schedules', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('is_repeat');
            }
            if (! Schema::hasColumn('schedules', 'sort_order')) {
                $table->integer('sort_order')->default(1)->after('is_active');
            }
            if (! Schema::hasColumn('schedules', 'custom_title')) {
                $table->string('custom_title')->nullable()->after('sort_order');
            }
        });
    }

    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn(['is_live', 'is_repeat', 'is_active', 'sort_order', 'custom_title']);
        });
    }
};
