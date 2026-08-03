<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('khatms', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->integer('total_juz')->default(30);
            $table->string('status')->default('active'); // active, completed, cancelled
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('juz_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('khatm_id')->constrained('khatms')->onDelete('cascade');
            $table->integer('juz_number');
            $table->string('status')->default('empty'); // empty, assigned, completed
            $table->string('claimed_by_name')->nullable();
            $table->string('claimed_by_phone')->nullable();
            $table->string('claimed_by_email')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('juz_claims');
        Schema::dropIfExists('khatms');
    }
};
