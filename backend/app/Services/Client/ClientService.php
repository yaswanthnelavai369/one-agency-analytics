<?php

namespace App\Services\Client;

use App\Models\Agency;
use App\Models\Client;
use App\Repositories\Contracts\ClientRepositoryInterface;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ClientService
{
    public function __construct(protected ClientRepositoryInterface $clients) {}

    public function createForAgency(Agency $agency, array $data): Client
    {
        $this->assertWithinPlanLimit($agency);

        return $this->clients->create([
            'uuid' => Str::uuid(),
            'agency_id' => $agency->id,
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'website' => $data['website'] ?? null,
            'industry' => $data['industry'] ?? null,
            'timezone' => $data['timezone'] ?? 'UTC',
            'status' => 'onboarding',
        ]);
    }

    public function listForAgency(Agency $agency, array $filters = [])
    {
        return $this->clients->forAgency($agency->id, $filters);
    }

    protected function assertWithinPlanLimit(Agency $agency): void
    {
        $limit = $agency->plan?->client_limit;

        if (is_null($limit)) {
            return; // unlimited
        }

        $current = $this->clients->forAgency($agency->id)->count();

        if ($current >= $limit) {
            throw ValidationException::withMessages([
                'plan' => "Your current plan allows a maximum of {$limit} clients. Upgrade to add more.",
            ]);
        }
    }
}
