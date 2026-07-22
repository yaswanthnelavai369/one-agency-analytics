<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\NewAccessToken;

class AuthService
{
    public function __construct(
        protected UserRepositoryInterface $users,
        protected TwoFactorService $twoFactor,
    ) {}

    /**
     * Registers a new Agency owner account. Client/team-member accounts are
     * created via invitation flows (see AgencyService/ClientService), not here.
     */
    public function registerAgencyOwner(array $data): User
    {
        return $this->users->create([
            'uuid' => Str::uuid(),
            'user_type' => 'agency',
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'status' => 'active',
        ]);
    }

    /**
     * Validates credentials. If 2FA is enabled, returns null and the caller
     * must complete the flow via TwoFactorService::challenge()/verify().
     */
    public function attemptLogin(string $email, string $password): ?User
    {
        $user = $this->users->findByEmail($email);

        if (! $user || ! $user->password || ! Hash::check($password, $user->password)) {
            return null;
        }

        if ($user->status !== 'active') {
            throw new AuthenticationException('This account is not active.');
        }

        return $user;
    }

    public function issueToken(User $user, string $deviceName = 'api'): NewAccessToken
    {
        $abilities = $this->abilitiesFor($user);

        return $user->createToken($deviceName, $abilities);
    }

    public function logout(User $user, ?string $currentTokenId = null): void
    {
        if ($currentTokenId) {
            $user->tokens()->where('id', $currentTokenId)->delete();

            return;
        }

        $user->tokens()->delete();
    }

    public function logoutAllDevices(User $user): void
    {
        $user->tokens()->delete();
    }

    public function recordLogin(User $user, string $ip): void
    {
        $this->users->updateLastLogin($user, $ip);
    }

    protected function abilitiesFor(User $user): array
    {
        return match ($user->user_type) {
            'master_admin' => ['*'],
            default => $user->getAllPermissions()->pluck('name')->push('access-api')->unique()->values()->all(),
        };
    }
}
