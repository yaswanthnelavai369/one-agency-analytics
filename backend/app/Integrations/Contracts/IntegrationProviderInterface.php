<?php

namespace App\Integrations\Contracts;

use App\Models\Client;
use App\Models\Integration;

/**
 * Every connector (Google Analytics 4, Search Console, Meta Ads, ...) implements
 * this interface. IntegrationManager resolves providers by key, so adding a new
 * connector is: write a class implementing this interface + register its key —
 * no changes to IntegrationController, the sync job, or the database schema.
 */
interface IntegrationProviderInterface
{
    /** Unique key stored in integrations.provider, e.g. "google_analytics_4". */
    public function key(): string;

    /** Human-readable name shown in the Integrations UI. */
    public function displayName(): string;

    /** Category for grouping in the UI: analytics | ads | social | seo | crm | ecommerce | email. */
    public function category(): string;

    /**
     * Builds the provider's OAuth consent URL. $state is an opaque, signed token
     * the callback route uses to know which agency/client initiated the connect.
     */
    public function getAuthorizationUrl(string $redirectUri, string $state): string;

    /**
     * Exchanges the OAuth callback's authorization code for tokens, and returns
     * everything IntegrationService needs to persist the connection: tokens,
     * the external account/property id, and a display name for it.
     *
     * @return array{access_token: string, refresh_token: ?string, expires_at: ?\DateTimeInterface,
     *               external_account_id: string, display_name: string, scopes: array}
     */
    public function handleCallback(string $code, string $redirectUri): array;

    /** Refreshes an expired access token using the stored refresh token. */
    public function refreshAccessToken(string $refreshToken): array;

    /**
     * Pulls metrics for the given date range and writes them via AnalyticsMetricRepository.
     * Called by SyncIntegrationDataJob on the scheduled cadence (see integrations.sync_frequency).
     */
    public function syncMetrics(Integration $integration, Client $client, \DateTimeInterface $from, \DateTimeInterface $to): void;

    /** Revokes the token with the provider, if the provider supports remote revocation. */
    public function disconnect(Integration $integration): void;
}
