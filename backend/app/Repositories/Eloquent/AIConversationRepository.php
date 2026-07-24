<?php

namespace App\Repositories\Eloquent;

use App\Models\AIConversation;
use App\Repositories\Contracts\AIConversationRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class AIConversationRepository implements AIConversationRepositoryInterface
{
    public function __construct(protected AIConversation $model) {}

    public function forUser(int $agencyId, int $userId, ?int $clientId): Collection
    {
        return $this->model->newQuery()
            ->where('agency_id', $agencyId)
            ->where('user_id', $userId)
            ->where('client_id', $clientId)
            ->latest()
            ->get();
    }

    public function findForUser(int $id, int $agencyId, int $userId): ?AIConversation
    {
        return $this->model->newQuery()
            ->where('id', $id)
            ->where('agency_id', $agencyId)
            ->where('user_id', $userId)
            ->with('messages')
            ->first();
    }

    public function create(array $data): AIConversation
    {
        return $this->model->newQuery()->create($data);
    }
}
