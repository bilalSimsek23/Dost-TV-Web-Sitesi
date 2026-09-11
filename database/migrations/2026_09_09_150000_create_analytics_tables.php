<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_logs', function (Blueprint $table) {
            $table->id();
            $table->string('search_query');
            $table->string('normalized_query')->index();
            $table->unsignedInteger('result_count')->default(0);
            $table->string('device_type')->nullable();
            $table->string('source_page')->nullable();
            $table->timestamp('searched_at')->useCurrent()->index();
            $table->timestamps();
        });

        Schema::create('site_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_name')->index();
            $table->string('entity_type')->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->useCurrent()->index();
            $table->timestamps();
        });

        Schema::create('not_found_logs', function (Blueprint $table) {
            $table->id();
            $table->string('path')->index();
            $table->string('referer')->nullable();
            $table->unsignedInteger('hit_count')->default(1);
            $table->timestamp('last_occurred_at')->useCurrent()->index();
            $table->timestamps();
        });

        Schema::create('analytics_integrations', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->unique(); // e.g. 'ga4', 'google_ads', 'meta_ads'
            $table->boolean('is_enabled')->default(false);
            $table->string('property_id')->nullable();
            $table->text('credentials')->nullable(); // encrypted cast
            $table->timestamp('last_synced_at')->nullable();
            $table->text('last_error')->nullable();
            $table->json('metrics_snapshot')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_integrations');
        Schema::dropIfExists('not_found_logs');
        Schema::dropIfExists('site_events');
        Schema::dropIfExists('search_logs');
    }
};
