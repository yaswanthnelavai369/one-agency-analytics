<?php

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\Client;
use App\Models\Plan;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\Agency\AgencyService;
use App\Services\Client\ClientService;
use App\Services\RBAC\RBACService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeds one working login per role in the platform's access model, all under
 * one demo agency + one demo client, so every screen and permission level can
 * be exercised locally without manually creating accounts through the UI.
 *
 * All passwords: ChangeMe!12345 — change or remove this seeder before any
 * real deployment; it exists purely for local development and demos.
 */
class DemoDataSeeder extends Seeder
{
    protected const PASSWORD = 'ChangeMe!12345';

    public function run(): void
    {
        $agency = $this->seedAgencyOwner();
        $client = $this->seedClient($agency);
        $this->seedTeamMembers($agency);
        $this->seedClientPortalUser($agency, $client);

        $this->command?->table(
            ['Role', 'Email', 'Password', 'Notes'],
            [
                ['Master Admin', 'admin@search29.ai', self::PASSWORD, 'Global — use X-Agency-ID header to act on an agency'],
                ['Agency Owner', 'agency@search29.ai', self::PASSWORD, 'Full access to "Search29 Agency"'],
                ['Manager (team member)', 'manager@search29.ai', self::PASSWORD, 'Most permissions, no billing/team-remove'],
                ['Analyst (team member)', 'analyst@search29.ai', self::PASSWORD, 'View + export + AI chat, no edit/create'],
                ['Viewer (team member)', 'viewer@search29.ai', self::PASSWORD, 'Read-only'],
                ['Client portal', 'client@search29.ai', self::PASSWORD, 'Sees only "Acme Corporation" via the /client/* routes'],
            ]
        );
    }

    protected function seedAgencyOwner(): Agency
    {
        $owner = User::firstOrCreate(
            ['email' => 'agency@search29.ai'],
            [
                'uuid' => Str::uuid(),
                'user_type' => 'agency',
                'name' => 'Demo Agency Owner',
                'password' => Hash::make(self::PASSWORD),
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        $agency = Agency::where('owner_id', $owner->id)->first();

        if (! $agency) {
            $plan = Plan::where('slug', 'professional')->first() ?: Plan::first();
            $agency = app(AgencyService::class)->createForOwner($owner, [
                'name' => 'Search29 Agency',
                'plan_id' => $plan?->id,
            ]);
        } else {
            $owner->forceFill(['agency_id' => $agency->id])->save();
        }

        return $agency;
    }

    protected function seedClient(Agency $agency): Client
    {
        $client = Client::where('agency_id', $agency->id)->first();

        if (! $client) {
            $client = app(ClientService::class)->createForAgency($agency, [
                'name' => 'Acme Corporation',
                'website' => 'https://acme.example.com',
                'industry' => 'Manufacturing',
                'timezone' => 'UTC',
            ]);
        }

        return $client;
    }

    /** One team member per non-owner system role, so RBAC differences are actually visible. */
    protected function seedTeamMembers(Agency $agency): void
    {
        $rbac = app(RBACService::class);

        $members = [
            ['email' => 'manager@search29.ai', 'name' => 'Demo Manager', 'role' => 'Manager'],
            ['email' => 'analyst@search29.ai', 'name' => 'Demo Analyst', 'role' => 'Analyst'],
            ['email' => 'viewer@search29.ai', 'name' => 'Demo Viewer', 'role' => 'Viewer'],
        ];

        foreach ($members as $member) {
            $user = User::firstOrCreate(
                ['email' => $member['email']],
                [
                    'uuid' => Str::uuid(),
                    'agency_id' => $agency->id,
                    'user_type' => 'team_member',
                    'name' => $member['name'],
                    'password' => Hash::make(self::PASSWORD),
                    'status' => 'active',
                    'email_verified_at' => now(),
                ]
            );

            if ($user->agency_id !== $agency->id) {
                $user->forceFill(['agency_id' => $agency->id])->save();
            }

            TeamMember::firstOrCreate(
                ['agency_id' => $agency->id, 'user_id' => $user->id],
                ['invitation_status' => 'accepted', 'invited_at' => now(), 'joined_at' => now()]
            );

            app()->make(PermissionRegistrar::class)->forgetCachedPermissions();
            $rbac->assignRole($user, $member['role'], $agency);
        }
    }

    protected function seedClientPortalUser(Agency $agency, Client $client): void
    {
        $user = User::firstOrCreate(
            ['email' => 'client@search29.ai'],
            [
                'uuid' => Str::uuid(),
                'agency_id' => $agency->id,
                'client_id' => $client->id,
                'user_type' => 'client',
                'name' => 'Acme Corporation Contact',
                'password' => Hash::make(self::PASSWORD),
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        if ($user->client_id !== $client->id) {
            $user->forceFill(['agency_id' => $agency->id, 'client_id' => $client->id])->save();
        }

        // No Spatie role needed: client-portal access is scoped entirely by
        // EnsureClientAccess (user_type='client' + client_id match), not by
        // permission checks — see routes/api.php's /client/* group.
    }
}
