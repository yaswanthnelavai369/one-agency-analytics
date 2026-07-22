<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgencyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'name' => $this->name,
            'slug' => $this->slug,
            'status' => $this->status,
            'plan' => $this->plan?->name,
            'branding' => [
                'logo' => $this->logo_path,
                'favicon' => $this->favicon_path,
                'brand_name' => $this->brand_name ?? $this->name,
                'primary_color' => $this->primary_color,
                'secondary_color' => $this->secondary_color,
                'font_family' => $this->font_family,
                'login_layout' => $this->login_layout,
                'hide_platform_branding' => $this->hide_platform_branding,
            ],
            'custom_domain' => $this->when($this->custom_domain, [
                'domain' => $this->custom_domain,
                'verified' => $this->custom_domain_verified,
            ]),
            'clients_count' => $this->whenCounted('clients'),
            'users_count' => $this->whenCounted('users'),
            'created_at' => $this->created_at,
        ];
    }
}
