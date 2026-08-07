<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('pages')
            ->whereIn('id', [1, 2, 14, 15, 16, 17, 27])
            ->update(['page_type' => 'corporate']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('pages')
            ->whereIn('id', [1, 2, 14, 15, 16, 17, 27])
            ->update(['page_type' => null]);
    }
};
