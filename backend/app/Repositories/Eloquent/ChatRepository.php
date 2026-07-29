<?php

namespace App\Repositories\Eloquent;

use App\Models\ChatThread;
use App\Repositories\Contracts\ChatRepositoryInterface;

class ChatRepository implements ChatRepositoryInterface
{
    public function __construct(protected ChatThread $model) {}

    public function findByClient(int $clientId): ?ChatThread
    {
        return $this->model->newQuery()
            ->where('client_id', $clientId)
            ->with('messages.sender')
            ->first();
    }

    public function create(array $data): ChatThread
    {
        return $this->model->newQuery()->create($data);
    }
}
