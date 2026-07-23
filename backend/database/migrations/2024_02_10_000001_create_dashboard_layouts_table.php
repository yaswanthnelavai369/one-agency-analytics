<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboard_layouts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // owner (personal dashboard)
            $table->string('name');
            $table->boolean('is_default')->default(false); // shown first when the client/agency is opened
            $table->boolean('is_shared')->default(false); // visible to other agency team members, not just the owner
            $table->boolean('is_template')->default(false); // reusable starting point offered to new clients
            $table->enum('template_scope', ['agency', 'platform'])->nullable(); // agency-authored vs Master-Admin-authored
            $table->timestamps();
            $table->softDeletes();

            $table->index(['agency_id', 'client_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_layouts');
    }
};
