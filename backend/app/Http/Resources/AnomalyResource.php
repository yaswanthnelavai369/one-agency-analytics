<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnomalyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'severity' => $this->severity,
            'metric' => $this->metric,
            'current_value' => $this->current_value,
            'baseline_value' => $this->baseline_value,
            'change_percent' => $this->change_percent,
            'message' => $this->message,
            'possible_causes' => $this->possible_causes ?? [],
            'recommended_fixes' => $this->recommended_fixes ?? [],
            'status' => $this->status,
            'integration' => $this->whenLoaded('integration', fn () => $this->integration?->display_name),
            'detected_date' => $this->detected_date->toDateString(),
            'acknowledged_at' => $this->acknowledged_at,
            'resolved_at' => $this->resolved_at,
        ];
    }
}
