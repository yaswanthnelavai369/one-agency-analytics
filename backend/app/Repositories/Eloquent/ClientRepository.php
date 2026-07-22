<?php

namespace App\Repositories\Eloquent;

use App\Models\Client;
use App\Repositories\Contracts\ClientRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ClientRepository extends BaseRepository implements ClientRepositoryInterface
{
    public function __construct(Client $model)
    {
        parent::__construct($model);
    }

    public function forAgency(int $agencyId, array $filters = []): Collection
    {
        $query = $this->model->newQuery()->where('agency_id', $agencyId);

        return $this->applyFilters($query, $filters)->get();
    }

    public function findForAgency(int $agencyId, int $clientId): ?Client
    {
        return $this->model->newQuery()
            ->where('agency_id', $agencyId)
            ->where('id', $clientId)
            ->first();
    }
}
