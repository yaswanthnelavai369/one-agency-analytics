<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integrations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();

            // Provider key, e.g. "google_analytics_4", "google_search_console", "meta_ads".
            // New connectors register a key here + an IntegrationProviderInterface class;
            // no core schema change needed to add one.
            $table->string('provider')->index();
            $table->string('display_name')->nullable(); // e.g. a GA4 property name, an ad account name
            $table->string('external_account_id')->nullable(); // provider's id for the connected resource

            $table->enum('status', ['connected', 'disconnected', 'error', 'pending'])->default('pending')->index();
            $table->text('last_error')->nullable();

            $table->foreignId('connected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('connected_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->string('sync_frequency')->default('daily'); // daily | hourly | manual

            $table->json('config')->nullable(); // provider-specific non-secret settings (e.g. GA4 property id)

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['client_id', 'provider', 'external_account_id'], 'integrations_client_provider_account_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integrations');
    }
};
