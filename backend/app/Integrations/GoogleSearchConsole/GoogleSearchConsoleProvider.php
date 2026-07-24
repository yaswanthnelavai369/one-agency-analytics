<?php

namespace App\Integrations\GoogleSearchConsole;

use App\Integrations\Contracts\IntegrationProviderInterface;
use App\Models\Client;
use App\Models\Integration;
use App\Repositories\Contracts\AnalyticsMetricRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Second connector after GA4 — deliberately follows the exact same shape
 * (getAuthorizationUrl -> handleCallback -> syncMetrics -> disconnect) to
 * prove out the IntegrationProviderInterface template with a second, distinct
 * Google API (Search Console instead of Analytics Data).
 */
class GoogleSearchConsoleProvider implements IntegrationProviderInterface
{
    protected const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    protected const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    protected const API_BASE = 'https://www.googleapis.com/webmasters/v3';

    protected const SCOPES = [
        'https://www.googleapis.com/auth/webmasters.readonly',
    ];

    public function __construct(protected AnalyticsMetricRepositoryInterface $metrics) {}

    public function key(): string
    {
        return 'google_search_console';
    }

    public function displayName(): string
    {
        return 'Google Search Console';
    }

    public function category(): string
    {
        return 'seo';
    }

    public function getAuthorizationUrl(string $redirectUri, string $state): string
    {
        $query = http_build_query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => implode(' ', self::SCOPES),
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state,
        ]);

        return self::AUTH_URL.'?'.$query;
    }

    public function handleCallback(string $code, string $redirectUri): array
    {
        $response = Http::asForm()->post(self::TOKEN_URL, [
            'code' => $code,
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code',
        ]);

        if ($response->failed()) {
            Log::warning('Search Console OAuth token exchange failed', ['body' => $response->body()]);
            throw new RuntimeException('Google rejected the authorization code. Please try connecting again.');
        }

        $tokens = $response->json();
        $site = $this->fetchFirstAccessibleSite($tokens['access_token']);

        return [
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'] ?? null,
            'expires_at' => isset($tokens['expires_in']) ? now()->addSeconds($tokens['expires_in']) : null,
            'external_account_id' => $site['siteUrl'],
            'display_name' => $site['siteUrl'],
            'scopes' => self::SCOPES,
        ];
    }

    public function refreshAccessToken(string $refreshToken): array
    {
        $response = Http::asForm()->post(self::TOKEN_URL, [
            'refresh_token' => $refreshToken,
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'grant_type' => 'refresh_token',
        ]);

        if ($response->failed()) {
            throw new RuntimeException('Failed to refresh Search Console access token.');
        }

        $tokens = $response->json();

        return [
            'access_token' => $tokens['access_token'],
            'expires_at' => isset($tokens['expires_in']) ? now()->addSeconds($tokens['expires_in']) : null,
        ];
    }

    public function syncMetrics(Integration $integration, Client $client, \DateTimeInterface $from, \DateTimeInterface $to): void
    {
        $accessToken = $this->validAccessToken($integration);
        $siteUrl = $integration->external_account_id;

        // Search Console data typically lags 2-3 days behind real-time; the API simply
        // returns fewer rows for very recent dates rather than erroring.
        $response = Http::withToken($accessToken)
            ->post(self::API_BASE.'/sites/'.rawurlencode($siteUrl).'/searchAnalytics/query', [
                'startDate' => Carbon::instance($from)->toDateString(),
                'endDate' => Carbon::instance($to)->toDateString(),
                'dimensions' => ['date'],
                'rowLimit' => 1000,
            ]);

        if ($response->failed()) {
            $integration->forceFill([
                'status' => 'error',
                'last_error' => $response->json('error.message', 'Unknown Search Console API error'),
            ])->save();

            Log::error('Search Console sync failed', ['integration_id' => $integration->id, 'body' => $response->body()]);

            return;
        }

        $this->persistReport($integration, $client, $response->json());

        $integration->forceFill([
            'status' => 'connected',
            'last_error' => null,
            'last_synced_at' => now(),
        ])->save();
    }

    public function disconnect(Integration $integration): void
    {
        $accessToken = optional($integration->oauthToken)->access_token_encrypted;

        if ($accessToken) {
            Http::asForm()->post('https://oauth2.googleapis.com/revoke', ['token' => decrypt($accessToken)]);
        }
    }

    protected function validAccessToken(Integration $integration): string
    {
        $token = $integration->oauthToken;

        if (! $token) {
            throw new RuntimeException("Integration [{$integration->id}] has no stored OAuth token.");
        }

        if ($token->expires_at && $token->expires_at->isFuture()) {
            return decrypt($token->access_token_encrypted);
        }

        $refreshed = $this->refreshAccessToken(decrypt($token->refresh_token_encrypted));

        $token->forceFill([
            'access_token_encrypted' => encrypt($refreshed['access_token']),
            'expires_at' => $refreshed['expires_at'],
        ])->save();

        return $refreshed['access_token'];
    }

    protected function fetchFirstAccessibleSite(string $accessToken): array
    {
        $response = Http::withToken($accessToken)->get(self::API_BASE.'/sites');

        // Prefer a verified property over an unverified one when both are present.
        $sites = collect($response->json('siteEntry', []));
        $site = $sites->firstWhere('permissionLevel', '!=', 'siteUnverifiedUser') ?? $sites->first();

        if (! $site) {
            throw new RuntimeException('No Search Console properties found for this Google account.');
        }

        return ['siteUrl' => $site['siteUrl']];
    }

    /**
     * Search Console's response shape differs from GA4's: each row already carries
     * clicks/impressions/ctr/position directly (no separate metrics array to index into).
     * ctr arrives as a 0–1 fraction; we store it as a 0–100 percent to match how
     * SeoScoreCalculator and the ctr widget interpret the generic 'ctr' metric.
     */
    protected function persistReport(Integration $integration, Client $client, array $report): void
    {
        foreach ($report['rows'] ?? [] as $row) {
            $date = $row['keys'][0]; // 'date' dimension, already YYYY-MM-DD

            $this->metrics->upsert($client->id, $integration->id, 'clicks', $date, (float) $row['clicks']);
            $this->metrics->upsert($client->id, $integration->id, 'impressions', $date, (float) $row['impressions']);
            $this->metrics->upsert($client->id, $integration->id, 'ctr', $date, (float) $row['ctr'] * 100);
            $this->metrics->upsert($client->id, $integration->id, 'avg_position', $date, (float) $row['position']);
        }
    }
}
