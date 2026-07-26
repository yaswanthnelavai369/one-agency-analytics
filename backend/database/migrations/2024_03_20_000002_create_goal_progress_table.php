<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Daily snapshots of a goal's current_value — powers "Historical Progress"
    // and the trend chart without recomputing from analytics_metrics every time.
    public function up(): void
    {
        Schema::create('goal_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goal_id')->constrained('goals')->cascadeOnDelete();
            $table->date('date');
            $table->decimal('value', 18, 4); // cumulative current_value as of this date, not a delta
            $table->enum('source', ['auto', 'manual'])->default('auto');
            $table->foreignId('logged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['goal_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goal_progress');
    }
};
