<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Observa – Datadog observability for Laravel
    |--------------------------------------------------------------------------
    |
    | Enable or disable instrumentation. When disabled, no spans are created.
    |
    */

    'enabled' => env('OBSERVA_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Instrumentation toggles
    |--------------------------------------------------------------------------
    */

    'controllers' => true,
    'jobs' => true,
    'events' => true,
    'listener_spans' => true,
    'middleware' => true,
    'policies' => true,

    /*
    |--------------------------------------------------------------------------
    | Global tags (optional)
    |--------------------------------------------------------------------------
    */

    'tags' => [
        'app' => env('APP_NAME', 'laravel'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Sampling (Phase 2: 0.0 = none, 1.0 = all)
    |--------------------------------------------------------------------------
    */

    'sampling' => (float) env('OBSERVA_SAMPLING', 1.0),

    /*
    |--------------------------------------------------------------------------
    | Phase 3 — Insight Pack (feature grouping, slow detection)
    |--------------------------------------------------------------------------
    | 'features': map feature names to controller/job identifiers for tagging
    |   spans with observa.feature. Optional; leave empty to disable.
    | 'slow_threshold_ms': if set, spans exceeding this duration get
    |   observa.slow = true. Optional; null disables.
    */

    'features' => [],

    'slow_threshold_ms' => env('OBSERVA_SLOW_THRESHOLD_MS') ? (int) env('OBSERVA_SLOW_THRESHOLD_MS') : null,
];
