<?php

declare(strict_types=1);

/**
 * Conductor / Orkes Laravel — e-commerce workflow demo (`order_processing`).
 *
 * @see vendor/conductor/orkes-laravel/config/conductor.php
 */
return [

    /*
     * API base URL. Prefer CONDUCTOR_SERVER_URL for Orkes; falls back to CONDUCTOR_SERVER.
     * @see docs/ORKEES_CLOUD_SETUP.md
     */
    'base_url' => env('CONDUCTOR_SERVER_URL', env('CONDUCTOR_SERVER', 'http://127.0.0.1:8080/api')),

    /** Static JWT, or leave unset and use CONDUCTOR_AUTH_KEY + CONDUCTOR_AUTH_SECRET (Orkes). */
    'auth_token' => env('CONDUCTOR_TOKEN'),

    'auth_key' => env('CONDUCTOR_AUTH_KEY'),

    'auth_secret' => env('CONDUCTOR_AUTH_SECRET'),

    /** `bearer` or `orkes` (X-Authorization). Orkes UI tokens need `orkes`. */
    'auth_header_style' => env('CONDUCTOR_AUTH_HEADER_STYLE', 'bearer'),

    'timeout' => (int) env('CONDUCTOR_TIMEOUT', 30),

    'worker_max_retries' => (int) env('CONDUCTOR_WORKER_MAX_RETRIES', 0),

    'poll_interval' => (int) env('CONDUCTOR_POLL_INTERVAL', 2),

    'retry_enabled' => (bool) env('CONDUCTOR_RETRY_ENABLED', false),

    'retry_max_attempts' => (int) env('CONDUCTOR_RETRY_MAX_ATTEMPTS', 3),

    'retry_initial_delay_ms' => (int) env('CONDUCTOR_RETRY_INITIAL_DELAY_MS', 1000),

    /*
     * Task handlers for php artisan conductor:work / conductor:local.
     */
    'task_handlers' => [
        App\Tasks\InventoryTask::class,
        App\Tasks\PaymentTask::class,
        App\Tasks\FraudCheckTask::class,
        App\Tasks\ShippingTask::class,
        App\Tasks\NotificationTask::class,
    ],

];
