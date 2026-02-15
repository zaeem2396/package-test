<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Ensure laravel-nats provider loads so it can register the queue connector.
        // NatsServiceProvider is deferred; resolving 'nats' loads it and runs its boot (addConnector('nats')).
        if ($this->app->config->get('queue.default') === 'nats' || $this->app->config->get('queue.connections.nats')) {
            $this->app->make('nats');
        }
    }
}
