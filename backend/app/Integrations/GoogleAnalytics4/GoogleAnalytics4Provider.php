<?php

namespace App\Integrations\GoogleAnalytics4;

use App\Integrations\Contracts\IntegrationProviderInterface;
use App\Models\Client;
use App\Models\Integration;
use App\Repositories\Contracts\AnalyticsMetricRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GoogleAnalytics4Provider implements IntegrationProviderInterface
{
    protected const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    protected const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    protected const ADMIN_API = 'https://analyticsadmin.googleapis.com/v1beta';
    protected const DATA_API = 'https://analyticsdata.googleapis.com/v1beta';

    protected const SCOPES = [
        'https://www.googleapis.com/auth/analytics.readonly',
    ];

    // Maps our generic metric names (used everywhere else in the app) to GA4's own metric names.
    protected const METRIC_MAP = [
        'visitors' => 'totalUsers',
        'sessions' => 'sessions',
        'conversions' => 'conversions',
        'revenue' => 'totalRevenue',
        'bounce_rate' => 'bounceRate',
        'avg_session_duration' => 'averageSessionDuration',
        'organic_traffic' => 'sessions', // filtered by sessionDefaultChannelGroup=Organic Search
    ];

    public function __construct(protected AnalyticsMetricRepositoryInterface $metrics) {}

    public function key(): string
    {
        return 'google_analytics_4';
    }

    public function displayName(): string
    {
        return 'Google Analytics 4';
    }

    public function category(): string
    {
        return 'analytics';
    }

    public function getAuthorizationUrl(string $redirectUri, string $state): string
    {
        $query = http_build_query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => implode(' ', self::SCOPES),
            'access_type' => 'offline',
            'prompt' => 'consent', // ensures a refresh_token is returned every time
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
            Log::warning('GA4 OAuth token exchange failed', ['body' => $response->body()]);
            throw new RuntimeException('Google rejected the authorization code. Please try connecting again.');
        }

        $tokens = $response->json();

        // GA4 requires a follow-up call to let the user pick which property to connect,
        // rather than assuming one — an account can have many properties.
        $property = $this->fetchFirstAccessibleProperty($tokens['access_token']);

        return [
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'] ?? null,
            'expires_at' => isset($tokens['expires_in']) ? now()->addSeconds($tokens['expires_in']) : null,
            'external_account_id' => $property['propertyId'],
            'display_name' => $property['displayName'],
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
            throw new RuntimeException('Failed to refresh Google Analytics access token.');
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
        $propertyId = $integration->external_account_id;

        $response = Http::withToken($accessToken)
            ->post(self::DATA_API."/properties/{$propertyId}:runReport", [
                'dateRanges' => [[
                    'startDate' => Carbon::instance($from)->toDateString(),
                    'endDate' => Carbon::instance($to)->toDateString(),
                ]],
                'dimensions' => [['name' => 'date']],
                'metrics' => collect(self::METRIC_MAP)->values()->unique()->map(fn ($m) => ['name' => $m])->all(),
            ]);

        if ($response->failed()) {
            $integration->forceFill([
                'status' => 'error',
                'last_error' => $response->json('error.message', 'Unknown GA4 API error'),
            ])->save();

            Log::error('GA4 sync failed', ['integration_id' => $integration->id, 'body' => $response->body()]);

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
            // Best-effort revoke; failure here shouldn't block local disconnect.
            Http::asForm()->post('https://oauth2.googleapis.com/revoke', ['token' => decrypt($accessToken)]);
        }
    }

    /** Ensures we have a non-expired access token, refreshing (and persisting) if needed. */
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

    protected function fetchFirstAccessibleProperty(string $accessToken): array
    {
        $response = Http::withToken($accessToken)->get(self::ADMIN_API.'/accountSummaries');

        $property = collect($response->json('accountSummaries', []))
            ->flatMap(fn ($account) => $account['propertySummaries'] ?? [])
            ->first();

        if (! $property) {
            throw new RuntimeException('No Google Analytics 4 properties found for this account.');
        }

        return [
            'propertyId' => str_replace('properties/', '', $property['property']),
            'displayName' => $property['displayName'],
        ];
    }

    protected function persistReport(Integration $integration, Client $client, array $report): void
    {
        $metricOrder = collect(self::METRIC_MAP)->values()->unique()->values()->all(); // GA4 metric names, in request order
        $genericNames = array_flip(self::METRIC_MAP); // GA4 name -> our generic name

        foreach ($report['rows'] ?? [] as $row) {
            $date = Carbon::createFromFormat('Ymd', $row['dimensionValues'][0]['value'])->toDateString();

            foreach ($row['metricValues'] as $i => $metricValue) {
                $ga4MetricName = $metricOrder[$i] ?? null;
                $genericMetric = $genericNames[$ga4MetricName] ?? null;

                if (! $genericMetric) {
                    continue;
                }

                $this->metrics->upsert(
                    clientId: $client->id,
                    integrationId: $integration->id,
                    metric: $genericMetric,
                    date: $date,
                    value: (float) $metricValue['value'],
                );
            }
        }
    }
}
