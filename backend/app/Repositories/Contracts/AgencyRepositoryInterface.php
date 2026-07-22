<?php

namespace App\Repositories\Contracts;

use App\Models\Agency;

interface AgencyRepositoryInterface extends BaseRepositoryInterface
{
    public function findBySlug(string $slug): ?Agency;

    public function findByCustomDomain(string $domain): ?Agency;

    public function withUsageCounts(int $agencyId): ?Agency;
}
