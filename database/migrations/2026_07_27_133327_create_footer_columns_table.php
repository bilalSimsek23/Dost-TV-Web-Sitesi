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
        Schema::create('footer_columns', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('column_type');
            $table->foreignId('menu_id')->nullable()->constrained('menus')->nullOnDelete();
            $table->json('content')->nullable();
            $table->string('width')->default('1');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('footer_columns');
    }
};
