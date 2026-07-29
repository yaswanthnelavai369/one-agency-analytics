<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // One thread per client, shared by the whole agency team and the whole
    // client-portal side — not per-user like AI chat, since this is "the
    // agency talking to the client," not one team member's private thread.
    public function up(): void
    {
        Schema::create('chat_threads', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();

            // Simple per-side read tracking rather than per-message-per-user receipts:
            // unread count = messages from the other side created after this timestamp.
            $table->timestamp('agency_last_read_at')->nullable();
            $table->timestamp('client_last_read_at')->nullable();
            $table->timestamp('last_message_at')->nullable();

            $table->timestamps();

            $table->unique('client_id'); // one thread per client
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_threads');
    }
};
