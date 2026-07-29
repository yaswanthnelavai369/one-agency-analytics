<?php

namespace App\Repositories\Contracts;

use App\Models\ChatThread;

interface ChatRepositoryInterface
{
    public function findByClient(int $clientId): ?ChatThread;

    public function create(array $data): ChatThread;
}
