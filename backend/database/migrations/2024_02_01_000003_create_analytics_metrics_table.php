<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Generic time-series store every integration's sync job writes into,
    // keeping dashboard widgets provider-agnostic (a "visitors" widget just
    // queries metric='visitors' regardless of whether it came from GA4 or
    // another analytics source down the line).
    public function up(): void
    {
        Schema::create('analytics_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('integration_id')->constrained('integrations')->cascadeOnDelete();
            $table->string('metric'); // e.g. "visitors", "sessions", "conversions", "revenue"
            $table->date('date');
            $table->decimal('value', 18, 4);
            $table->json('dimensions')->nullable(); // e.g. {"channel": "organic", "device": "mobile"}
            $table->timestamps();

            $table->index(['client_id', 'metric', 'date']);
            $table->index(['integration_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_metrics');
    }
};
