<?php

namespace App\Repositories\Contracts;

use App\Models\Integration;
use Illuminate\Database\Eloquent\Collection;

interface IntegrationRepositoryInterface extends BaseRepositoryInterface
{
    public function forClient(int $clientId): Collection;

    public function findForClient(int $clientId, int $integrationId): ?Integration;

    public function findByProviderAndAccount(int $clientId, string $provider, string $externalAccountId): ?Integration;

    public function dueForSync(string $frequency): Collection;
}
