<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agencies', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignId('plan_id')->nullable()->constrained('plans')->nullOnDelete();
            $table->foreignId('owner_id')->nullable(); // FK added after users table exists (see later migration)
            $table->enum('status', ['active', 'trial', 'suspended', 'cancelled'])->default('trial')->index();
            $table->timestamp('trial_ends_at')->nullable();
            $table->string('billing_email')->nullable();

            // White-label branding
            $table->string('logo_path')->nullable();
            $table->string('favicon_path')->nullable();
            $table->string('brand_name')->nullable();
            $table->string('primary_color', 7)->default('#6C5CE7');
            $table->string('secondary_color', 7)->default('#00CEC9');
            $table->string('font_family')->default('Inter');
            $table->string('login_background_path')->nullable();
            $table->string('login_illustration_path')->nullable();
            $table->string('login_layout')->default('split'); // split | centered | fullscreen
            $table->json('email_template_overrides')->nullable();
            $table->json('whatsapp_template_overrides')->nullable();
            $table->boolean('hide_platform_branding')->default(false);
            $table->string('custom_footer')->nullable();
            $table->json('custom_menu')->nullable();

            // Custom domain
            $table->string('custom_domain')->nullable()->unique();
            $table->boolean('custom_domain_verified')->default(false);
            $table->string('custom_domain_verification_token')->nullable();

            // Custom SMTP
            $table->string('smtp_host')->nullable();
            $table->integer('smtp_port')->nullable();
            $table->string('smtp_username')->nullable();
            $table->text('smtp_password_encrypted')->nullable();
            $table->string('smtp_encryption')->nullable();
            $table->string('smtp_from_address')->nullable();
            $table->string('smtp_from_name')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agencies');
    }
};
