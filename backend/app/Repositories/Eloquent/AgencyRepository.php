<?php

namespace App\Repositories\Eloquent;

use App\Models\Agency;
use App\Repositories\Contracts\AgencyRepositoryInterface;

class AgencyRepository extends BaseRepository implements AgencyRepositoryInterface
{
    public function __construct(Agency $model)
    {
        parent::__construct($model);
    }

    public function findBySlug(string $slug): ?Agency
    {
        return $this->model->newQuery()->where('slug', $slug)->first();
    }

    public function findByCustomDomain(string $domain): ?Agency
    {
        return $this->model->newQuery()
            ->where('custom_domain', $domain)
            ->where('custom_domain_verified', true)
            ->first();
    }

    public function withUsageCounts(int $agencyId): ?Agency
    {
        return $this->model->newQuery()
            ->withCount(['clients', 'users'])
            ->find($agencyId);
    }
}
