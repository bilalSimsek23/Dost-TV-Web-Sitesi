<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('fingerprint')->unique();
            $table->string('type')->default('insight_alert');
            $table->string('category')->index(); // search, content, traffic, ads, technical
            $table->string('severity')->index(); // warning, critical
            $table->string('title');
            $table->text('description');
            $table->string('source');
            $table->string('metric')->nullable();
            $table->string('change')->nullable();
            $table->string('action_label')->nullable();
            $table->json('action_target')->nullable();
            $table->string('status')->default('open')->index(); // open, resolved, dismissed
            $table->timestamp('first_detected_at')->useCurrent();
            $table->timestamp('last_detected_at')->useCurrent()->index();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('dismissed_at')->nullable();
            $table->timestamp('notified_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_alerts');
    }
};
