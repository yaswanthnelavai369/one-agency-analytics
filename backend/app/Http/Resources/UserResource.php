<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'name' => $this->name,
            'email' => $this->email,
            'avatar' => $this->avatar,
            'user_type' => $this->user_type,
            'agency_id' => $this->agency?->uuid,
            'client_id' => $this->client?->uuid,
            'theme_preference' => $this->theme_preference,
            'locale' => $this->locale,
            'two_factor_enabled' => $this->hasTwoFactorEnabled(),
            'roles' => $this->whenLoaded('roles', fn () => $this->roles->pluck('name')),
            'permissions' => $this->when(
                $request->boolean('with_permissions'),
                fn () => $this->getAllPermissions()->pluck('name')
            ),
            'created_at' => $this->created_at,
        ];
    }
}
