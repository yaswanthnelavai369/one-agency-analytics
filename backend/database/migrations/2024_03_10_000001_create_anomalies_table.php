<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anomalies', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('integration_id')->nullable()->constrained('integrations')->nullOnDelete();

            // e.g. "traffic_drop", "conversion_drop", "revenue_loss", "ctr_drop",
            // "ranking_loss", "high_cpc", "high_cpa", "campaign_failure", "api_failure",
            // "missing_tracking_codes". Plain string (not enum) so new detector types
            // don't require a migration.
            $table->string('type')->index();
            $table->enum('severity', ['critical', 'warning', 'info'])->default('warning')->index();
            $table->string('metric')->nullable(); // the analytics_metrics key this anomaly is about, if any

            $table->decimal('current_value', 18, 4)->nullable();
            $table->decimal('baseline_value', 18, 4)->nullable();
            $table->decimal('change_percent', 8, 2)->nullable();

            $table->string('message');
            $table->json('possible_causes')->nullable();
            $table->json('recommended_fixes')->nullable();

            $table->enum('status', ['open', 'acknowledged', 'resolved'])->default('open')->index();
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('resolved_at')->nullable();

            $table->date('detected_date'); // the day the anomaly was found, for dedup against re-runs
            $table->timestamps();

            $table->unique(['client_id', 'type', 'metric', 'detected_date'], 'anomalies_dedup_unique');
            $table->index(['client_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anomalies');
    }
};
