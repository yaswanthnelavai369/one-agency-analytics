<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardLayoutResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'client_id' => $this->client_id,
            'is_default' => $this->is_default,
            'is_shared' => $this->is_shared,
            'is_owner' => $this->user_id === $request->user()?->id,
            'widgets' => $this->whenLoaded('widgets', fn () => $this->widgets->map(fn ($w) => [
                'id' => $w->id,
                'widget_type' => $w->widget_type,
                'config' => $w->config,
                'x' => $w->pos_x,
                'y' => $w->pos_y,
                'w' => $w->width,
                'h' => $w->height,
                'hidden' => $w->is_hidden,
            ])),
            'updated_at' => $this->updated_at,
        ];
    }
}
