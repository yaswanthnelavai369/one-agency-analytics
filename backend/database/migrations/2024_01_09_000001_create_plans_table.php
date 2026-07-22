<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_custom')->default(false); // true for Master-Admin-created one-off plans
            $table->boolean('is_active')->default(true);

            $table->decimal('price_monthly', 10, 2)->default(0);
            $table->decimal('price_yearly', 10, 2)->default(0);
            $table->string('currency', 3)->default('USD');

            // Limits (nullable = unlimited)
            $table->unsignedInteger('client_limit')->nullable();
            $table->unsignedInteger('user_limit')->nullable();
            $table->unsignedInteger('project_limit')->nullable();
            $table->unsignedBigInteger('storage_limit_mb')->nullable();
            $table->unsignedInteger('report_limit_monthly')->nullable();
            $table->unsignedInteger('export_limit_monthly')->nullable();
            $table->unsignedInteger('ai_credit_limit_monthly')->nullable();
            $table->unsignedInteger('api_call_limit_monthly')->nullable();
            $table->unsignedInteger('integration_limit')->nullable();

            $table->enum('support_level', ['community', 'email', 'priority', 'dedicated'])->default('community');
            $table->boolean('branding_allowed')->default(false);
            $table->boolean('custom_domain_allowed')->default(false);

            $table->json('feature_flags')->nullable(); // module toggles, e.g. {"ai_chat": true, "anomaly_detection": true}
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
