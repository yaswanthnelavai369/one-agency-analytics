<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Per-agency configuration for broadcast channels (Slack/Discord/Teams webhook
    // URLs). Email/SMS/WhatsApp are user-targeted, not agency-configured, so they
    // don't need a row here to be "enabled" — see NotificationChannelInterface::isBroadcast().
    public function up(): void
    {
        Schema::create('notification_channels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->string('channel'); // 'email' | 'slack' | 'discord' | 'teams' | 'sms' | 'whatsapp'
            $table->boolean('is_enabled')->default(false);
            $table->json('config')->nullable(); // e.g. {"webhook_url": "..."} for broadcast channels
            $table->timestamps();

            $table->unique(['agency_id', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_channels');
    }
};
