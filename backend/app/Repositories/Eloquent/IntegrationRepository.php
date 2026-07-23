<?php

namespace App\Repositories\Eloquent;

use App\Models\Integration;
use App\Repositories\Contracts\IntegrationRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class IntegrationRepository extends BaseRepository implements IntegrationRepositoryInterface
{
    public function __construct(Integration $model)
    {
        parent::__construct($model);
    }

    public function forClient(int $clientId): Collection
    {
        return $this->model->newQuery()->where('client_id', $clientId)->get();
    }

    public function findForClient(int $clientId, int $integrationId): ?Integration
    {
        return $this->model->newQuery()
            ->where('client_id', $clientId)
            ->where('id', $integrationId)
            ->first();
    }

    public function findByProviderAndAccount(int $clientId, string $provider, string $externalAccountId): ?Integration
    {
        return $this->model->newQuery()
            ->where('client_id', $clientId)
            ->where('provider', $provider)
            ->where('external_account_id', $externalAccountId)
            ->first();
    }

    public function dueForSync(string $frequency): Collection
    {
        return $this->model->newQuery()
            ->where('status', 'connected')
            ->where('sync_frequency', $frequency)
            ->get();
    }
}
