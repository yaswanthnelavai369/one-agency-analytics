<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HealthScore extends Model
{
    protected $fillable = [
        'client_id', 'date', 'overall_score', 'growth_score', 'seo_score', 'ads_score',
        'social_score', 'website_score', 'lead_score', 'revenue_score', 'breakdown',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'breakdown' => 'array',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /** Simple traffic-light banding used consistently across the UI (see also frontend semanticColors). */
    public function band(): string
    {
        return match (true) {
            $this->overall_score >= 80 => 'excellent',
            $this->overall_score >= 60 => 'good',
            $this->overall_score >= 40 => 'needs_attention',
            default => 'at_risk',
        };
    }
}
