<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('logo_path')->nullable();
            $table->string('website')->nullable();
            $table->string('industry')->nullable();
            $table->string('timezone')->default('UTC');
            $table->enum('status', ['active', 'onboarding', 'paused', 'archived'])->default('onboarding')->index();
            $table->json('branding_overrides')->nullable(); // per-client branding within an agency's white-label
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['agency_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
