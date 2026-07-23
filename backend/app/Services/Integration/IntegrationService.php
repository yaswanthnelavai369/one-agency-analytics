<?php

namespace App\Services\Integration;

use App\Integrations\IntegrationManager;
use App\Models\Agency;
use App\Models\Client;
use App\Models\Integration;
use App\Models\User;
use App\Repositories\Contracts\IntegrationRepositoryInterface;
use App\Models\OauthToken;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class IntegrationService
{
    public function __construct(
        protected IntegrationManager $manager,
        protected IntegrationRepositoryInterface $integrations,
    ) {}

    /** Builds the OAuth URL and a signed state payload proving which client/agency/user initiated it. */
    public function initiateConnect(string $providerKey, Client $client, User $user, string $redirectUri): string
    {
        $this->assertWithinPlanLimit($client->agency);

        $provider = $this->manager->resolve($providerKey);

        $state = Crypt::encryptString(json_encode([
            'provider' => $providerKey,
            'agency_id' => $client->agency_id,
            'client_id' => $client->id,
            'user_id' => $user->id,
            'nonce' => Str::random(16),
            'issued_at' => now()->timestamp,
        ]));

        return $provider->getAuthorizationUrl($redirectUri, $state);
    }

    /**
     * Handles the OAuth callback: verifies state, exchanges the code, and persists
     * the Integration + OauthToken rows.
     */
    public function completeConnect(string $code, string $state, string $redirectUri): Integration
    {
        $payload = $this->decodeState($state);
        $provider = $this->manager->resolve($payload['provider']);

        $result = $provider->handleCallback($code, $redirectUri);

        return DB::transaction(function () use ($payload, $result) {
            $integration = $this->integrations->findByProviderAndAccount(
                $payload['client_id'],
                $payload['provider'],
                $result['external_account_id']
            ) ?? $this->integrations->create([
                'uuid' => Str::uuid(),
                'agency_id' => $payload['agency_id'],
                'client_id' => $payload['client_id'],
                'provider' => $payload['provider'],
                'external_account_id' => $result['external_account_id'],
            ]);

            $this->integrations->update($integration, [
                'display_name' => $result['display_name'],
                'status' => 'connected',
                'last_error' => null,
                'connected_by' => $payload['user_id'],
                'connected_at' => now(),
            ]);

            OauthToken::updateOrCreate(
                ['integration_id' => $integration->id],
                [
                    'access_token_encrypted' => encrypt($result['access_token']),
                    'refresh_token_encrypted' => $result['refresh_token'] ? encrypt($result['refresh_token']) : null,
                    'expires_at' => $result['expires_at'],
                    'scopes' => $result['scopes'],
                ]
            );

            return $integration->fresh();
        });
    }

    public function disconnect(Integration $integration): void
    {
        $provider = $this->manager->resolve($integration->provider);
        $provider->disconnect($integration);

        $this->integrations->update($integration, ['status' => 'disconnected']);
        $integration->oauthToken?->delete();
    }

    public function syncNow(Integration $integration): void
    {
        $provider = $this->manager->resolve($integration->provider);
        $provider->syncMetrics($integration, $integration->client, now()->subDays(30), now());
    }

    protected function decodeState(string $state): array
    {
        try {
            $payload = json_decode(Crypt::decryptString($state), true);
        } catch (\Throwable) {
            throw ValidationException::withMessages(['state' => 'Invalid or expired connection request.']);
        }

        if (now()->timestamp - $payload['issued_at'] > 600) {
            throw ValidationException::withMessages(['state' => 'This connection request has expired. Please try again.']);
        }

        return $payload;
    }

    protected function assertWithinPlanLimit(Agency $agency): void
    {
        $limit = $agency->plan?->integration_limit;

        if (is_null($limit)) {
            return;
        }

        $current = Integration::where('agency_id', $agency->id)->where('status', 'connected')->count();

        if ($current >= $limit) {
            throw ValidationException::withMessages([
                'plan' => "Your current plan allows a maximum of {$limit} active integrations. Upgrade to connect more.",
            ]);
        }
    }
}
