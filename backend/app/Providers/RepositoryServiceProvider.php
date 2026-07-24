<?php

namespace App\Providers;

use App\Repositories\Contracts\AgencyRepositoryInterface;
use App\Repositories\Contracts\AIConversationRepositoryInterface;
use App\Repositories\Contracts\AnalyticsMetricRepositoryInterface;
use App\Repositories\Contracts\ClientRepositoryInterface;
use App\Repositories\Contracts\DashboardLayoutRepositoryInterface;
use App\Repositories\Contracts\HealthScoreRepositoryInterface;
use App\Repositories\Contracts\IntegrationRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Eloquent\AgencyRepository;
use App\Repositories\Eloquent\AIConversationRepository;
use App\Repositories\Eloquent\AnalyticsMetricRepository;
use App\Repositories\Eloquent\ClientRepository;
use App\Repositories\Eloquent\DashboardLayoutRepository;
use App\Repositories\Eloquent\HealthScoreRepository;
use App\Repositories\Eloquent\IntegrationRepository;
use App\Repositories\Eloquent\UserRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Map of interface => concrete implementation.
     * Add a line here for every new repository so it resolves via
     * constructor injection anywhere in the app (controllers, services, jobs).
     */
    public array $bindings = [
        UserRepositoryInterface::class => UserRepository::class,
        AgencyRepositoryInterface::class => AgencyRepository::class,
        ClientRepositoryInterface::class => ClientRepository::class,
        IntegrationRepositoryInterface::class => IntegrationRepository::class,
        AnalyticsMetricRepositoryInterface::class => AnalyticsMetricRepository::class,
        DashboardLayoutRepositoryInterface::class => DashboardLayoutRepository::class,
        HealthScoreRepositoryInterface::class => HealthScoreRepository::class,
        AIConversationRepositoryInterface::class => AIConversationRepository::class,
    ];

    public function register(): void
    {
        foreach ($this->bindings as $interface => $concrete) {
            $this->app->bind($interface, $concrete);
        }
    }
}
