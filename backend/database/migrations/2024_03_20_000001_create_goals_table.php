<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goals', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('name'); // e.g. "100 Leads This Quarter"

            // If set, progress is auto-computed from analytics_metrics (see GoalCatalogue
            // for the metric -> tracking_mode mapping); if null, it's a manual goal the
            // creator updates by hand via goal_progress entries.
            $table->string('metric')->nullable();
            $table->enum('tracking_mode', ['cumulative', 'snapshot', 'manual'])->default('manual');
            // cumulative: sum the metric from start_date to today (e.g. Leads, Visitors, Sales)
            // snapshot: latest value of the metric (e.g. CTR, ROAS — a rate, not a running total)
            // manual: no linked metric; progress is entered by hand

            $table->decimal('target_value', 18, 4);
            $table->decimal('current_value', 18, 4)->default(0);
            $table->enum('format', ['number', 'percent', 'currency'])->default('number');

            $table->date('start_date');
            $table->date('deadline')->nullable();
            $table->enum('status', ['active', 'completed', 'missed', 'archived'])->default('active')->index();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['client_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goals');
    }
};
