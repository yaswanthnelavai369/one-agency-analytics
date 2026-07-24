<?php

namespace App\Repositories\Contracts;

use App\Models\AIConversation;
use Illuminate\Database\Eloquent\Collection;

interface AIConversationRepositoryInterface
{
    public function forUser(int $agencyId, int $userId, ?int $clientId): Collection;

    public function findForUser(int $id, int $agencyId, int $userId): ?AIConversation;

    public function create(array $data): AIConversation;
}
