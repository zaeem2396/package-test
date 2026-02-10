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
        // Ensure the laravel-nats deferred provider is loaded so it registers the
        // 'nats' queue connector. Otherwise dispatching a job with onConnection('nats')
        // only resolves the queue manager, and the connector is never registered.
        if (class_exists(\LaravelNats\Laravel\NatsManager::class)) {
            $this->app->make('nats');
        }
    }
}
