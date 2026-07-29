<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // recipient, when user-targeted
            $table->foreignId('anomaly_id')->nullable()->constrained('anomalies')->nullOnDelete();
            $table->string('channel');
            $table->string('recipient'); // email address, phone number, or a label like "Slack: #alerts"
            $table->enum('status', ['sent', 'failed'])->index();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['agency_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
