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
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('site_name')->default('Dost TV');
            $table->string('logo')->nullable();
            $table->enum('live_tv_type', ['iframe', 'hls'])->default('iframe');
            $table->string('live_tv_url')->nullable();
            $table->string('radio_stream_url')->nullable();
            $table->string('radio_name')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
