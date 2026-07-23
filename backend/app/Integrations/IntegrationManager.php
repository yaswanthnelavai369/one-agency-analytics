<?php

namespace App\Integrations;

use App\Integrations\Contracts\IntegrationProviderInterface;
use App\Integrations\GoogleAnalytics4\GoogleAnalytics4Provider;
use InvalidArgumentException;

/**
 * Central registry mapping provider keys to their implementation class.
 *
 * To add a new connector (e.g. Google Search Console, Meta Ads):
 *   1. Create App\Integrations\{Name}\{Name}Provider implementing IntegrationProviderInterface
 *   2. Add one line to $providers below
 * Nothing else in the app (controllers, jobs, migrations) needs to change.
 */
class IntegrationManager
{
    /** @var array<string, class-string<IntegrationProviderInterface>> */
    protected array $providers = [
        'google_analytics_4' => GoogleAnalytics4Provider::class,
        // 'google_search_console' => GoogleSearchConsoleProvider::class,
        // 'google_ads' => GoogleAdsProvider::class,
        // 'meta_ads' => MetaAdsProvider::class,
        // ...one line per future connector.
    ];

    public function resolve(string $providerKey): IntegrationProviderInterface
    {
        if (! isset($this->providers[$providerKey])) {
            throw new InvalidArgumentException("Unknown integration provider [{$providerKey}].");
        }

        return app($this->providers[$providerKey]);
    }

    public function isSupported(string $providerKey): bool
    {
        return isset($this->providers[$providerKey]);
    }

    /** @return IntegrationProviderInterface[] */
    public function all(): array
    {
        return collect($this->providers)
            ->keys()
            ->map(fn ($key) => $this->resolve($key))
            ->all();
    }

    /** Catalogue for the "available integrations" screen — key, name, category — before any are connected. */
    public function catalogue(): array
    {
        return collect($this->all())->map(fn (IntegrationProviderInterface $p) => [
            'key' => $p->key(),
            'name' => $p->displayName(),
            'category' => $p->category(),
        ])->values()->all();
    }
}
