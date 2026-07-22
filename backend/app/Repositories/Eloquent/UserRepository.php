<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->model->newQuery()->where('email', $email)->first();
    }

    public function findBySocialId(string $provider, string $id): ?User
    {
        $column = match ($provider) {
            'google' => 'google_id',
            'microsoft' => 'microsoft_id',
            default => throw new \InvalidArgumentException("Unsupported provider [{$provider}]"),
        };

        return $this->model->newQuery()->where($column, $id)->first();
    }

    public function forAgency(int $agencyId): Collection
    {
        return $this->model->newQuery()->where('agency_id', $agencyId)->get();
    }

    public function updateLastLogin(User $user, string $ip): void
    {
        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $ip,
        ])->save();
    }
}
