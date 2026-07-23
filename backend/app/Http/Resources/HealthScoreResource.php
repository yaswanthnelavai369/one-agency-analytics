<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HealthScoreResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'date' => $this->date->toDateString(),
            'overall_score' => $this->overall_score,
            'band' => $this->band(),
            'category_scores' => [
                'growth' => $this->growth_score,
                'seo' => $this->seo_score,
                'ads' => $this->ads_score,
                'social' => $this->social_score,
                'website' => $this->website_score,
                'lead' => $this->lead_score,
                'revenue' => $this->revenue_score,
            ],
            'suggestions' => $this->breakdown['suggestions'] ?? [],
            'signals' => $this->when(
                $request->boolean('with_signals'),
                fn () => collect($this->breakdown['categories'] ?? [])->map(fn ($c) => $c['signals'])->all()
            ),
        ];
    }
}
