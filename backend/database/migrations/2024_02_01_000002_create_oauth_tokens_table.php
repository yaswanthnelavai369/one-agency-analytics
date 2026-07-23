<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oauth_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('integration_id')->constrained('integrations')->cascadeOnDelete();
            $table->text('access_token_encrypted');
            $table->text('refresh_token_encrypted')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->json('scopes')->nullable();
            $table->timestamps();

            $table->unique('integration_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oauth_tokens');
    }
};
