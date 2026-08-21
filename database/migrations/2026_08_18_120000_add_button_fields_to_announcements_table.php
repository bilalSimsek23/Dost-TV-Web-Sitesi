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
        Schema::table('announcements', function (Blueprint $table) {
            if (! Schema::hasColumn('announcements', 'button_text')) {
                $table->string('button_text')->nullable()->after('image');
            }

            if (! Schema::hasColumn('announcements', 'button_url')) {
                $table->string('button_url')->nullable()->after('button_text');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('announcements', 'button_text')) {
                $columns[] = 'button_text';
            }
            if (Schema::hasColumn('announcements', 'button_url')) {
                $columns[] = 'button_url';
            }

            if (! empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
