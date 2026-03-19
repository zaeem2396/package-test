<?php

declare(strict_types=1);

/**
 * Conductor / Orkes Laravel package config (published for PoC).
 *
 * @see vendor/conductor/orkes-laravel/config/conductor.php
 */
return [

    'base_url' => env('CONDUCTOR_SERVER', 'http://127.0.0.1:8080/api'),

    'auth_token' => env('CONDUCTOR_TOKEN'),

    'timeout' => (int) env('CONDUCTOR_TIMEOUT', 30),

    'worker_concurrency' => (int) env('CONDUCTOR_WORKER_CONCURRENCY', 5),

    'poll_interval' => (int) env('CONDUCTOR_POLL_INTERVAL', 2),

    'retry_enabled' => (bool) env('CONDUCTOR_RETRY_ENABLED', false),

    'retry_max_attempts' => (int) env('CONDUCTOR_RETRY_MAX_ATTEMPTS', 3),

    'retry_initial_delay_ms' => (int) env('CONDUCTOR_RETRY_INITIAL_DELAY_MS', 1000),

    /*
     * Task handlers for php artisan conductor:work / conductor:local (PoC).
     */
    'task_handlers' => [
        App\Conductor\Handlers\PocValidateTaskHandler::class,
        App\Conductor\Handlers\PocProcessTaskHandler::class,
        App\Conductor\Handlers\PocNotifyTaskHandler::class,
    ],

];
