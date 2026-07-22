<?php

namespace App\Repositories\Contracts;

use App\Models\Client;
use Illuminate\Database\Eloquent\Collection;

interface ClientRepositoryInterface extends BaseRepositoryInterface
{
    public function forAgency(int $agencyId, array $filters = []): Collection;

    public function findForAgency(int $agencyId, int $clientId): ?Client;
}
