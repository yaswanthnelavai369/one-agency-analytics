<?php

namespace App\Providers;

use App\Repositories\Contracts\AgencyRepositoryInterface;
use App\Repositories\Contracts\ClientRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Eloquent\AgencyRepository;
use App\Repositories\Eloquent\ClientRepository;
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
    ];

    public function register(): void
    {
        foreach ($this->bindings as $interface => $concrete) {
            $this->app->bind($interface, $concrete);
        }
    }
}
