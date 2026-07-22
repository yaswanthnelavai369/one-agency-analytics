<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Agency extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid', 'name', 'slug', 'plan_id', 'owner_id', 'status', 'trial_ends_at', 'billing_email',
        'logo_path', 'favicon_path', 'brand_name', 'primary_color', 'secondary_color', 'font_family',
        'login_background_path', 'login_illustration_path', 'login_layout',
        'email_template_overrides', 'whatsapp_template_overrides', 'hide_platform_branding',
        'custom_footer', 'custom_menu', 'custom_domain', 'custom_domain_verified',
        'smtp_host', 'smtp_port', 'smtp_username', 'smtp_password_encrypted',
        'smtp_encryption', 'smtp_from_address', 'smtp_from_name',
    ];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'hide_platform_branding' => 'boolean',
            'custom_domain_verified' => 'boolean',
            'email_template_overrides' => 'array',
            'whatsapp_template_overrides' => 'array',
            'custom_menu' => 'array',
            'smtp_password_encrypted' => 'encrypted',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    public function teamMembers(): HasMany
    {
        return $this->hasMany(TeamMember::class);
    }

    public function isOnTrial(): bool
    {
        return $this->status === 'trial' && $this->trial_ends_at?->isFuture();
    }
}
