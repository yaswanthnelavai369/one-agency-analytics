<?php

namespace App\Http\Resources;

use App\Services\Goals\GoalService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GoalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $forecast = app(GoalService::class)->forecast($this->resource);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'metric' => $this->metric,
            'tracking_mode' => $this->tracking_mode,
            'is_auto_tracked' => $this->isAutoTracked(),
            'target_value' => $this->target_value,
            'current_value' => $this->current_value,
            'format' => $this->format,
            'start_date' => $this->start_date->toDateString(),
            'deadline' => $this->deadline?->toDateString(),
            'status' => $this->status,
            'forecast' => $forecast,
            'progress_history' => $this->whenLoaded('progress', fn () => $this->progress->map(fn ($p) => [
                'date' => $p->date->toDateString(),
                'value' => $p->value,
            ])),
            'created_at' => $this->created_at,
        ];
    }
}
