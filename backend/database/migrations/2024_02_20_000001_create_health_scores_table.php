<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // One row per client per day — powers both "today's score" and the
    // trend graph / historical comparison views without recomputing history.
    public function up(): void
    {
        Schema::create('health_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->date('date');

            $table->unsignedTinyInteger('overall_score');
            $table->unsignedTinyInteger('growth_score')->nullable();
            $table->unsignedTinyInteger('seo_score')->nullable();
            $table->unsignedTinyInteger('ads_score')->nullable();
            $table->unsignedTinyInteger('social_score')->nullable();
            $table->unsignedTinyInteger('website_score')->nullable();
            $table->unsignedTinyInteger('lead_score')->nullable();
            $table->unsignedTinyInteger('revenue_score')->nullable();

            // Per-category raw signals (metric values + which weights fired) for
            // transparency/debugging, and the source data for suggestion generation.
            $table->json('breakdown')->nullable();

            $table->timestamps();

            $table->unique(['client_id', 'date']);
            $table->index(['client_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('health_scores');
    }
};
