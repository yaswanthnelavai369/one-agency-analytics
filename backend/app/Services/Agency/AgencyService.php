<?php

namespace App\Services\Agency;

use App\Models\Agency;
use App\Models\User;
use App\Repositories\Contracts\AgencyRepositoryInterface;
use App\Services\RBAC\RBACService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AgencyService
{
    public function __construct(
        protected AgencyRepositoryInterface $agencies,
        protected RBACService $rbac,
    ) {}

    /**
     * Creates the Agency record, links the owning user, and provisions
     * default roles — the full "sign up as an agency" flow.
     */
    public function createForOwner(User $owner, array $data): Agency
    {
        return DB::transaction(function () use ($owner, $data) {
            /** @var Agency $agency */
            $agency = $this->agencies->create([
                'uuid' => Str::uuid(),
                'name' => $data['name'],
                'slug' => $this->uniqueSlug($data['name']),
                'plan_id' => $data['plan_id'] ?? null,
                'owner_id' => $owner->id,
                'status' => 'trial',
                'trial_ends_at' => now()->addDays(14),
                'billing_email' => $data['billing_email'] ?? $owner->email,
                'primary_color' => $data['primary_color'] ?? '#6C5CE7',
                'secondary_color' => $data['secondary_color'] ?? '#00CEC9',
            ]);

            $owner->forceFill(['agency_id' => $agency->id])->save();

            $this->rbac->provisionDefaultRoles($agency);
            $this->rbac->assignRole($owner, 'Agency Owner', $agency);

            return $agency;
        });
    }

    public function updateBranding(Agency $agency, array $branding): Agency
    {
        return $this->agencies->update($agency, array_intersect_key($branding, array_flip([
            'logo_path', 'favicon_path', 'brand_name', 'primary_color', 'secondary_color',
            'font_family', 'login_background_path', 'login_illustration_path', 'login_layout',
            'hide_platform_branding', 'custom_footer', 'custom_menu',
        ])));
    }

    public function requestCustomDomain(Agency $agency, string $domain): Agency
    {
        return $this->agencies->update($agency, [
            'custom_domain' => $domain,
            'custom_domain_verified' => false,
            'custom_domain_verification_token' => Str::random(32),
        ]);
    }

    protected function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;

        while ($this->agencies->findBySlug($slug)) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
