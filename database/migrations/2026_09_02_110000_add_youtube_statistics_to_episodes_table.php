<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('episodes', function (Blueprint $table) {
            if (! Schema::hasColumn('episodes', 'view_count')) {
                $table->unsignedBigInteger('view_count')->nullable()->default(null)->after('duration');
                $table->index('view_count');
            }
            if (! Schema::hasColumn('episodes', 'like_count')) {
                $table->unsignedBigInteger('like_count')->nullable()->default(null)->after('view_count');
                $table->index('like_count');
            }
            if (! Schema::hasColumn('episodes', 'comment_count')) {
                $table->unsignedBigInteger('comment_count')->nullable()->default(null)->after('like_count');
                $table->index('comment_count');
            }
        });
    }

    public function down(): void
    {
        Schema::table('episodes', function (Blueprint $table) {
            $table->dropIndex(['view_count']);
            $table->dropIndex(['like_count']);
            $table->dropIndex(['comment_count']);
            $table->dropColumn(['view_count', 'like_count', 'comment_count']);
        });
    }
};
