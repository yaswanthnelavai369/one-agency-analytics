<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid', 'name', 'slug', 'description', 'is_custom', 'is_active',
        'price_monthly', 'price_yearly', 'currency',
        'client_limit', 'user_limit', 'project_limit', 'storage_limit_mb',
        'report_limit_monthly', 'export_limit_monthly', 'ai_credit_limit_monthly',
        'api_call_limit_monthly', 'integration_limit',
        'support_level', 'branding_allowed', 'custom_domain_allowed',
        'feature_flags', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_custom' => 'boolean',
            'is_active' => 'boolean',
            'branding_allowed' => 'boolean',
            'custom_domain_allowed' => 'boolean',
            'feature_flags' => 'array',
        ];
    }

    public function agencies(): HasMany
    {
        return $this->hasMany(Agency::class);
    }

    public function hasUnlimited(string $limitColumn): bool
    {
        return is_null($this->{$limitColumn});
    }
}
