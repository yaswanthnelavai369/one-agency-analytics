<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Links a user (of user_type = 'team_member') to the agency they were invited into,
    // plus optional scoping to specific clients/projects and an invitation lifecycle.
    public function up(): void
    {
        Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('invitation_status', ['pending', 'accepted', 'revoked'])->default('pending');
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('joined_at')->nullable();
            // Fine-grained scoping: if null, the member's Spatie role/permissions apply agency-wide.
            $table->json('client_scope')->nullable(); // array of client_ids this member is restricted to
            $table->timestamps();

            $table->unique(['agency_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_members');
    }
};
