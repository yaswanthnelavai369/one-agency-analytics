<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Without this, two different goals going at-risk on the same day for the
    // same client would collide on the (client_id, type, metric, detected_date)
    // dedup key — 'goal_at_risk' anomalies for manual goals all share
    // type='goal_at_risk' + metric=null, so only the first would ever be stored.
    public function up(): void
    {
        Schema::table('anomalies', function (Blueprint $table) {
            $table->foreignId('goal_id')->nullable()->after('integration_id')->constrained('goals')->nullOnDelete();
        });

        Schema::table('anomalies', function (Blueprint $table) {
            $table->dropUnique('anomalies_dedup_unique');
        });

        Schema::table('anomalies', function (Blueprint $table) {
            $table->unique(['client_id', 'type', 'metric', 'goal_id', 'detected_date'], 'anomalies_dedup_unique');
        });
    }

    public function down(): void
    {
        Schema::table('anomalies', function (Blueprint $table) {
            $table->dropUnique('anomalies_dedup_unique');
            $table->dropConstrainedForeignId('goal_id');
        });

        Schema::table('anomalies', function (Blueprint $table) {
            $table->unique(['client_id', 'type', 'metric', 'detected_date'], 'anomalies_dedup_unique');
        });
    }
};
