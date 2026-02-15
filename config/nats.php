<?php

// Prefer runtime env (Docker: NATS_HOST=nats); fallback to 'nats' when inside Docker so /nats/streams works
$natsHost = getenv('NATS_HOST') ?: env('NATS_HOST');
$natsPort = getenv('NATS_PORT') ?: env('NATS_PORT');
if ($natsHost === null || $natsHost === '') {
    $natsHost = file_exists('/.dockerenv') ? 'nats' : 'localhost';
}
if ($natsPort === null || $natsPort === '') {
    $natsPort = 4222;
}

return [

    'default' => env('NATS_CONNECTION', 'default'),

    'connections' => [
        'default' => [
            'host' => $natsHost,
            'port' => (int) $natsPort,
            'user' => env('NATS_USER'),
            'password' => env('NATS_PASSWORD'),
            'token' => env('NATS_TOKEN'),
        ],
        // Second connection for analytics/metrics (e.g. separate cluster in production)
        'analytics' => [
            'host' => env('NATS_ANALYTICS_HOST', $natsHost),
            'port' => (int) env('NATS_ANALYTICS_PORT', $natsPort),
            'user' => env('NATS_ANALYTICS_USER', env('NATS_USER')),
            'password' => env('NATS_ANALYTICS_PASSWORD', env('NATS_PASSWORD')),
            'token' => env('NATS_ANALYTICS_TOKEN', env('NATS_TOKEN')),
        ],
    ],

    'jetstream' => [
        'domain' => env('NATS_JETSTREAM_DOMAIN'),
        'timeout' => (float) env('NATS_JETSTREAM_TIMEOUT', 5.0),
    ],

    'queue' => [
        'delayed' => [
            'enabled' => env('NATS_QUEUE_DELAYED_ENABLED', true),
            'stream' => env('NATS_QUEUE_DELAYED_STREAM', 'laravel_delayed'),
            'subject_prefix' => env('NATS_QUEUE_DELAYED_SUBJECT_PREFIX', 'laravel.delayed.'),
            'consumer' => env('NATS_QUEUE_DELAYED_CONSUMER', 'laravel_delayed_worker'),
        ],
    ],

];
