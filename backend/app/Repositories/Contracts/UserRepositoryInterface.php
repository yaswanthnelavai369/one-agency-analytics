<?php

namespace App\Repositories\Contracts;

use App\Models\User;

interface UserRepositoryInterface extends BaseRepositoryInterface
{
    public function findByEmail(string $email): ?User;

    public function findBySocialId(string $provider, string $id): ?User;

    public function forAgency(int $agencyId): \Illuminate\Database\Eloquent\Collection;

    public function updateLastLogin(User $user, string $ip): void;
}
