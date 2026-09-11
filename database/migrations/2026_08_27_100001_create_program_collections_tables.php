<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_collections', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('source_type')->default('manual'); // manual, category, active_period_schedule, featured, hybrid
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->boolean('is_active')->default(true)->index();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('program_collection_program', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_collection_id')->constrained('program_collections')->cascadeOnDelete();
            $table->foreignId('program_id')->constrained('programs')->cascadeOnDelete();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_pinned')->default(false);
            $table->timestamps();

            $table->unique(['program_collection_id', 'program_id'], 'pcoll_prog_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_collection_program');
        Schema::dropIfExists('program_collections');
    }
};
